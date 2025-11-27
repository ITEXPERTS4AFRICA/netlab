import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { Card, CardContent, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import { toast } from 'sonner';
import { Loader2, Power, RefreshCcw, Terminal, Network, Link2, ChevronDown, Plug, PlugZap } from 'lucide-react';
import { useConsole } from '@/hooks/useConsole';
import { useIntelligentPolling } from '@/hooks/useIntelligentPolling';
import ConsoleTerminal from '@/components/ConsoleTerminal';
import { useActionLogs } from '@/contexts/ActionLogsContext';
import { useNodeInterfaces } from '@/hooks/useNodeInterfaces';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import HiddenConsoleIframe from '@/components/HiddenConsoleIframe';

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


type Props = {
    cmlLabId: string;
    nodes: LabNode[];
};

type SessionState = {
    sessionId: string;
    nodeId: string;
    consoleId?: string; // ID de la console pour récupérer les logs
    consoleUrl?: string; // URL de la console pour l'iframe caché
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

export default function LabConsolePanel({ cmlLabId, nodes }: Props) {

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
    const [consoles, setConsoles] = useState<ConsoleInfo[]>([]);
    const [loadingConsoles, setLoadingConsoles] = useState(false);
    const [loadingSession, setLoadingSession] = useState(false);
    const [session, setSession] = useState<SessionState | null>(null);
    const [connectionState, setConnectionState] = useState<ConnectionState>('idle');
    const [logLines, setLogLines] = useState<string[]>([]);
    const [error, setError] = useState<string | null>(null);
    const [selectedInterface, setSelectedInterface] = useState<string | null>(null);
    const [selectedLink, setSelectedLink] = useState<string | null>(null);
    const [showInterfaces, setShowInterfaces] = useState(false);
    const [showLinks, setShowLinks] = useState(false);
    const [pendingCommand, setPendingCommand] = useState<string | null>(null);
    const sessionRef = useRef<SessionState | null>(null);
    
    // Hook pour récupérer les interfaces et liens
    const { 
        interfaces, 
        links, 
        getNodeInterfaces, 
        getNodeLinks,
        connectInterface,
        disconnectInterface,
        connectLink,
        disconnectLink,
        loading: loadingInterfaces
    } = useNodeInterfaces();

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

    // Hook de polling intelligent pour récupérer les logs en temps réel
    // Intervalle augmenté à 4 secondes pour éviter les erreurs 429
    const polling = useIntelligentPolling({
        labId: cmlLabId,
        nodeId: selectedNodeId,
        consoleId: session?.consoleId || session?.sessionId || '',
        enabled: !!session, // Toujours activer si session existe
        interval: 4000, // Poll toutes les 4 secondes pour éviter le rate limiting
    });

    // Synchroniser les logs du polling avec logLines
    useEffect(() => {
        if (polling.logs.length > 0) {
            setLogLines(polling.logs);
        }
    }, [polling.logs]);

    // Mettre à jour selectedNodeId quand nodes change (seulement si selectedNodeId est vide)
    useEffect(() => {
        if (!selectedNodeId && firstValidNodeId && firstValidNodeId !== selectedNodeId) {
            setSelectedNodeId(firstValidNodeId);
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [firstValidNodeId]); // Ne pas inclure selectedNodeId pour éviter les boucles

    // Ouvrir automatiquement une session après sélection d'un node
    useEffect(() => {
        if (selectedNodeId && !session && !loadingSession && !loadingConsoles) {
            // Attendre un court délai pour que les consoles soient chargées
            const timer = setTimeout(() => {
                handleCreateSession();
            }, 500);
            return () => clearTimeout(timer);
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [selectedNodeId]); // Ne pas inclure session pour éviter les boucles

    // Charger les interfaces et liens quand un node est sélectionné
    useEffect(() => {
        if (selectedNodeId && cmlLabId) {
            getNodeInterfaces(cmlLabId, selectedNodeId);
            getNodeLinks(cmlLabId, selectedNodeId);
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [selectedNodeId, cmlLabId]);


    const appendLog = useCallback((line: string) => {
        setLogLines(prev => {
            const next = [...prev, line];
            if (next.length > MAX_LOG_LINES) {
                return next.slice(next.length - MAX_LOG_LINES);
            }
            return next;
        });
    }, []);



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
                } else if (!data && !controller.signal.aborted && isMounted) {
                    // Si data est null, c'est qu'il y a eu une erreur (gérée par useConsole)
                    setConsoles([]);
                }
            } catch (err) {
                if (controller.signal.aborted || !isMounted) return;
                console.error(err);
                setError("Impossible de charger les consoles pour ce nœud.");
                setConsoles([]);
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
            setSession(null);
            setConnectionState('closed');
            return;
        }

        if (reason) {
            appendLog(`[Console] ${reason}`);
        }

        setConnectionState('closing');

        if (!skipApi && closeSessionRef.current) {
            const success = await closeSessionRef.current(activeSession.sessionId);
            if (!success) {
                console.warn('Console session close failed');
            }
        }

        sessionRef.current = null;
        setSession(null);
        setConnectionState('closed');
    }, [appendLog]);

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
            setConnectionState('idle');
            return;
        }

        // Pour CML, on utilise uniquement le polling des logs
        // Pas de WebSocket, pas d'iframe visible
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
    }, [session, checkConnectionAvailability, connectionState]);

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

            if (!sessionId) {
                throw new Error('Réponse invalide du serveur (session_id manquant).');
            }

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

            // Récupérer l'URL de la console depuis la réponse
            const consoleUrl = (typeof data.console_url === 'string' ? data.console_url : null) 
                ?? (typeof data.url === 'string' ? data.url : null);

            const nextSession: SessionState = {
                sessionId,
                nodeId: data.node_id ?? selectedNodeId,
                consoleId: consoleId ?? sessionId,
                consoleUrl: consoleUrl ?? undefined,
                protocol: data.protocol,
                type: data.type ?? type,
            };

            sessionRef.current = nextSession;
            setSession(nextSession);
            setConnectionState('open');
            appendLog('[Console] Session console connectée avec succès.');
            const nodeLabel = (() => {
                const node = nodes.find(n => n.id === selectedNodeId);
                return node?.label || node?.name || selectedNodeId;
            })();
            appendLog(`[Console] Node: ${nodeLabel}`);

            // Mettre à jour le log d'action
            const lastLog = actionLogs.find(log => log.type === 'session' && log.status === 'pending' && log.nodeId === selectedNodeId);
            if (lastLog) {
                updateActionLogStatus(lastLog.id, 'success', `Session créée avec succès. ID: ${sessionId.slice(0, 8)}`);
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
    }, [appendLog, cmlLabId, closeSession, selectedNodeId, addActionLog, actionLogs, updateActionLogStatus, checkConnectionAvailability, consoles, nodes]);

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


    const connectionBadge = useMemo(() => CONNECTION_META[connectionState], [connectionState]);
    // Une session est "ouverte" si elle existe et que la connexion est ouverte ou en cours de connexion
    const isSessionOpen = useMemo(() => {
        return session !== null && (
            connectionState === 'open'
            || connectionState === 'connecting'
        );
    }, [session, connectionState]);

    const selectedNode = useMemo(() => nodes.find(node => node.id === selectedNodeId) ?? null, [nodes, selectedNodeId]);

    return (
        <Card className="flex h-full min-h-[32rem] flex-col border-0 shadow-sm">
            <CardHeader className="space-y-3 pb-3">
                {/* En-tête avec titre et statut */}
                <div className="flex items-center justify-between gap-3">
                    <CardTitle className="flex items-center gap-2 text-xl">
                        <Terminal className="h-6 w-6 text-primary" />
                        Console Réseau
                    </CardTitle>
                    <Badge
                        variant={connectionBadge.variant}
                        className={connectionBadge.className}
                    >
                        {connectionBadge.label}
                    </Badge>
                </div>

                {/* Sélection du node - Layout amélioré */}
                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:gap-3 flex-1">
                        {nodes.length === 0 ? (
                            <div className="text-sm text-muted-foreground">
                                Aucun nœud disponible. Le lab doit être démarré pour voir les nœuds.
                            </div>
                        ) : (
                            <div className="flex items-center gap-2 flex-1">
                                <label className="text-sm font-medium text-muted-foreground whitespace-nowrap">
                                    Nœud:
                                </label>
                                <Select
                                    value={selectedNodeId}
                                    onValueChange={value => {
                                        if (value && value !== selectedNodeId) {
                                            setSelectedNodeId(value);
                                        }
                                    }}
                                    disabled={loadingConsoles || loadingSession}
                                >
                                    <SelectTrigger className="w-full sm:w-[300px]">
                                        <SelectValue placeholder="Sélectionner un nœud" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {nodes.map(node => {
                                            const nodeId = node.id || '';
                                            if (!nodeId) return null;
                                            return (
                                                <SelectItem key={nodeId} value={nodeId}>
                                                    <div className="flex items-center justify-between w-full">
                                                        <span>{node.label ?? node.name ?? nodeId}</span>
                                                        {node.state && (
                                                            <Badge variant="secondary" className="ml-2 text-xs">
                                                                {node.state}
                                                            </Badge>
                                                        )}
                                                    </div>
                                                </SelectItem>
                                            );
                                        })}
                                    </SelectContent>
                                </Select>
                            </div>
                        )}

                        {/* Actions rapides */}
                        <div className="flex items-center gap-2">
                            {session && (
                                <Button
                                    variant="destructive"
                                    size="sm"
                                    onClick={handleCloseSession}
                                    className="gap-2"
                                >
                                    <Power className="h-4 w-4" />
                                    Fermer
                                </Button>
                            )}
                            <Button
                                variant="ghost"
                                size="sm"
                                onClick={() => {
                                    if (selectedNodeId) {
                                        setLoadingConsoles(true);
                                        setError(null);
                                        getNodeConsolesRef.current(cmlLabId, selectedNodeId)
                                            .then(data => {
                                                if (data) {
                                                    setConsoles(Array.isArray(data.consoles) ? data.consoles : []);
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
                                className="gap-2"
                            >
                                <RefreshCcw className={`h-4 w-4 ${loadingConsoles ? 'animate-spin' : ''}`} />
                            </Button>
                        </div>
                    </div>
                </div>

                {/* Informations du node sélectionné */}
                {selectedNode && (
                    <div className="flex flex-wrap items-center gap-2 text-xs">
                        {selectedNode.state && (
                            <Badge variant="secondary">{selectedNode.state}</Badge>
                        )}
                        {selectedNode.node_definition && (
                            <Badge variant="outline">{selectedNode.node_definition}</Badge>
                        )}
                    </div>
                )}

                {error && (
                    <div className="p-2 bg-destructive/10 border border-destructive/20 rounded text-sm text-destructive">
                        {error}
                    </div>
                )}

                {loadingConsoles && (
                    <div className="flex items-center gap-2 text-sm text-muted-foreground">
                        <Loader2 className="h-4 w-4 animate-spin" />
                        Chargement des consoles…
                    </div>
                )}
            </CardHeader>

            <CardContent className="flex flex-1 flex-col gap-4 overflow-hidden">

                <Separator />

                {/* Section Interfaces et Liens */}
                {(interfaces.length > 0 || links.length > 0) && (
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        {/* Interfaces */}
                        {interfaces.length > 0 && (
                            <Collapsible open={showInterfaces} onOpenChange={setShowInterfaces}>
                                <CollapsibleTrigger asChild>
                                    <Button variant="outline" className="w-full justify-between">
                                        <div className="flex items-center gap-2">
                                            <Network className="h-4 w-4" />
                                            <span>Interfaces ({interfaces.length})</span>
                                        </div>
                                        <ChevronDown className={`h-4 w-4 transition-transform ${showInterfaces ? 'rotate-180' : ''}`} />
                                    </Button>
                                </CollapsibleTrigger>
                                <CollapsibleContent className="mt-2">
                                    <div className="space-y-2 max-h-48 overflow-y-auto">
                                        <Select
                                            value={selectedInterface || ''}
                                            onValueChange={setSelectedInterface}
                                        >
                                            <SelectTrigger>
                                                <SelectValue placeholder="Sélectionner une interface" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {interfaces.map((iface) => (
                                                    <SelectItem key={iface.id} value={iface.id}>
                                                        <div className="flex items-center justify-between w-full">
                                                            <span>{iface.label || iface.id}</span>
                                                            <Badge variant={iface.is_connected ? 'default' : 'secondary'} className="ml-2">
                                                                {iface.is_connected ? 'Connectée' : 'Déconnectée'}
                                                            </Badge>
                                                        </div>
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        {selectedInterface && (
                                            <div className="space-y-2">
                                                <div className="p-3 bg-muted rounded-lg text-sm space-y-2">
                                                    {(() => {
                                                        const iface = interfaces.find(i => i.id === selectedInterface);
                                                        if (!iface) {
                                                            return (
                                                                <p className="text-muted-foreground">Interface non trouvée</p>
                                                            );
                                                        }
                                                        return (
                                                            <>
                                                                <div className="space-y-1">
                                                                    <p><strong>ID:</strong> <code className="text-xs">{iface.id}</code></p>
                                                                    <p><strong>Label:</strong> {iface.label || 'N/A'}</p>
                                                                    <p><strong>Type:</strong> {iface.type || 'N/A'}</p>
                                                                    <p><strong>État:</strong> 
                                                                        <Badge variant={iface.is_connected ? 'default' : 'secondary'} className="ml-2">
                                                                            {iface.state || (iface.is_connected ? 'Connectée' : 'Déconnectée')}
                                                                        </Badge>
                                                                    </p>
                                                                    {iface.mac_address && (
                                                                        <p><strong>MAC:</strong> <code className="text-xs">{iface.mac_address}</code></p>
                                                                    )}
                                                                    {iface.node && (
                                                                        <p><strong>Node:</strong> <code className="text-xs">{iface.node}</code></p>
                                                                    )}
                                                                </div>
                                                                {/* Debug info (dev only) */}
                                                                {process.env.NODE_ENV === 'development' && (
                                                                    <details className="mt-2 text-xs">
                                                                        <summary className="cursor-pointer text-muted-foreground">Debug (dev only)</summary>
                                                                        <pre className="mt-1 overflow-auto max-h-32 text-xs bg-background p-2 rounded border">
                                                                            {JSON.stringify(iface, null, 2)}
                                                                        </pre>
                                                                    </details>
                                                                )}
                                                            </>
                                                        );
                                                    })()}
                                                </div>
                                                <div className="flex gap-2">
                                                    <Button
                                                        size="sm"
                                                        variant={(() => {
                                                            const iface = interfaces.find(i => i.id === selectedInterface);
                                                            return iface?.is_connected ? 'destructive' : 'default';
                                                        })()}
                                                        onClick={async () => {
                                                            const iface = interfaces.find(i => i.id === selectedInterface);
                                                            if (!iface) {
                                                                console.error('❌ Interface non trouvée:', selectedInterface);
                                                                toast.error('Interface non trouvée');
                                                                return;
                                                            }
                                                            
                                                            console.log('🖱️ Clic sur bouton interface:', {
                                                                interfaceId: selectedInterface,
                                                                iface,
                                                                is_connected: iface.is_connected,
                                                            });
                                                            
                                                            const success = iface.is_connected
                                                                ? await disconnectInterface(cmlLabId, selectedInterface)
                                                                : await connectInterface(cmlLabId, selectedInterface);
                                                            
                                                            if (success) {
                                                                // Rafraîchir les interfaces après l'action
                                                                setTimeout(() => {
                                                                    void getNodeInterfaces(cmlLabId, selectedNodeId);
                                                                }, 1500);
                                                            }
                                                        }}
                                                        disabled={loadingInterfaces}
                                                        className="flex-1 gap-2"
                                                    >
                                                        {loadingInterfaces ? (
                                                            <Loader2 className="h-4 w-4 animate-spin" />
                                                        ) : (
                                                            <>
                                                                {(() => {
                                                                    const iface = interfaces.find(i => i.id === selectedInterface);
                                                                    return iface?.is_connected ? (
                                                                        <>
                                                                            <PlugZap className="h-4 w-4" />
                                                                            Déconnecter
                                                                        </>
                                                                    ) : (
                                                                        <>
                                                                            <Plug className="h-4 w-4" />
                                                                            Connecter
                                                                        </>
                                                                    );
                                                                })()}
                                                            </>
                                                        )}
                                                    </Button>
                                                </div>
                                            </div>
                                        )}
                                    </div>
                                </CollapsibleContent>
                            </Collapsible>
                        )}

                        {/* Liens */}
                        {links.length > 0 && (
                            <Collapsible open={showLinks} onOpenChange={setShowLinks}>
                                <CollapsibleTrigger asChild>
                                    <Button variant="outline" className="w-full justify-between">
                                        <div className="flex items-center gap-2">
                                            <Link2 className="h-4 w-4" />
                                            <span>Liens ({links.length})</span>
                                        </div>
                                        <ChevronDown className={`h-4 w-4 transition-transform ${showLinks ? 'rotate-180' : ''}`} />
                                    </Button>
                                </CollapsibleTrigger>
                                <CollapsibleContent className="mt-2">
                                    <div className="space-y-2 max-h-48 overflow-y-auto">
                                        <Select
                                            value={selectedLink || ''}
                                            onValueChange={setSelectedLink}
                                        >
                                            <SelectTrigger>
                                                <SelectValue placeholder="Sélectionner un lien" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {links.map((link) => {
                                                    const nodeA = link.node_a || link.n1;
                                                    const nodeB = link.node_b || link.n2;
                                                    const isConnectedToSelected = nodeA === selectedNodeId || nodeB === selectedNodeId;
                                                    return (
                                                        <SelectItem key={link.id} value={link.id}>
                                                            <div className="flex items-center justify-between w-full">
                                                                <span>
                                                                    {link.interface1?.label || link.i1 || 'Interface 1'} ↔ {link.interface2?.label || link.i2 || 'Interface 2'}
                                                                </span>
                                                                <Badge variant={isConnectedToSelected ? 'default' : 'secondary'} className="ml-2">
                                                                    {link.state || 'N/A'}
                                                                </Badge>
                                                            </div>
                                                        </SelectItem>
                                                    );
                                                })}
                                            </SelectContent>
                                        </Select>
                                        {selectedLink && (
                                            <div className="space-y-2">
                                                <div className="p-3 bg-muted rounded-lg text-sm space-y-1">
                                                    {(() => {
                                                        const link = links.find(l => l.id === selectedLink);
                                                        if (!link) return null;
                                                        return (
                                                            <>
                                                                <p><strong>État:</strong> {link.state || 'N/A'}</p>
                                                                {link.interface1 && (
                                                                    <p><strong>Interface 1:</strong> {link.interface1.label || link.interface1.id}</p>
                                                                )}
                                                                {link.interface2 && (
                                                                    <p><strong>Interface 2:</strong> {link.interface2.label || link.interface2.id}</p>
                                                                )}
                                                            </>
                                                        );
                                                    })()}
                                                </div>
                                                <div className="flex gap-2">
                                                    <Button
                                                        size="sm"
                                                        variant={(() => {
                                                            const link = links.find(l => l.id === selectedLink);
                                                            const isActive = link?.state === 'STARTED' || link?.state === 'started' || link?.state === 'up';
                                                            return isActive ? 'destructive' : 'default';
                                                        })()}
                                                        onClick={async () => {
                                                            const link = links.find(l => l.id === selectedLink);
                                                            if (!link) return;
                                                            
                                                            const isActive = link.state === 'STARTED' || link.state === 'started' || link.state === 'up';
                                                            
                                                            const success = isActive
                                                                ? await disconnectLink(cmlLabId, selectedLink)
                                                                : await connectLink(cmlLabId, selectedLink);
                                                            
                                                            if (success) {
                                                                // Rafraîchir les liens après l'action
                                                                setTimeout(() => {
                                                                    void getNodeLinks(cmlLabId, selectedNodeId);
                                                                }, 1000);
                                                            }
                                                        }}
                                                        disabled={loadingInterfaces}
                                                        className="flex-1 gap-2"
                                                    >
                                                        {loadingInterfaces ? (
                                                            <Loader2 className="h-4 w-4 animate-spin" />
                                                        ) : (
                                                            <>
                                                                {(() => {
                                                                    const link = links.find(l => l.id === selectedLink);
                                                                    const isActive = link?.state === 'STARTED' || link?.state === 'started' || link?.state === 'up';
                                                                    return isActive ? (
                                                                        <>
                                                                            <PlugZap className="h-4 w-4" />
                                                                            Déconnecter
                                                                        </>
                                                                    ) : (
                                                                        <>
                                                                            <Plug className="h-4 w-4" />
                                                                            Connecter
                                                                        </>
                                                                    );
                                                                })()}
                                                            </>
                                                        )}
                                                    </Button>
                                                </div>
                                            </div>
                                        )}
                                    </div>
                                </CollapsibleContent>
                            </Collapsible>
                        )}
                    </div>
                )}

                <Separator />

                {/* Section console */}
                <div className="flex-1 flex flex-col min-h-[400px]">
                    <ConsoleTerminal
                        output={logLines}
                        showCursor={Boolean(isSessionOpen)}
                        className="flex-1"
                    />
                    {isSessionOpen && (
                        <div className="mt-2 flex gap-2">
                            <input
                                type="text"
                                className="flex-1 rounded border border-input bg-background px-3 py-2 text-sm font-mono"
                                placeholder="Tapez votre commande..."
                                onKeyDown={(e) => {
                                    if (e.key === 'Enter') {
                                        const cmd = e.currentTarget.value.trim();
                                        if (!cmd) return;

                                        const actionLogId = `${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;
                                        addActionLog({
                                            type: 'command',
                                            action: 'Envoi de commande',
                                            command: cmd,
                                            status: 'pending',
                                            nodeId: sessionRef.current?.nodeId,
                                            details: `Commande: ${cmd}`,
                                        });

                                        const activeSession = sessionRef.current;
                                        if (!activeSession) {
                                            toast.error('Aucune session console active.');
                                            return;
                                        }

                                        if (activeSession.sessionId) {
                                            appendLog(`> ${cmd}`);
                                            updateActionLogStatus(actionLogId, 'sent', `Commande envoyée: ${cmd}`);

                                            // Envoyer la commande via l'iframe caché si disponible
                                            if (activeSession.consoleUrl) {
                                                setPendingCommand(cmd);
                                            }

                                            const consoleId = activeSession.consoleId ?? activeSession.sessionId;
                                            
                                            if (consoleId) {
                                                // Polling amélioré : commencer immédiatement et continuer plusieurs fois
                                                let pollCount = 0;
                                                const maxPolls = 8; // Poller 8 fois (32 secondes au total avec intervalle de 4s)
                                                const initialLogCount = logLines.length;
                                                let pollInterval = 4000; // Intervalle initial de 4 secondes
                                                
                                                const pollLogs = async () => {
                                                    try {
                                                        const previousLogCount = logLines.length;
                                                        await handleLoadLog(consoleId, true);
                                                        pollCount++;
                                                        
                                                        // Vérifier si de nouveaux logs sont apparus
                                                        const currentLogCount = logLines.length;
                                                        const hasNewLogs = currentLogCount > previousLogCount;
                                                        
                                                        if (hasNewLogs && pollCount >= 2) {
                                                            // Si on a de nouveaux logs après au moins 2 polls, considérer que la commande a été exécutée
                                                            updateActionLogStatus(actionLogId, 'success', `Commande exécutée: ${cmd}`);
                                                        } else if (pollCount < maxPolls) {
                                                            // Continuer à poller avec intervalle adaptatif
                                                            setTimeout(pollLogs, pollInterval);
                                                        } else {
                                                            // Après maxPolls, considérer terminé (avec ou sans nouveaux logs)
                                                            updateActionLogStatus(actionLogId, 'success', `Polling terminé pour: ${cmd}`);
                                                        }
                                                    } catch (err) {
                                                        console.error('Erreur lors de la récupération du log:', err);
                                                        const errorMessage = err instanceof Error ? err.message : 'Erreur inconnue';
                                                        
                                                        // Si erreur 429, augmenter l'intervalle
                                                        if (errorMessage.includes('429') || errorMessage.includes('Too Many Requests')) {
                                                            pollInterval = Math.min(pollInterval * 2, 10000); // Maximum 10 secondes
                                                            console.warn('Rate limit détecté, intervalle augmenté à', pollInterval, 'ms');
                                                        }
                                                        
                                                        if (pollCount === 0) {
                                                            // Seulement mettre à jour le statut si c'est la première tentative
                                                            updateActionLogStatus(actionLogId, 'error', `Erreur lors de la récupération des résultats: ${errorMessage}`);
                                                        } else if (pollCount < maxPolls) {
                                                            // Continuer à poller même en cas d'erreur
                                                            setTimeout(pollLogs, pollInterval);
                                                        }
                                                    }
                                                };
                                                
                                                // Commencer le polling après un délai pour éviter les requêtes simultanées
                                                setTimeout(pollLogs, 1000);
                                            }
                                            
                                            toast.success(`Commande envoyée: ${cmd}`, {
                                                description: session.consoleUrl 
                                                    ? 'Si la commande ne fonctionne pas, ouvrez la console CML dans un nouvel onglet.'
                                                    : 'Les résultats apparaîtront dans la console.',
                                            });
                                            e.currentTarget.value = '';
                                            return;
                                        }

                                        toast.error('Aucune session console active.');
                                        updateActionLogStatus(actionLogId, 'error', 'Session non disponible');
                                    }
                                }}
                            />
                        </div>
                    )}
                </div>
            </CardContent>

            <CardFooter className="flex flex-col gap-2">
                <div className="flex items-center justify-between w-full">
                    <p className="text-xs text-muted-foreground">
                        Console réseau avec affichage en temps réel des logs.
                    </p>
                    {session?.consoleUrl && (
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() => {
                                window.open(session.consoleUrl, '_blank', 'width=800,height=600');
                                toast.info('Console ouverte dans un nouvel onglet. Tapez vos commandes directement dans la console.');
                            }}
                            className="text-xs"
                        >
                            <Terminal className="h-3 w-3 mr-1" />
                            Ouvrir console CML
                        </Button>
                    )}
                </div>
                {session && (
                    <div className="w-full text-xs text-muted-foreground">
                        Session active pour: {selectedNode?.label || selectedNode?.name || selectedNodeId}
                        {session.consoleUrl && (
                            <span className="ml-2 text-yellow-600 dark:text-yellow-400">
                                (Les commandes peuvent nécessiter l'ouverture de la console CML)
                            </span>
                        )}
                    </div>
                )}
            </CardFooter>

            {/* Iframe caché pour envoyer les commandes à CML */}
            {session?.consoleUrl && (
                <HiddenConsoleIframe
                    consoleUrl={session.consoleUrl}
                    command={pendingCommand}
                    onCommandSent={() => {
                        setPendingCommand(null);
                    }}
                    enabled={isSessionOpen}
                />
            )}
        </Card >
    );
}


