import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { Card, CardContent, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import { toast } from 'sonner';
import { Loader2, Power, RefreshCcw, Terminal } from 'lucide-react';

type LabNode = {
    id: string;
    label?: string;
    name?: string;
    state?: string;
    node_definition?: string;
};

type ConsoleInfo = {
    id: string;
    console_id?: string;
    console_type?: string;
    protocol?: string;
    [key: string]: unknown;
};

type ConsoleSessionsResponse = {
    sessions?: ConsoleSession[];
    [key: string]: unknown;
};

type ConsoleSession = {
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
    wsHref?: string;
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

const getCsrfToken = () => {
    const element = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]');
    return element?.content;
};

export default function LabConsolePanel({ cmlLabId, nodes, initialSessions }: Props) {
    const [selectedNodeId, setSelectedNodeId] = useState<string>(() => nodes?.[0]?.id ?? '');
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
        if (!selectedNodeId) return;

        const controller = new AbortController();
        const fetchConsoles = async () => {
            setLoadingConsoles(true);
            setError(null);
            try {
                const res = await fetch(`/api/labs/${cmlLabId}/nodes/${selectedNodeId}/consoles`, {
                    method: 'GET',
                    headers: {
                        Accept: 'application/json',
                    },
                    credentials: 'same-origin',
                    signal: controller.signal,
                });

                if (!res.ok) {
                    throw new Error(`Erreur ${res.status}`);
                }

                const data = await res.json();
                setConsoles(Array.isArray(data?.consoles) ? data.consoles : []);
                setAvailableTypes(typeof data?.available_types === 'object' ? data.available_types : {});
            } catch (err) {
                if (controller.signal.aborted) return;
                console.error(err);
                setError("Impossible de charger les consoles pour ce nœud.");
                setConsoles([]);
                setAvailableTypes({});
            } finally {
                if (!controller.signal.aborted) {
                    setLoadingConsoles(false);
                }
            }
        };

        fetchConsoles();

        return () => {
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

        if (!skipApi) {
            const csrf = getCsrfToken();
            if (csrf) {
                try {
                    const res = await fetch(`/api/console/sessions/${activeSession.sessionId}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': csrf,
                            Accept: 'application/json',
                        },
                        credentials: 'same-origin',
                    });

                    if (!res.ok) {
                        console.warn('Console session close returned', res.status);
                    }
                } catch (err) {
                    console.error('Unable to close console session', err);
                }
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

    useEffect(() => {
        const activeSession = session;

        if (!activeSession) {
            teardownWebsocket({ silent: true });
            setConnectionState('idle');
            return;
        }

        if (!activeSession.wsHref) {
            teardownWebsocket({ silent: true });
            setConnectionState('open');
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
            appendLog(`[Console] Session ${activeSession.sessionId} connectée.`);
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
            setError('Erreur de connexion WebSocket.');
            appendLog('[Console] Erreur de transport');
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

        const csrf = getCsrfToken();
        if (!csrf) {
            toast.error('Jeton CSRF introuvable.');
            return;
        }

        setLoadingSession(true);
        setError(null);
        await closeSession({ reason: 'Fermeture de la session existante' });
        setLogLines([`[Console] Connexion en cours pour le nœud ${selectedNodeId}`]);

        try {
            const res = await fetch('/api/console/sessions', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    Accept: 'application/json',
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    lab_id: cmlLabId,
                    node_id: selectedNodeId,
                    type,
                }),
            });

            if (!res.ok) {
                const body = await res.json().catch(() => ({}));
                throw new Error(body?.error ?? `Erreur ${res.status}`);
            }

            const data = await res.json();
            const sessionId = data.session_id ?? data.id ?? '';
            let wsHref = data.ws_href ?? data.ws ?? data.ws_url ?? null;

            if (!sessionId) {
                throw new Error('Réponse invalide du serveur (session_id manquant).');
            }

            if (typeof wsHref === 'string') {
                try {
                    const url = new URL(wsHref, window.location.origin);
                    if (url.protocol !== 'ws:' && url.protocol !== 'wss:') {
                        url.protocol = window.location.protocol === 'https:' ? 'wss:' : 'ws:';
                    }
                    wsHref = url.toString();
                } catch (err) {
                    console.warn('URL WebSocket invalide fournie par l’API', err);
                }
            }

            const nextSession: SessionState = {
                sessionId,
                nodeId: data.node_id ?? selectedNodeId,
                wsHref: typeof wsHref === 'string' ? wsHref : undefined,
                protocol: data.protocol,
                type: data.type ?? type,
            };

            sessionRef.current = nextSession;
            setSession(nextSession);
            setConnectionState(nextSession.wsHref ? 'connecting' : 'open');
            appendLog('[Console] Session console créée.');
            toast.success('Session console créée.');
        } catch (err) {
            console.error(err);
            setError(err instanceof Error ? err.message : 'Impossible de créer la session.');
            setConnectionState('error');
            toast.error('Création de session impossible.');
        } finally {
            setLoadingSession(false);
        }
    }, [appendLog, cmlLabId, closeSession, selectedNodeId]);

    const handleCloseSession = useCallback(() => {
        void closeSession({ reason: 'Session fermée manuellement' });
    }, [closeSession]);

    const handleSendCommand = useCallback(() => {
        if (!wsRef.current || wsRef.current.readyState !== WebSocket.OPEN) {
            toast.error('Connexion console non disponible.');
            setConnectionState('error');
            return;
        }

        const trimmed = command.trimEnd();
        if (!trimmed) return;

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
    }, [appendLog, command]);

    const handleLoadLog = useCallback(async (consoleId: string) => {
        if (!consoleId) return;

        appendLog('[Console] Récupération du journal…');
        try {
            const res = await fetch(`/api/labs/${cmlLabId}/nodes/${selectedNodeId}/consoles/${consoleId}/log`, {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            });

            if (!res.ok) {
                throw new Error(`Erreur ${res.status}`);
            }

            const data = await res.json();

            if (Array.isArray(data?.log)) {
                data.log.forEach((line: string) => appendLog(line));
            } else if (typeof data === 'string') {
                data.split(/\r?\n/).forEach(appendLog);
            } else {
                appendLog(JSON.stringify(data, null, 2));
            }
        } catch (err) {
            console.error(err);
            toast.error('Impossible de récupérer le journal de console.');
        }
    }, [appendLog, cmlLabId, selectedNodeId]);

    const connectionBadge = useMemo(() => CONNECTION_META[connectionState], [connectionState]);
    const isSessionOpen = connectionState === 'open';

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
                    <Select
                        value={selectedNodeId}
                        onValueChange={value => setSelectedNodeId(value)}
                        disabled={loadingConsoles || loadingSession}
                    >
                        <SelectTrigger>
                            <SelectValue placeholder="Sélectionner un nœud" />
                        </SelectTrigger>
                        <SelectContent>
                            {nodes.map(node => (
                                <SelectItem key={node.id} value={node.id}>
                                    {node.label ?? node.name ?? node.id}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>

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
                        disabled={loadingSession || connectionState === 'connecting'}
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
                        onClick={() => setSelectedNodeId(value => value)}
                        disabled={loadingConsoles}
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

                <div className="flex-1 overflow-y-auto rounded-lg border border-muted bg-black/95 p-3 font-mono text-xs text-emerald-200 dark:bg-black">
                    {logLines.length > 0 ? (
                        logLines.map((line, index) => (
                            <pre key={index} className="whitespace-pre-wrap">
                                {line}
                            </pre>
                        ))
                    ) : (
                        <p className="text-muted-foreground">La sortie console apparaîtra ici.</p>
                    )}
                </div>
            </CardContent>

            <CardFooter className="flex flex-col gap-2">
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

                {normalizedSessions.length > 0 && (
                    <div className="w-full text-xs text-muted-foreground">
                        Sessions actives détectées : {normalizedSessions.length}
                    </div>
                )}
            </CardFooter>
        </Card>
    );
}


