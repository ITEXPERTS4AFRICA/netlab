import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { Card, CardContent, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import { toast } from 'sonner';
import { Loader2, Power, RefreshCcw, Terminal } from 'lucide-react';
import { useConsole, type ConsoleResponse, type ConsoleSessionResponse } from '@/hooks/useConsole';

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
};

type SessionState = {
    sessionId: string;
    nodeId: string;
    consoleUrl?: string; // URL de la console CML (iframe)
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

export default function LabConsolePanel({ cmlLabId, nodes, initialSessions }: Props) {
    // Trouver le premier node valide avec un id
    const getFirstValidNodeId = useCallback(() => {
        if (!nodes || nodes.length === 0) return '';
        const firstNode = nodes.find(node => node.id && node.id.trim() !== '');
        return firstNode?.id ?? '';
    }, [nodes]);

    const [selectedNodeId, setSelectedNodeId] = useState<string>(getFirstValidNodeId);
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
    
    // Utiliser le hook useConsole pour les appels API typés
    const consoleApi = useConsole();
    const getNodeConsolesFn = useCallback(
        (labId: string, nodeId: string) => consoleApi.getNodeConsoles(labId, nodeId),
        [consoleApi]
    );

    // Mettre à jour selectedNodeId quand nodes change
    useEffect(() => {
        if (!selectedNodeId && nodes && nodes.length > 0) {
            const firstValid = getFirstValidNodeId();
            if (firstValid && firstValid !== selectedNodeId) {
                setSelectedNodeId(firstValid);
            }
        }
    }, [nodes, selectedNodeId, getFirstValidNodeId]);

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
                const data = await getNodeConsolesFn(cmlLabId, selectedNodeId);
                if (data && !controller.signal.aborted && isMounted) {
                    setConsoles(Array.isArray(data.consoles) ? data.consoles : []);
                    setAvailableTypes(data.available_types || {});
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
    }, [cmlLabId, selectedNodeId, getNodeConsolesFn]);

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

        if (!skipApi && consoleApi) {
            const success = await consoleApi.closeSession(activeSession.sessionId);
            if (!success) {
                console.warn('Console session close failed');
            }
        }

        sessionRef.current = null;
        setSession(null);
        setConnectionState('closed');
    }, [appendLog, teardownWebsocket, consoleApi]);

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
            appendLog('[Console] Console disponible via iframe.');
            return;
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
    }, [session, appendLog, teardownWebsocket]);

    const handleCreateSession = useCallback(async (type?: string) => {
        if (!selectedNodeId) {
            toast.error('Sélectionnez un nœud avant de lancer une console.');
            return;
        }

        setLoadingSession(true);
        setError(null);
        await closeSession({ reason: 'Fermeture de la session existante' });
        setLogLines([`[Console] Connexion en cours pour le nœud ${selectedNodeId}`]);

        try {
            const data = await consoleApi.createSession({
                lab_id: cmlLabId,
                node_id: selectedNodeId,
                type,
            });

            if (!data) {
                throw new Error('Impossible de créer la session. Aucune réponse du serveur.');
            }
            
            // Vérifier si la réponse contient une erreur
            if ('error' in data && data.error) {
                throw new Error(data.error);
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

            const nextSession: SessionState = {
                sessionId,
                nodeId: data.node_id ?? selectedNodeId,
                consoleUrl: typeof consoleUrl === 'string' ? consoleUrl : undefined,
                wsHref: undefined, // CML n'utilise pas de WebSocket
                protocol: data.protocol,
                type: data.type ?? type,
            };

            sessionRef.current = nextSession;
            setSession(nextSession);
            setConnectionState('open'); // CML utilise des iframes, pas de connexion WebSocket
            appendLog('[Console] Session console créée.');
            appendLog(`[Console] URL: ${consoleUrl}`);
            toast.success('Session console créée.');
        } catch (err) {
            console.error('Erreur lors de la création de session:', err);
            const errorMessage = err instanceof Error ? err.message : 'Impossible de créer la session.';
            setError(errorMessage);
            setConnectionState('error');
            
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
    }, [appendLog, cmlLabId, closeSession, selectedNodeId, consoleApi]);

    const handleCloseSession = useCallback(() => {
        void closeSession({ reason: 'Session fermée manuellement' });
    }, [closeSession]);

    const handleSendCommand = useCallback(async () => {
        const trimmed = command.trimEnd();
        if (!trimmed) return;

        const activeSession = sessionRef.current;
        if (!activeSession) {
            toast.error('Aucune session console active.');
            return;
        }

        // Si on a un WebSocket ouvert, l'utiliser
        if (wsRef.current && wsRef.current.readyState === WebSocket.OPEN) {
            const payload = trimmed.endsWith('\n') ? trimmed : `${trimmed}\n`;
            try {
                wsRef.current.send(payload);
                appendLog(`> ${trimmed}`);
                setCommand('');
            } catch (err) {
                console.error(err);
                toast.error('Échec d\'envoi de la commande.');
                setConnectionState('error');
            }
            return;
        }

        // Sinon, essayer d'envoyer via l'API REST si disponible
        // Pour l'instant, on affiche juste un message d'erreur
        toast.error('Connexion WebSocket non disponible. Veuillez attendre que la connexion soit établie.');
        appendLog(`> ${trimmed} (en attente de connexion...)`);
        setCommand('');
    }, [appendLog, command]);

    const handleLoadLog = useCallback(async (consoleId: string) => {
        if (!consoleId) return;

        appendLog('[Console] Récupération du journal…');
        try {
            const data = await consoleApi.getConsoleLog(cmlLabId, selectedNodeId, consoleId);

            if (!data) {
                throw new Error('Impossible de récupérer le log');
            }

            if (Array.isArray(data.log)) {
                data.log.forEach((line: string) => appendLog(line));
            } else if (typeof data.log === 'string') {
                data.log.split(/\r?\n/).forEach(appendLog);
            } else {
                const logStr = typeof data === 'string' ? data : JSON.stringify(data, null, 2);
                appendLog(logStr);
            }
        } catch (err) {
            console.error(err);
            toast.error('Impossible de récupérer le journal de console.');
        }
    }, [appendLog, cmlLabId, selectedNodeId, consoleApi]);

    const connectionBadge = useMemo(() => CONNECTION_META[connectionState], [connectionState]);
    // Une session est "ouverte" si elle existe et que la connexion est ouverte ou en cours de connexion
    // ou si la session existe sans WebSocket (mode sans WebSocket)
    const isSessionOpen = session !== null && (connectionState === 'open' || connectionState === 'connecting' || (session.consoleUrl && connectionState !== 'error'));

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
                                consoleApi.getNodeConsoles(cmlLabId, selectedNodeId)
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

                <div className="flex-1 overflow-hidden rounded-lg border border-muted bg-black/95 dark:bg-black">
                    {session?.consoleUrl ? (
                        <iframe
                            src={session.consoleUrl}
                            className="h-full w-full border-0"
                            title="Console CML"
                            allow="clipboard-read; clipboard-write"
                        />
                    ) : logLines.length > 0 ? (
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
            </CardContent>

            <CardFooter className="flex flex-col gap-2">
                {session?.consoleUrl ? (
                    <p className="text-xs text-muted-foreground">
                        Utilisez la console ci-dessus pour entrer vos commandes directement.
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


