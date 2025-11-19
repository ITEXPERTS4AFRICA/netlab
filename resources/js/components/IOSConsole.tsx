import { useState, useRef, useEffect, useCallback, useMemo } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { useIOSAutocomplete } from '@/hooks/useIOSAutocomplete';
import { useConsoleStability } from '@/hooks/useConsoleStability';
import ConsoleTerminal from '@/components/ConsoleTerminal';
import { Terminal, Send, History, X, CheckCircle, XCircle, Wifi, WifiOff, Activity, Settings } from 'lucide-react';
import { cn } from '@/lib/utils';
import { toast } from 'sonner';

interface IOSConsoleProps {
    onSendCommand: (command: string) => void;
    output: string[];
    isConnected: boolean;
    nodeLabel?: string;
    className?: string;
}

export default function IOSConsole({
    onSendCommand,
    output,
    isConnected,
    nodeLabel,
    className = '',
}: IOSConsoleProps) {
    const [command, setCommand] = useState('');
    const [showSuggestions, setShowSuggestions] = useState(false);
    const [suggestionIndex, setSuggestionIndex] = useState(0);
    const [showHistory, setShowHistory] = useState(false);
    const [typingSpeed, setTypingSpeed] = useState(0); // 0 = pas d'animation
    const inputRef = useRef<HTMLInputElement>(null);
    const outputRef = useRef<HTMLDivElement>(null);
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

    // Coloration syntaxique pour la sortie
    const formatOutputLine = (line: string): JSX.Element => {
        // Commandes
        if (line.startsWith('> ')) {
            return <span className="text-blue-400 font-mono">{line}</span>;
        }

        // Erreurs
        if (line.toLowerCase().includes('error') || line.toLowerCase().includes('invalid') || line.includes('%')) {
            return <span className="text-red-400 font-mono">{line}</span>;
        }

        // Succès
        if (line.toLowerCase().includes('success') || line.toLowerCase().includes('ok')) {
            return <span className="text-green-400 font-mono">{line}</span>;
        }

        // Adresses IP
        const ipRegex = /\b(\d{1,3}\.){3}\d{1,3}\b/g;
        if (ipRegex.test(line)) {
            const parts = line.split(ipRegex);
            const matches = line.match(ipRegex) || [];
            return (
                <span className="font-mono">
                    {parts.map((part, i) => (
                        <span key={i}>
                            {part}
                            {matches[i] && <span className="text-cyan-400">{matches[i]}</span>}
                        </span>
                    ))}
                </span>
            );
        }

        // Interfaces
        if (line.match(/\b(GigabitEthernet|FastEthernet|Ethernet|Serial|Loopback|Vlan)\d+/i)) {
            return <span className="text-yellow-400 font-mono">{line}</span>;
        }

        return <span className="font-mono text-gray-300">{line}</span>;
    };

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
            <CardContent className="flex-1 flex flex-col gap-2 p-4">
                {/* Zone de sortie avec terminal amélioré */}
                <ConsoleTerminal
                    output={output}
                    showCursor={isConnected}
                    typingSpeed={typingSpeed}
                    className="min-h-[300px] max-h-[500px]"
                />

                {/* Panneau d'historique */}
                {showHistory && commandHistory.length > 0 && (
                    <div className="bg-gray-900 rounded-lg p-2 max-h-32 overflow-y-auto">
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
                <div className="relative">
                    <div className="flex gap-2">
                        <div className="flex-1 relative">
                            <input
                                ref={inputRef}
                                type="text"
                                value={command}
                                onChange={handleInputChange}
                                onKeyDown={handleKeyDown}
                                onFocus={() => setShowSuggestions(command.trim().length > 0 && suggestions.length > 0)}
                                placeholder="Tapez une commande IOS (ex: show ip interface brief)..."
                                className="w-full bg-gray-900 text-white font-mono px-4 py-2 rounded-lg border border-gray-700 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                                disabled={!isConnected}
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
                            disabled={!isConnected || !command.trim()}
                            className="px-6"
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
            </CardContent>
        </Card>
    );
}


