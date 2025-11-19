import { useState, useRef, useEffect, useCallback, useMemo } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import { useIOSAutocomplete } from '@/hooks/useIOSAutocomplete';
import { useConsoleStability } from '@/hooks/useConsoleStability';
import ConsoleTerminal from '@/components/ConsoleTerminal';
import { Terminal, Send, History, XCircle, Wifi, WifiOff, Activity, ChevronDown, BookOpen, Power, PowerOff } from 'lucide-react';
import { cn } from '@/lib/utils';
import { toast } from 'sonner';

interface IOSConsoleProps {
    onSendCommand: (command: string) => void;
    output: string[];
    isConnected: boolean;
    nodeLabel?: string;
    nodeState?: string;
    className?: string;
}

export default function IOSConsole({
    onSendCommand,
    output,
    isConnected,
    nodeLabel,
    nodeState,
    className = '',
}: IOSConsoleProps) {
    const [command, setCommand] = useState('');
    const [showSuggestions, setShowSuggestions] = useState(false);
    const [suggestionIndex, setSuggestionIndex] = useState(0);
    const [showHistory, setShowHistory] = useState(false);
    const [showCheatsheet, setShowCheatsheet] = useState(false);
    const inputRef = useRef<HTMLInputElement>(null);
    const suggestionsRef = useRef<HTMLDivElement>(null);

    // Hook de stabilité pour la gestion d'erreurs et reconnexion
    const stability = useConsoleStability({
        maxRetries: 3,
        retryDelay: 1000,
        onReconnect: () => {
            console.log('Reconnexion réussie');
        },
        onError: (error) => {
            console.error('Erreur console:', error);
        },
    });

    const {
        getSuggestions,
        autocomplete,
        addToHistory,
        getPreviousHistory,
        getNextHistory,
        resetHistoryIndex,
        commandHistory,
    } = useIOSAutocomplete();

    // Suggestions pour la commande actuelle
    const suggestions = useMemo(() => {
        if (!command.trim()) return [];
        return getSuggestions(command);
    }, [command, getSuggestions]);

    // Synchroniser l'état de connexion avec le hook de stabilité
    useEffect(() => {
        stability.setIsConnected(isConnected);
    }, [isConnected, stability]);

    // Gérer les touches du clavier
    const handleKeyDown = useCallback((e: React.KeyboardEvent<HTMLInputElement>) => {
        if (showSuggestions && suggestions.length > 0) {
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                setSuggestionIndex(prev => Math.min(suggestions.length - 1, prev + 1));
                return;
            }
            if (e.key === 'ArrowUp') {
                e.preventDefault();
                setSuggestionIndex(prev => Math.max(0, prev - 1));
                return;
            }
            if (e.key === 'Tab') {
                e.preventDefault();
                const completed = autocomplete(command);
                setCommand(completed);
                setShowSuggestions(false);
                return;
            }
            if (e.key === 'Enter' && suggestionIndex >= 0 && suggestionIndex < suggestions.length) {
                e.preventDefault();
                const selected = suggestions[suggestionIndex];
                const parts = command.trim().split(/\s+/);
                const prefix = parts.slice(0, -1).join(' ');
                const newCommand = prefix ? `${prefix} ${selected}` : selected;
                setCommand(newCommand);
                setShowSuggestions(false);
                return;
            }
        }

        // Historique avec flèches haut/bas
        if (e.key === 'ArrowUp' && !showSuggestions) {
            e.preventDefault();
            const prev = getPreviousHistory();
            if (prev !== null) {
                setCommand(prev);
            }
            return;
        }

        if (e.key === 'ArrowDown' && !showSuggestions) {
            e.preventDefault();
            const next = getNextHistory();
            if (next !== null) {
                setCommand(next);
            }
            return;
        }

        // Envoyer avec Enter
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            handleSend();
            return;
        }

        // Échap pour fermer les suggestions
        if (e.key === 'Escape') {
            setShowSuggestions(false);
            resetHistoryIndex();
            return;
        }
    }, [command, showSuggestions, suggestions, suggestionIndex, autocomplete, getPreviousHistory, getNextHistory, resetHistoryIndex]);

    const handleSend = useCallback(async () => {
        const trimmed = command.trim();
        if (!trimmed) return;

        addToHistory(trimmed);

        // Si pas connecté, ajouter à la queue
        if (!isConnected) {
            stability.queueCommand(trimmed);
            toast.info('Commande mise en queue (reconnexion en cours...)');
        } else {
            try {
                onSendCommand(trimmed);
            } catch (error) {
                stability.handleError(
                    error instanceof Error ? error : new Error('Erreur d\'envoi'),
                    async () => {
                        // Fonction de retry
                        onSendCommand(trimmed);
                    }
                );
            }
        }

        setCommand('');
        setShowSuggestions(false);
        resetHistoryIndex();
    }, [command, addToHistory, onSendCommand, resetHistoryIndex, isConnected, stability]);

    const handleInputChange = useCallback((e: React.ChangeEvent<HTMLInputElement>) => {
        const value = e.target.value;
        setCommand(value);
        setShowSuggestions(value.trim().length > 0 && suggestions.length > 0);
        setSuggestionIndex(0);
        resetHistoryIndex();
    }, [suggestions.length, resetHistoryIndex]);

    // Catalogue complet des commandes IOS
    const iosCommandsCatalog: Record<string, Record<string, string[]> | string[]> = {
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
    };

    const handleCommandClick = (cmd: string) => {
        setCommand(cmd);
        setShowCheatsheet(false);
        inputRef.current?.focus();
    };

    // Déterminer l'état du node
    const isNodeStarted = useMemo(() => {
        if (!nodeState) return null;
        const normalizedState = nodeState.toUpperCase();
        return normalizedState === 'BOOTED' || normalizedState === 'STARTED' || normalizedState === 'RUNNING';
    }, [nodeState]);

    const nodeStateDisplay = useMemo(() => {
        if (!nodeState) return null;
        const normalizedState = nodeState.toUpperCase();
        if (normalizedState === 'BOOTED' || normalizedState === 'STARTED' || normalizedState === 'RUNNING') {
            return { label: 'Démarré', variant: 'default' as const, icon: Power, color: 'text-green-400' };
        }
        if (normalizedState === 'STOPPED' || normalizedState === 'STOP') {
            return { label: 'Arrêté', variant: 'destructive' as const, icon: PowerOff, color: 'text-red-400' };
        }
        return { label: nodeState, variant: 'secondary' as const, icon: Activity, color: 'text-yellow-400' };
    }, [nodeState]);

    return (
        <Card className={`${className} flex flex-col h-full`}>
            <CardHeader className="pb-3">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-2">
                        <Terminal className="h-5 w-5" />
                        <CardTitle className="text-lg">Console IOS</CardTitle>
                        {nodeLabel && (
                            <Badge variant="outline" className="ml-2">
                                {nodeLabel}
                            </Badge>
                        )}
                        {nodeStateDisplay && (
                            <Badge
                                variant={nodeStateDisplay.variant}
                                className={cn(
                                    "ml-2 flex items-center gap-1",
                                    isNodeStarted && "bg-green-600",
                                    !isNodeStarted && nodeStateDisplay.variant === 'destructive' && "bg-red-600"
                                )}
                            >
                                <nodeStateDisplay.icon className="h-3 w-3" />
                                {nodeStateDisplay.label}
                            </Badge>
                        )}
                    </div>
                    <div className="flex items-center gap-2">
                        <Badge
                            variant={isConnected ? 'default' : 'secondary'}
                            className={cn(
                                isConnected && stability.connectionQuality === 'excellent' && 'bg-emerald-600',
                                isConnected && stability.connectionQuality === 'good' && 'bg-blue-600',
                                isConnected && stability.connectionQuality === 'poor' && 'bg-yellow-600',
                            )}
                        >
                            {isConnected ? (
                                <>
                                    {stability.connectionQuality === 'excellent' ? (
                                        <Wifi className="h-3 w-3 mr-1" />
                                    ) : stability.connectionQuality === 'good' ? (
                                        <Activity className="h-3 w-3 mr-1" />
                                    ) : (
                                        <WifiOff className="h-3 w-3 mr-1" />
                                    )}
                                    {stability.latency !== null && (
                                        <span className="mr-1">{Math.round(stability.latency)}ms</span>
                                    )}
                                    Connecté
                                </>
                            ) : (
                                <>
                                    <XCircle className="h-3 w-3 mr-1" />
                                    Déconnecté
                                </>
                            )}
                        </Badge>
                        {stability.retryCount > 0 && (
                            <Badge variant="outline" className="text-xs">
                                Reconnexion... ({stability.retryCount})
                            </Badge>
                        )}
                        {commandHistory.length > 0 && (
                            <Button
                                variant="ghost"
                                size="sm"
                                onClick={() => setShowHistory(!showHistory)}
                                className="h-7"
                            >
                                <History className="h-4 w-4 mr-1" />
                                Historique
                            </Button>
                        )}
                    </div>
                </div>
            </CardHeader>
            <CardContent className="flex-1 flex flex-col gap-2 p-4 min-h-0">
                {/* Zone de sortie avec terminal amélioré */}
                <div className="flex-1 min-h-0 overflow-hidden">
                <ConsoleTerminal
                    output={output}
                    showCursor={isConnected}
                    typingSpeed={0}
                    className="h-full"
                />
                </div>

                {/* Panneau d'historique */}
                {showHistory && commandHistory.length > 0 && (
                    <div className="bg-gray-900 rounded-lg p-2 max-h-32 overflow-y-auto flex-shrink-0">
                        <div className="text-xs text-gray-400 mb-1">Historique des commandes :</div>
                        <div className="space-y-1">
                            {commandHistory.slice().reverse().map((cmd, index) => (
                                <div
                                    key={index}
                                    className="text-xs font-mono text-gray-300 cursor-pointer hover:bg-gray-800 p-1 rounded"
                                    onClick={() => {
                                        setCommand(cmd);
                                        setShowHistory(false);
                                    }}
                                >
                                    {cmd}
                                </div>
                            ))}
                        </div>
                    </div>
                )}

                {/* Zone de saisie avec suggestions */}
                <div className="relative flex-shrink-0">
                    <div className="flex gap-2">
                        <div className="flex-1 relative">
                            <input
                                ref={inputRef}
                                type="text"
                                value={command}
                                onChange={handleInputChange}
                                onKeyDown={handleKeyDown}
                                onFocus={() => setShowSuggestions(command.trim().length > 0 && suggestions.length > 0)}
                                placeholder={isNodeStarted === false ? "Le node est arrêté. Démarrez-le pour envoyer des commandes..." : "Tapez une commande IOS (ex: show ip interface brief)..."}
                                className="w-full bg-gray-900 text-white font-mono px-4 py-2 rounded-lg border border-gray-700 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                                disabled={!isConnected || (isNodeStarted === false)}
                            />
                            {/* Suggestions */}
                            {showSuggestions && suggestions.length > 0 && (
                                <div
                                    ref={suggestionsRef}
                                    className="absolute bottom-full left-0 right-0 mb-2 bg-gray-800 border border-gray-700 rounded-lg shadow-lg max-h-48 overflow-y-auto z-10"
                                >
                                    {suggestions.map((suggestion, index) => (
                                        <div
                                            key={index}
                                            className={`px-4 py-2 cursor-pointer font-mono text-sm ${
                                                index === suggestionIndex
                                                    ? 'bg-blue-600 text-white'
                                                    : 'text-gray-300 hover:bg-gray-700'
                                            }`}
                                            onClick={() => {
                                                const parts = command.trim().split(/\s+/);
                                                const prefix = parts.slice(0, -1).join(' ');
                                                const newCommand = prefix ? `${prefix} ${suggestion}` : suggestion;
                                                setCommand(newCommand);
                                                setShowSuggestions(false);
                                            }}
                                        >
                                            {suggestion}
                                        </div>
                                    ))}
                                </div>
                            )}
                        </div>
                        <Button
                            onClick={handleSend}
                            disabled={!isConnected || !command.trim() || (isNodeStarted === false)}
                            className="px-6"
                            title={isNodeStarted === false ? 'Le node doit être démarré pour envoyer des commandes' : ''}
                        >
                            <Send className="h-4 w-4 mr-2" />
                            Envoyer
                        </Button>
                    </div>
                    {showSuggestions && suggestions.length > 0 && (
                        <div className="text-xs text-gray-500 mt-1">
                            Utilisez Tab pour compléter, ↑↓ pour naviguer, Entrée pour sélectionner
                        </div>
                    )}
                </div>

                {/* Cheatsheet - Catalogue des commandes */}
                <Collapsible open={showCheatsheet} onOpenChange={setShowCheatsheet} className="flex-shrink-0">
                    <CollapsibleTrigger asChild>
                        <Button
                            variant="outline"
                            size="sm"
                            className="w-full justify-between"
                        >
                            <div className="flex items-center gap-2">
                                <BookOpen className="h-4 w-4" />
                                <span>Cheatsheet - Catalogue des commandes IOS</span>
                            </div>
                            <ChevronDown className={cn(
                                "h-4 w-4 transition-transform duration-200",
                                showCheatsheet && "transform rotate-180"
                            )} />
                        </Button>
                    </CollapsibleTrigger>
                    <CollapsibleContent className="mt-2">
                        <div className="bg-gray-900 rounded-lg border border-gray-700 p-4 max-h-[300px] overflow-y-auto">
                            <div className="space-y-4">
                                {Object.entries(iosCommandsCatalog).map(([category, commands]) => {
                                    const isArray = Array.isArray(commands);
                                    return (
                                        <div key={category} className="border-b border-gray-700 pb-3 last:border-0">
                                            <h4 className="text-sm font-semibold text-blue-400 mb-2">{category}</h4>
                                            <div className="space-y-1">
                                                {isArray ? (
                                                    commands.map((cmd, idx) => (
                                                        <div
                                                            key={idx}
                                                            onClick={() => handleCommandClick(cmd)}
                                                            className="text-xs font-mono text-gray-300 hover:text-blue-400 hover:bg-gray-800 cursor-pointer px-2 py-1 rounded transition-colors"
                                                        >
                                                            {cmd}
                                                        </div>
                                                    ))
                                                ) : (
                                                    Object.entries(commands).map(([mainCmd, subCmds]) => (
                                                        <div key={mainCmd} className="mb-1">
                                                            <div
                                                                onClick={() => handleCommandClick(mainCmd)}
                                                                className="text-xs font-mono text-cyan-400 hover:text-cyan-300 hover:bg-gray-800 cursor-pointer px-2 py-1 rounded transition-colors font-semibold"
                                                            >
                                                                {mainCmd}
                                                            </div>
                                                            {Array.isArray(subCmds) && subCmds.length > 0 && (
                                                                <div className="ml-4 mt-1 space-y-0.5">
                                                                    {subCmds.map((subCmd, idx) => (
                                                                        <div
                                                                            key={idx}
                                                                            onClick={() => handleCommandClick(`${mainCmd} ${subCmd}`)}
                                                                            className="text-xs font-mono text-gray-400 hover:text-blue-400 hover:bg-gray-800 cursor-pointer px-2 py-0.5 rounded transition-colors"
                                                                        >
                                                                            {subCmd}
                                                                        </div>
                                                                    ))}
                                                                </div>
                                                            )}
                                                        </div>
                                                    ))
                                                )}
                                            </div>
                                        </div>
                                    );
                                })}
                            </div>
                        </div>
                    </CollapsibleContent>
                </Collapsible>
            </CardContent>
        </Card>
    );
}


