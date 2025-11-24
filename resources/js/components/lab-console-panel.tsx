import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { Card, CardContent, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import { toast } from 'sonner';
import { Loader2, Power, RefreshCcw, Terminal, Code } from 'lucide-react';
import { useConsole } from '@/hooks/useConsole';
import IOSConsole from '@/components/IOSConsole';
import { useActionLogs } from '@/contexts/ActionLogsContext';
import ConsoleCommandTester from '@/components/ConsoleCommandTester';

type LabNode = {
    id: string;
    label?: string;
    name?: string;
    state?: string;
    node_definition?: string;
};

// Types basés sur les réponses API
type ConsoleInfo = {
    id: string;
    console_id?: string;
    console_type?: string;
    protocol?: string;
    [key: string]: unknown;
};

export type ConsoleSessionsResponse = {
    sessions?: ConsoleSession[];
    [key: string]: unknown;
};

export type ConsoleSession = {
    session_id?: string;
    node_id?: string;
    lab_id?: string;
    ws_href?: string;
    type?: string;
    protocol?: string;
    [key: string]: unknown;
};

type AvailableConsoleTypes = {
    serial?: boolean;
    vnc?: boolean;
    console?: boolean;
    [key: string]: boolean | undefined;
};

type Props = {
    cmlLabId: string;
    nodes: LabNode[];
    initialSessions: ConsoleSessionsResponse | ConsoleSession[] | null;
    labTitle?: string;
};

type SessionState = {
    sessionId: string;
    nodeId: string;
    consoleUrl?: string; // URL de la console CML (iframe)
    consoleId?: string; // ID de la console pour récupérer les logs
    wsHref?: string; // Déprécié - CML n'utilise pas de WebSocket
    protocol?: string;
    type?: string;
};

type ConnectionState = 'idle' | 'connecting' | 'open' | 'closing' | 'closed' | 'error';

const CONNECTION_META: Record<ConnectionState, { label: string; variant: 'outline' | 'secondary' | 'destructive'; className?: string }> = {
    idle: { label: 'En attente', variant: 'outline' },
    connecting: { label: 'Connexion…', variant: 'secondary', className: 'animate-pulse' },
    open: { label: 'Connectée', variant: 'secondary', className: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300' },
    closing: { label: 'Fermeture…', variant: 'secondary', className: 'animate-pulse' },
    closed: { label: 'Fermée', variant: 'outline' },
    error: { label: 'Erreur', variant: 'destructive' },
};

const MAX_LOG_LINES = 500;

export default function LabConsolePanel({ cmlLabId, nodes, initialSessions, labTitle }: Props) {
    // Mode d'affichage : 'iframe' (console CML native) ou 'ios' (console intelligente IOS)
    const [consoleMode, setConsoleMode] = useState<'iframe' | 'ios'>('ios');
    
    // Trouver le premier node valide avec un id - utiliser useMemo pour éviter les re-calculs
    const firstValidNodeId = useMemo(() => {
        if (!nodes || nodes.length === 0) return '';
        const firstNode = nodes.find(node => node.id && node.id.trim() !== '');
        return firstNode?.id ?? '';
    }, [nodes]);

    const [selectedNodeId, setSelectedNodeId] = useState<string>(() => {
        if (!nodes || nodes.length === 0) return '';
        const firstNode = nodes.find(node => node.id && node.id.trim() !== '');
        return firstNode?.id ?? '';
    });
    const [availableTypes, setAvailableTypes] = useState<AvailableConsoleTypes>({});
    const [consoles, setConsoles] = useState<ConsoleInfo[]>([]);
    const [loadingConsoles, setLoadingConsoles] = useState(false);
    const [loadingSession, setLoadingSession] = useState(false);
    const [session, setSession] = useState<SessionState | null>(null);
    const [connectionState, setConnectionState] = useState<ConnectionState>('idle');
    const [command, setCommand] = useState('');
    const [logLines, setLogLines] = useState<string[]>([]);
    const [error, setError] = useState<string | null>(null);
    const wsRef = useRef<WebSocket | null>(null);
    const sessionRef = useRef<SessionState | null>(null);
    
    // Utiliser le contexte partagé pour les logs d'actions
    const { actionLogs, addActionLog, updateActionLogStatus } = useActionLogs();
    
    // Utiliser le hook useConsole pour les appels API typés
    const consoleApi = useConsole();
    
    // Utiliser useRef pour garder des références stables aux méthodes de consoleApi
    // Mettre à jour les références directement dans le corps du composant (pas dans useEffect)
    const getNodeConsolesRef = useRef(consoleApi.getNodeConsoles);
    const createSessionRef = useRef(consoleApi.createSession);
    const closeSessionRef = useRef(consoleApi.closeSession);
    const getConsoleLogRef = useRef(consoleApi.getConsoleLog);
    
    getNodeConsolesRef.current = consoleApi.getNodeConsoles;
    createSessionRef.current = consoleApi.createSession;
    closeSessionRef.current = consoleApi.closeSession;
    getConsoleLogRef.current = consoleApi.getConsoleLog;

    // Mettre à jour selectedNodeId quand nodes change (seulement si selectedNodeId est vide)
    useEffect(() => {
        if (!selectedNodeId && firstValidNodeId && firstValidNodeId !== selectedNodeId) {
            setSelectedNodeId(firstValidNodeId);
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [firstValidNodeId]); // Ne pas inclure selectedNodeId pour éviter les boucles

    const normalizedSessions = useMemo<ConsoleSession[]>(() => {
        if (!initialSessions) return [];
        if (Array.isArray(initialSessions)) return initialSessions;
        if (Array.isArray(initialSessions.sessions)) return initialSessions.sessions;
        return [];
    }, [initialSessions]);

    const appendLog = useCallback((line: string) => {
        setLogLines(prev => {
            const next = [...prev, line];
            if (next.length > MAX_LOG_LINES) {
                return next.slice(next.length - MAX_LOG_LINES);
            }
            return next;
        });
    }, []);


    const teardownWebsocket = useCallback((options?: { reason?: string; silent?: boolean }) => {
        if (wsRef.current) {
            try {
                wsRef.current.close();
            } catch (err) {
                console.error('WebSocket close error', err);
            }
            wsRef.current = null;
        }
        if (options?.reason && !options.silent) {
            appendLog(`[Console] ${options.reason}`);
        }
    }, [appendLog]);

    useEffect(() => {
        sessionRef.current = session;
    }, [session]);

    useEffect(() => {
        if (!selectedNodeId || !cmlLabId) return;

        const controller = new AbortController();
        let isMounted = true;
        
        const fetchConsoles = async () => {
            setLoadingConsoles(true);
            setError(null);
            try {
                const data = await getNodeConsolesRef.current(cmlLabId, selectedNodeId);
                if (data && !controller.signal.aborted && isMounted) {
                    setConsoles(Array.isArray(data.consoles) ? data.consoles : []);
                    setAvailableTypes(data.available_types || {});
                } else if (!data && !controller.signal.aborted && isMounted) {
                    // Si data est null, c'est qu'il y a eu une erreur (gérée par useConsole)
                    setConsoles([]);
                    setAvailableTypes({});
                }
            } catch (err) {
                if (controller.signal.aborted || !isMounted) return;
                console.error(err);
                setError("Impossible de charger les consoles pour ce nœud.");
                setConsoles([]);
                setAvailableTypes({});
            } finally {
                if (!controller.signal.aborted && isMounted) {
                    setLoadingConsoles(false);
                }
            }
        };

        fetchConsoles();

        return () => {
            isMounted = false;
            controller.abort();
        };
    }, [cmlLabId, selectedNodeId]);

    const closeSession = useCallback(async ({ skipApi = false, reason }: { skipApi?: boolean; reason?: string } = {}) => {
        const activeSession = sessionRef.current;
        if (!activeSession) {
            if (reason) {
                appendLog(`[Console] ${reason}`);
            }
            teardownWebsocket({ silent: true });
            setSession(null);
            setConnectionState('closed');
            return;
        }

        if (reason) {
            appendLog(`[Console] ${reason}`);
        }

        setConnectionState('closing');
        teardownWebsocket({ silent: true });

        if (!skipApi && closeSessionRef.current) {
            const success = await closeSessionRef.current(activeSession.sessionId);
            if (!success) {
                console.warn('Console session close failed');
            }
        }

        sessionRef.current = null;
        setSession(null);
        setConnectionState('closed');
    }, [appendLog, teardownWebsocket]);

    useEffect(() => {
        return () => {
            void closeSession({ skipApi: true, reason: 'Session console fermée (changement de page)' });
        };
    }, [closeSession]);

    useEffect(() => {
        const active = sessionRef.current;
        if (active && active.nodeId !== selectedNodeId) {
            void closeSession({ reason: 'Session fermée (changement de nœud)' });
        }
    }, [selectedNodeId, closeSession]);

    // Vérifier la disponibilité de la connexion avant de créer une session
    const checkConnectionAvailability = useCallback(async (): Promise<boolean> => {
        try {
            // Vérifier que le serveur CML est accessible
            const response = await fetch('/api/console/ping', {
                method: 'GET',
                headers: { 'Accept': 'application/json' },
                cache: 'no-store',
            });
            
            if (!response.ok) {
                console.warn('API console non disponible:', response.status);
                return false;
            }
            
            const data = await response.json();
            return data.status === 'ok';
        } catch (err) {
            console.error('Erreur lors de la vérification de la connexion:', err);
            return false;
        }
    }, []);

    useEffect(() => {
        const activeSession = session;

        if (!activeSession) {
            teardownWebsocket({ silent: true });
            setConnectionState('idle');
            return;
        }

        // CML utilise des URLs de console (iframe), pas de WebSocket
        if (activeSession.consoleUrl) {
            teardownWebsocket({ silent: true });
            setConnectionState('open');
            
            // Vérifier périodiquement que la session est toujours disponible
            const checkInterval = setInterval(async () => {
                try {
                    const isAvailable = await checkConnectionAvailability();
                    if (!isAvailable && connectionState === 'open') {
                        console.warn('Connexion perdue, tentative de reconnexion...');
                        setConnectionState('error');
                        toast.warning('Connexion perdue. Vérification de la disponibilité...');
                    } else if (isAvailable && connectionState === 'error') {
                        setConnectionState('open');
                        toast.success('Connexion rétablie.');
                    }
                } catch (err) {
                    console.error('Erreur lors de la vérification de la connexion:', err);
                }
            }, 30000); // Vérifier toutes les 30 secondes

            return () => {
                clearInterval(checkInterval);
            };
        }
        
        // Fallback pour WebSocket (si jamais utilisé dans le futur)
        if (!activeSession.wsHref) {
            teardownWebsocket({ silent: true });
            setConnectionState('open');
            appendLog('[Console] Session créée sans WebSocket. Les commandes seront envoyées via l\'API.');
            return;
        }

        setConnectionState('connecting');
        teardownWebsocket({ silent: true });

        let ws: WebSocket;
        try {
            if (activeSession.protocol) {
                ws = new WebSocket(activeSession.wsHref, [activeSession.protocol]);
            } else {
                ws = new WebSocket(activeSession.wsHref);
            }
        } catch (err) {
            console.error('Unable to open WebSocket', err);
            setError('Impossible d\'ouvrir la connexion console.');
            setConnectionState('error');
            return;
        }

        wsRef.current = ws;

        const handleOpen = () => {
            setConnectionState('open');
            setError(null);
            appendLog(`[Console] Session ${activeSession.sessionId} connectée via WebSocket.`);
            console.log('WebSocket ouvert avec succès');
        };

        const handleMessage = (event: MessageEvent) => {
            if (typeof event.data === 'string') {
                appendLog(event.data);
            } else if (event.data instanceof Blob) {
                event.data.text().then(appendLog).catch(() => appendLog('[Console] Flux binaire reçu'));
            } else {
                appendLog('[Console] Message non textuel reçu');
            }
        };

        const handleError = (event: Event) => {
            console.error('WebSocket error', event);
            setConnectionState('error');
            setError('Erreur de connexion WebSocket. Vérifiez que le serveur CML est accessible.');
            appendLog('[Console] Erreur de transport WebSocket');
            toast.error('Erreur de connexion WebSocket. Les commandes peuvent ne pas fonctionner.');
        };

        const handleClose = (event: CloseEvent) => {
            if (wsRef.current === ws) {
                wsRef.current = null;
            }
            if (!event.wasClean) {
                appendLog('[Console] Connexion fermée de manière inattendue');
                setError('Connexion console interrompue.');
            } else {
                appendLog('[Console] Connexion fermée');
            }
            setConnectionState(prev => (prev === 'closing' ? 'closed' : 'closed'));
        };

        ws.addEventListener('open', handleOpen);
        ws.addEventListener('message', handleMessage);
        ws.addEventListener('error', handleError);
        ws.addEventListener('close', handleClose);

        return () => {
            ws.removeEventListener('open', handleOpen);
            ws.removeEventListener('message', handleMessage);
            ws.removeEventListener('error', handleError);
            ws.removeEventListener('close', handleClose);
            if (wsRef.current === ws) {
                try {
                    ws.close();
                } catch (err) {
                    console.error('WebSocket cleanup error', err);
                }
                wsRef.current = null;
            }
        };
    }, [session, appendLog, teardownWebsocket, checkConnectionAvailability, connectionState]);

    const handleCreateSession = useCallback(async (type?: string) => {
        if (!selectedNodeId) {
            toast.error('Sélectionnez un nœud avant de lancer une console.');
            return;
        }

        // Vérifier la disponibilité de la connexion avant de créer la session
        const isAvailable = await checkConnectionAvailability();
        if (!isAvailable) {
            toast.error('Connexion non disponible. Vérifiez que le serveur CML est accessible.');
            return;
        }

        addActionLog({
            type: 'session',
            action: 'Création de session console',
            status: 'pending',
            nodeId: selectedNodeId,
            details: `Ouverture d'une session console pour le nœud ${selectedNodeId}${type ? ` (type: ${type})` : ''}`,
        });

        setLoadingSession(true);
        setError(null);
        await closeSession({ reason: 'Fermeture de la session existante' });
        setLogLines([`[Console] Connexion en cours pour le nœud ${selectedNodeId}`]);

        try {
            const data = await createSessionRef.current({
                    lab_id: cmlLabId,
                    node_id: selectedNodeId,
                    type,
            });

            if (!data) {
                throw new Error('Impossible de créer la session. Aucune réponse du serveur.');
            }
            
            // Vérifier si la réponse contient une erreur
            if ('error' in data && data.error) {
                const errorMessage = typeof data.error === 'string' ? data.error : JSON.stringify(data.error);
                throw new Error(errorMessage);
            }

            const sessionId = data.session_id ?? data.id ?? '';
            // CML utilise des URLs de console, pas des WebSockets
            const consoleUrl = data.console_url ?? data.url ?? data.ws_href ?? null;

            console.log('Session créée:', {
                sessionId,
                hasConsoleUrl: !!consoleUrl,
                consoleUrl,
                dataKeys: Object.keys(data),
                fullData: data,
            });

            if (!sessionId) {
                throw new Error('Réponse invalide du serveur (session_id manquant).');
            }

            if (!consoleUrl) {
                throw new Error('URL de console non fournie par le serveur.');
            }

            const consoleUrlString = typeof consoleUrl === 'string' ? consoleUrl : String(consoleUrl);

            // Récupérer le consoleId depuis les consoles disponibles ou depuis la réponse
            let consoleId: string | undefined = undefined;
            if (data.console_id && typeof data.console_id === 'string') {
                consoleId = data.console_id;
            } else if (data.consoleId && typeof data.consoleId === 'string') {
                consoleId = data.consoleId;
            } else if (consoles.length > 0) {
                // Utiliser le premier console disponible
                const firstConsole = consoles.find(c => c.id || c.console_id);
                const foundId = firstConsole?.id ?? firstConsole?.console_id;
                if (foundId && typeof foundId === 'string') {
                    consoleId = foundId;
                }
            }
            
            const nextSession: SessionState = {
                sessionId,
                nodeId: data.node_id ?? selectedNodeId,
                consoleUrl: consoleUrlString,
                consoleId: consoleId,
                wsHref: undefined, // CML n'utilise pas de WebSocket
                protocol: data.protocol,
                type: data.type ?? type,
            };

            sessionRef.current = nextSession;
            setSession(nextSession);
            setConnectionState('open'); // CML utilise des iframes, pas de connexion WebSocket
            appendLog('[Console] Session console créée.');
            // Masquer l'URL complète, ne garder que l'ID de session
            const urlParts = consoleUrlString.split('/');
            const urlSessionId = urlParts[urlParts.length - 1] || 'N/A';
            appendLog(`[Console] Session ID: ${urlSessionId}`);
            
            // Mettre à jour le log d'action
            const lastLog = actionLogs.find(log => log.type === 'session' && log.status === 'pending' && log.nodeId === selectedNodeId);
            if (lastLog) {
                updateActionLogStatus(lastLog.id, 'success', `Session créée avec succès. ID: ${urlSessionId}`);
            }
            
            toast.success('Session console créée.');
        } catch (err) {
            console.error('Erreur lors de la création de session:', err);
            const errorMessage = err instanceof Error ? err.message : 'Impossible de créer la session.';
            setError(errorMessage);
            setConnectionState('error');
            
            // Mettre à jour le log d'action avec l'erreur
            const lastLog = actionLogs.find(log => log.type === 'session' && log.status === 'pending' && log.nodeId === selectedNodeId);
            if (lastLog) {
                updateActionLogStatus(lastLog.id, 'error', `Erreur: ${errorMessage}`);
            }
            
            // Message d'erreur plus détaillé
            let toastMessage = 'Création de session impossible.';
            if (errorMessage.includes('Unable to post')) {
                toastMessage = 'Impossible de se connecter au serveur CML. Vérifiez que le lab est démarré et que le serveur est accessible.';
            } else if (errorMessage.includes('401') || errorMessage.includes('Token')) {
                toastMessage = 'Token CML expiré. Veuillez vous reconnecter.';
            } else if (errorMessage.includes('404')) {
                toastMessage = 'Endpoint console non trouvé. Vérifiez la version de CML.';
            }
            
            toast.error(toastMessage);
        } finally {
            setLoadingSession(false);
        }
    }, [appendLog, cmlLabId, closeSession, selectedNodeId, addActionLog, actionLogs, updateActionLogStatus, checkConnectionAvailability, consoles]);

    const handleCloseSession = useCallback(() => {
        addActionLog({
            type: 'session',
            action: 'Fermeture de session console',
            status: 'pending',
            nodeId: sessionRef.current?.nodeId,
            details: 'Fermeture manuelle de la session',
        });
        void closeSession({ reason: 'Session fermée manuellement' });
        // Mettre à jour le statut après la fermeture
        setTimeout(() => {
            const lastLog = actionLogs.find(log => log.type === 'session' && log.action === 'Fermeture de session console' && log.status === 'pending');
            if (lastLog) {
                updateActionLogStatus(lastLog.id, 'success', 'Session fermée avec succès');
            }
        }, 500);
    }, [closeSession, addActionLog, actionLogs, updateActionLogStatus]);

    const handleLoadLog = useCallback(async (consoleId: string, silent = false) => {
        if (!consoleId) return;

        if (!silent) {
            appendLog('[Console] Récupération du journal…');
        }
        try {
            const data = await getConsoleLogRef.current(cmlLabId, selectedNodeId, consoleId);

            if (!data) {
                throw new Error('Impossible de récupérer le log');
            }

            // Stocker les lignes déjà affichées pour éviter les doublons
            const existingLines = new Set(logLines);
            let newLinesCount = 0;

            if (Array.isArray(data.log)) {
                data.log.forEach((line: string) => {
                    if (line.trim() && !existingLines.has(line.trim())) {
                        appendLog(line);
                        existingLines.add(line.trim());
                        newLinesCount++;
                    }
                });
            } else if (typeof data.log === 'string') {
                data.log.split(/\r?\n/).forEach((line: string) => {
                    if (line.trim() && !existingLines.has(line.trim())) {
                        appendLog(line);
                        existingLines.add(line.trim());
                        newLinesCount++;
                    }
                });
            } else {
                const logStr = typeof data === 'string' ? data : JSON.stringify(data, null, 2);
                if (!existingLines.has(logStr.trim())) {
                    appendLog(logStr);
                    newLinesCount++;
                }
            }
            
            if (newLinesCount > 0 && !silent) {
                appendLog(`[Console] ${newLinesCount} nouvelle(s) ligne(s) ajoutée(s)`);
            }
        } catch (err) {
            console.error('Erreur lors de la récupération du log:', err);
            if (!silent) {
                toast.error('Impossible de récupérer le journal de console.');
            }
        }
    }, [appendLog, cmlLabId, selectedNodeId, logLines]);

    const handleSendCommand = useCallback(async () => {
        const trimmed = command.trimEnd();
        if (!trimmed) return;

        const activeSession = sessionRef.current;
        if (!activeSession) {
            toast.error('Aucune session console active.');
            return;
        }

        // Créer un log d'action pour cette commande
        const actionLogId = `${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;
        addActionLog({
            type: 'command',
            action: 'Envoi de commande',
            command: trimmed,
            status: 'pending',
            nodeId: activeSession.nodeId,
            details: `Commande: ${trimmed}`,
        });

        // PRIORITÉ 1: Si on utilise consoleUrl (iframe CML), les commandes sont envoyées via l'iframe
        // et on récupère les résultats via polling des logs
        // Vérifier consoleUrl en premier pour éviter le message d'erreur WebSocket
        if (activeSession.consoleUrl || activeSession.sessionId) {
            appendLog(`> ${trimmed}`);
            updateActionLogStatus(actionLogId, 'sent', `Commande envoyée: ${trimmed}`);
            setCommand('');
            
            // Récupérer le consoleId depuis la session (sessionId = consoleKey pour CML)
            // ou depuis les consoles disponibles
            const consoleId = activeSession.consoleId 
                ?? activeSession.sessionId // Pour CML, sessionId = consoleKey
                ?? (consoles.length > 0 ? (consoles[0]?.id ?? consoles[0]?.console_id) : null);
            
            console.log('Envoi de commande avec consoleUrl:', {
                hasConsoleUrl: !!activeSession.consoleUrl,
                consoleId: consoleId,
                sessionId: activeSession.sessionId,
                sessionConsoleId: activeSession.consoleId,
                consolesCount: consoles.length,
            });
            
            if (consoleId) {
                // Attendre un court délai puis récupérer les logs pour voir les résultats
                setTimeout(async () => {
                    try {
                        await handleLoadLog(consoleId, true); // silent = true pour éviter les messages répétitifs
                        updateActionLogStatus(actionLogId, 'success', `Commande exécutée: ${trimmed}`);
                    } catch (err) {
                        console.error('Erreur lors de la récupération du log:', err);
                        updateActionLogStatus(actionLogId, 'error', `Erreur lors de la récupération des résultats: ${err instanceof Error ? err.message : 'Erreur inconnue'}`);
                    }
                }, 1500); // Attendre 1.5 secondes pour que la commande s'exécute
                
                // Mettre en place un polling pour récupérer les logs périodiquement
                const pollInterval = setInterval(async () => {
                    try {
                        await handleLoadLog(consoleId, true); // silent = true pour éviter les messages répétitifs
                    } catch (err) {
                        console.error('Erreur lors du polling du log:', err);
                        clearInterval(pollInterval);
                    }
                }, 2000); // Poll toutes les 2 secondes
                
                // Arrêter le polling après 30 secondes
                setTimeout(() => {
                    clearInterval(pollInterval);
                }, 30000);
            } else {
                appendLog('[Console] Console ID non disponible. Impossible de récupérer les logs automatiquement.');
                updateActionLogStatus(actionLogId, 'error', 'Console ID non disponible');
            }
            
            toast.success(`Commande envoyée: ${trimmed}`);
            return;
        }

        // Sinon, essayer d'envoyer via l'API REST si disponible
        // Pour l'instant, on affiche juste un message d'erreur
        updateActionLogStatus(actionLogId, 'error', 'Connexion WebSocket non disponible');
        toast.error('Connexion WebSocket non disponible. Veuillez attendre que la connexion soit établie.');
        appendLog(`> ${trimmed} (en attente de connexion...)`);
        setCommand('');
    }, [appendLog, command, addActionLog, updateActionLogStatus, sessionRef, consoles, handleLoadLog]);

    const connectionBadge = useMemo(() => CONNECTION_META[connectionState], [connectionState]);
    // Une session est "ouverte" si elle existe et que la connexion est ouverte ou en cours de connexion
    // ou si la session existe sans WebSocket (mode sans WebSocket)
    // Pour CML, si on a une consoleUrl, on considère la session comme ouverte même sans WebSocket
    const isSessionOpen = useMemo(() => {
        const open = session !== null && (
            connectionState === 'open' 
            || connectionState === 'connecting' 
            || (session.consoleUrl && connectionState !== 'error' && connectionState !== 'closed')
        );
        
        // Debug log
        if (session) {
            console.log('LabConsolePanel: État de la session', {
                hasSession: !!session,
                hasConsoleUrl: !!session.consoleUrl,
                connectionState,
                isSessionOpen: open,
            });
        }
        
        return open;
    }, [session, connectionState]);

    const selectedNode = useMemo(() => nodes.find(node => node.id === selectedNodeId) ?? null, [nodes, selectedNodeId]);

    return (
        <Card className="flex h-full min-h-[28rem] flex-col border-0 shadow-sm">
            <CardHeader className="space-y-1">
                <div className="flex items-center justify-between gap-3">
                    <CardTitle className="flex items-center gap-2 text-lg">
                        <Terminal className="h-5 w-5 text-primary" />
                        Console
                    </CardTitle>

                    <Badge
                        variant={connectionBadge.variant}
                        className={connectionBadge.className}
                    >
                        {connectionBadge.label}
                    </Badge>
                </div>

                <div className="flex flex-col gap-2 lg:flex-row lg:items-center">
                    {nodes.length === 0 ? (
                        <div className="text-sm text-muted-foreground">
                            Aucun nœud disponible. Le lab doit être démarré pour voir les nœuds.
                        </div>
                    ) : (
                    <Select
                        value={selectedNodeId}
                            onValueChange={value => {
                                if (value && value !== selectedNodeId) {
                                    setSelectedNodeId(value);
                                }
                            }}
                        disabled={loadingConsoles || loadingSession}
                    >
                            <SelectTrigger className="w-full lg:w-auto">
                            <SelectValue placeholder="Sélectionner un nœud" />
                        </SelectTrigger>
                        <SelectContent>
                                {nodes.map(node => {
                                    const nodeId = node.id || '';
                                    if (!nodeId) return null;
                                    return (
                                        <SelectItem key={nodeId} value={nodeId}>
                                            {node.label ?? node.name ?? nodeId}
                                </SelectItem>
                                    );
                                })}
                        </SelectContent>
                    </Select>
                    )}

                    <div className="flex flex-wrap items-center gap-2 text-xs text-muted-foreground">
                        {selectedNode?.state && (
                            <Badge variant="secondary">{selectedNode.state}</Badge>
                        )}
                        {selectedNode?.node_definition && (
                            <Badge variant="outline">{selectedNode.node_definition}</Badge>
                        )}
                    </div>
                </div>

                {error && <p className="text-sm text-red-500">{error}</p>}

                {loadingConsoles && (
                    <div className="flex items-center gap-2 text-sm text-muted-foreground">
                        <Loader2 className="h-4 w-4 animate-spin" />
                        Chargement des consoles…
                    </div>
                )}
            </CardHeader>

            <CardContent className="flex flex-1 flex-col gap-3">
                <div className="flex flex-wrap items-center gap-2">
                    <Button
                        variant="outline"
                        size="sm"
                        onClick={() => handleCreateSession()}
                        disabled={loadingSession || connectionState === 'connecting' || !selectedNodeId || nodes.length === 0}
                        className="gap-2"
                    >
                        {loadingSession ? <Loader2 className="h-4 w-4 animate-spin" /> : <Terminal className="h-4 w-4" />}
                        Ouvrir une session
                    </Button>

                    {(availableTypes?.serial || availableTypes?.vnc) && (
                        <>
                            {availableTypes?.serial && (
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={() => handleCreateSession('serial')}
                                    disabled={loadingSession || connectionState === 'connecting'}
                                >
                                    Série
                                </Button>
                            )}
                            {availableTypes?.vnc && (
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={() => handleCreateSession('vnc')}
                                    disabled={loadingSession || connectionState === 'connecting'}
                                >
                                    VNC
                                </Button>
                            )}
                        </>
                    )}

                    <Button
                        variant="ghost"
                        size="sm"
                        onClick={() => {
                            if (selectedNodeId) {
                                // Recharger les consoles pour le node sélectionné
                                setLoadingConsoles(true);
                                setError(null);
                                getNodeConsolesRef.current(cmlLabId, selectedNodeId)
                                    .then(data => {
                                        if (data) {
                                            setConsoles(Array.isArray(data.consoles) ? data.consoles : []);
                                            setAvailableTypes(data.available_types || {});
                                        }
                                    })
                                    .catch(err => {
                                        console.error(err);
                                        setError("Impossible de charger les consoles pour ce nœud.");
                                    })
                                    .finally(() => {
                                        setLoadingConsoles(false);
                                    });
                            }
                        }}
                        disabled={loadingConsoles || !selectedNodeId}
                        className="gap-2 text-muted-foreground"
                    >
                        <RefreshCcw className="h-4 w-4" />
                        Actualiser
                    </Button>

                    {session && (
                        <Button
                            variant="destructive"
                            size="sm"
                            onClick={handleCloseSession}
                            className="gap-2"
                        >
                            <Power className="h-4 w-4" />
                            Fermer la session
                        </Button>
                    )}
                </div>

                {consoles.length > 0 && (
                    <div className="flex flex-wrap items-center gap-2 text-xs">
                        {consoles.map(consoleInfo => {
                            const consoleId = consoleInfo.id ?? consoleInfo.console_id ?? '';
                            return (
                                <Badge
                                    key={consoleId}
                                    variant="secondary"
                                    className="cursor-pointer"
                                    onClick={() => handleLoadLog(consoleId)}
                                >
                                    {consoleInfo.console_type ?? consoleInfo.protocol ?? 'console'} · {consoleId.slice(0, 6)}
                                </Badge>
                            );
                        })}
                    </div>
                )}

                <Separator />

                {/* Section console */}
                <div className="flex-1 flex flex-col">
                    {/* Sélecteur de mode console */}
                    <div className="flex items-center gap-2 pb-2">
                        <span className="text-xs text-muted-foreground">Mode console :</span>
                        <Button
                            variant={consoleMode === 'ios' ? 'default' : 'outline'}
                            size="sm"
                            onClick={() => setConsoleMode('ios')}
                            className="h-7"
                        >
                            <Code className="h-3 w-3 mr-1" />
                            IOS Intelligent
                        </Button>
                    </div>

                    {/* Console intelligente IOS - Toujours afficher, jamais l'iframe CML */}
                    {consoleMode === 'ios' ? (
                        <IOSConsole
                            onSendCommand={(cmd) => {
                                // Créer un log d'action pour cette commande
                                const actionLogId = `${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;
                                addActionLog({
                                    type: 'command',
                                    action: 'Envoi de commande',
                                    command: cmd,
                                    status: 'pending',
                                    nodeId: sessionRef.current?.nodeId,
                                    details: `Commande: ${cmd}`,
                                });
                                
                                // Appeler handleSendCommand avec la commande
                                const trimmed = cmd.trimEnd();
                                if (!trimmed) return;
                                
                                const activeSession = sessionRef.current;
                                if (!activeSession) {
                                    toast.error('Aucune session console active.');
                                    return;
                                }
                                
                                // PRIORITÉ 1: Si on utilise consoleUrl (iframe CML), utiliser handleSendCommand
                                // qui gère correctement le mode iframe avec polling des logs
                                if (activeSession.consoleUrl || activeSession.sessionId) {
                                    // Utiliser la fonction handleSendCommand qui gère correctement le mode iframe
                                    handleSendCommand();
                                    return;
                                }
                                
                                // PRIORITÉ 2: Si on a un WebSocket ouvert, l'utiliser
                                if (wsRef.current && wsRef.current.readyState === WebSocket.OPEN) {
                                    const payload = trimmed.endsWith('\n') ? trimmed : `${trimmed}\n`;
                                    try {
                                        wsRef.current.send(payload);
                                        appendLog(`> ${trimmed}`);
                                        
                                        // Mettre à jour le statut du log
                                        updateActionLogStatus(actionLogId, 'sent', `Commande envoyée via WebSocket: ${trimmed}`);
                                        
                                        // Mettre à jour le statut après un court délai pour indiquer le succès
                                        setTimeout(() => {
                                            updateActionLogStatus(actionLogId, 'success', `Commande envoyée avec succès: ${trimmed}`);
                                        }, 500);
                                        
                                        toast.success(`Commande envoyée: ${trimmed}`);
                                    } catch (err) {
                                        console.error(err);
                                        updateActionLogStatus(actionLogId, 'error', `Erreur lors de l'envoi: ${err instanceof Error ? err.message : 'Erreur inconnue'}`);
                                        toast.error('Échec d\'envoi de la commande.');
                                        setConnectionState('error');
                                    }
                                    return;
                                }
                                
                                // Sinon, utiliser handleSendCommand qui gère tous les cas
                                handleSendCommand();
                            }}
                            output={logLines}
                            isConnected={Boolean(isSessionOpen)}
                            nodeLabel={selectedNode?.label || selectedNode?.name || undefined}
                            nodeState={selectedNode?.state}
                            className="flex-1"
                        />
                    ) : (
                        <div className="flex-1 overflow-hidden rounded-lg border border-muted bg-black/95 dark:bg-black">
                            {logLines.length > 0 ? (
                                <div className="overflow-y-auto p-3 font-mono text-xs text-emerald-200">
                                    {logLines.map((line, index) => (
                                        <pre key={index} className="whitespace-pre-wrap">
                                            {line}
                                        </pre>
                                    ))}
                                </div>
                            ) : (
                                <div className="flex h-full items-center justify-center p-3">
                                    <p className="text-muted-foreground">La sortie console apparaîtra ici.</p>
                                </div>
                            )}
                        </div>
                    )}
                </div>

                {/* Section Tests TDD */}
                <Separator />
                <ConsoleCommandTester
                    onCommandSend={async (command) => {
                        // Utiliser handleSendCommand pour envoyer la commande
                        const trimmed = command.trim();
                        if (!trimmed) return;

                        const activeSession = sessionRef.current;
                        if (!activeSession) {
                            throw new Error('Aucune session console active.');
                        }

                        // Vérifier que la session est prête
                        const isReady = activeSession.consoleUrl 
                            ? connectionState === 'open'
                            : (wsRef.current !== null && wsRef.current.readyState === WebSocket.OPEN);
                        
                        if (!isReady) {
                            throw new Error('Session non prête. Veuillez attendre que la connexion soit établie.');
                        }

                        // Si on a un WebSocket ouvert, l'utiliser
                        if (wsRef.current && wsRef.current.readyState === WebSocket.OPEN) {
                            const payload = trimmed.endsWith('\n') ? trimmed : `${trimmed}\n`;
                            wsRef.current.send(payload);
                            appendLog(`> ${trimmed}`);
                            return;
                        }

                        // Pour CML avec consoleUrl (iframe), les commandes sont envoyées via l'iframe
                        if (activeSession.consoleUrl) {
                            appendLog(`> ${trimmed}`);
                            return;
                        }

                        throw new Error('Connexion non disponible');
                    }}
                    commandCatalog={{
                        'Commandes de visualisation': {
                            'show': [
                                'running-config', 'startup-config', 'version', 'inventory', 'clock',
                                'ip', 'interface', 'vlan', 'cdp', 'lldp', 'arp', 'route', 'ospf',
                                'eigrp', 'bgp', 'access-lists', 'mac', 'spanning-tree', 'trunk',
                                'port-security', 'users', 'sessions', 'processes', 'memory', 'cpu',
                                'flash', 'boot', 'redundancy', 'standby', 'logging', 'snmp',
                            ],
                        },
                        'Commandes de configuration': {
                            'configure': ['terminal', 'memory', 'network'],
                            'interface': [
                                'gigabitethernet', 'fastethernet', 'ethernet', 'serial', 'loopback',
                                'tunnel', 'vlan', 'port-channel', 'tengigabitethernet',
                            ],
                            'ip': [
                                'address', 'route', 'ospf', 'eigrp', 'bgp', 'access-list', 'nat',
                                'dhcp', 'domain', 'name-server', 'default-gateway',
                            ],
                            'vlan': [],
                            'line': ['console', 'vty', 'aux'],
                            'hostname': [],
                            'banner': ['motd', 'login', 'exec'],
                            'username': [],
                            'clock': ['set', 'timezone'],
                            'logging': ['console', 'buffered', 'trap', 'facility'],
                            'snmp-server': ['community', 'location', 'contact', 'enable'],
                        },
                        'Commandes d\'interface': [
                            'ip address', 'ip access-group', 'shutdown', 'no shutdown',
                            'description', 'duplex', 'speed', 'switchport', 'switchport mode',
                            'switchport access vlan', 'switchport trunk', 'spanning-tree',
                            'port-security', 'cdp', 'lldp',
                        ],
                        'Commandes de ligne': [
                            'password', 'login', 'exec-timeout', 'logging synchronous',
                            'transport input', 'transport output', 'access-class',
                        ],
                        'Commandes système': {
                            'enable': ['password', 'secret'],
                            'disable': [],
                            'exit': [],
                            'logout': [],
                            'reload': [],
                            'copy': ['running-config', 'startup-config', 'flash', 'tftp', 'ftp'],
                            'write': ['memory', 'erase'],
                            'erase': ['startup-config', 'flash'],
                        },
                        'Commandes réseau': {
                            'ping': [],
                            'traceroute': [],
                            'telnet': [],
                            'ssh': [],
                        },
                        'Commandes de débogage': {
                            'debug': [],
                            'undebug': [],
                        },
                        'Commandes terminal': {
                            'terminal': ['length', 'width', 'monitor', 'no', 'history'],
                            'clear': ['line', 'counters', 'arp', 'mac', 'spanning-tree'],
                        },
                        'Commandes de négation': {
                            'no': ['shutdown'],
                        },
                    }}
                    labName={labTitle}
                />
            </CardContent>

            <CardFooter className="flex flex-col gap-2">
                {consoleMode === 'ios' ? (
                    <p className="text-xs text-muted-foreground">
                        Console IOS intelligente avec auto-complétion, historique et coloration syntaxique.
                    </p>
                ) : (
                <div className="flex w-full items-center gap-2">
                    <Input
                        placeholder="Entrer une commande (ex. show ip interface brief)"
                        value={command}
                        onChange={event => setCommand(event.target.value)}
                        onKeyDown={event => {
                            if (event.key === 'Enter' && !event.shiftKey) {
                                event.preventDefault();
                                handleSendCommand();
                            }
                        }}
                        disabled={!isSessionOpen}
                    />
                    <Button onClick={handleSendCommand} disabled={!isSessionOpen || !command.trim()}>
                        Envoyer
                    </Button>
                </div>
                )}

                {normalizedSessions.length > 0 && (
                    <div className="w-full text-xs text-muted-foreground">
                        Sessions actives détectées : {normalizedSessions.length}
                    </div>
                )}
            </CardFooter>
        </Card>
    );
}


