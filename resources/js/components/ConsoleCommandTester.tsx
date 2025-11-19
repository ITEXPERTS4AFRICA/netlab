import { useState, useCallback } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Progress } from '@/components/ui/progress';
import { 
    Play, 
    Square, 
    CheckCircle2, 
    XCircle, 
    Clock, 
    Loader2,
    BarChart3,
    AlertCircle
} from 'lucide-react';
import { useConsoleCommandTester, type TestSuite, type TestResult } from '@/hooks/useConsoleCommandTester';
import { cn } from '@/lib/utils';

interface ConsoleCommandTesterProps {
    onCommandSend: (command: string) => Promise<void> | void;
    commandCatalog: Record<string, Record<string, string[]> | string[]>;
    labName?: string;
    className?: string;
}

export default function ConsoleCommandTester({
    onCommandSend,
    commandCatalog,
    labName,
    className,
}: ConsoleCommandTesterProps) {
    const [showDetails, setShowDetails] = useState(false);
    const [selectedSuite, setSelectedSuite] = useState<string | null>(null);

    const {
        testSuites,
        isRunning,
        currentSuite,
        runAllTestsFromCatalog,
        resetTests,
        getGlobalStats,
    } = useConsoleCommandTester({
        onCommandSend,
        timeout: 10000,
        onTestComplete: (suite) => {
            console.log('Test suite complétée:', suite);
        },
    });

    const handleRunTests = useCallback(async () => {
        const suiteName = labName ? `Tests - ${labName}` : 'Test Suite';
        await runAllTestsFromCatalog(commandCatalog, suiteName, 500);
    }, [runAllTestsFromCatalog, commandCatalog, labName]);

    const handleStopTests = useCallback(() => {
        resetTests();
    }, [resetTests]);

    const stats = getGlobalStats();
    const activeSuite = currentSuite || testSuites[testSuites.length - 1];

    const getStatusIcon = (status: TestResult['status']) => {
        switch (status) {
            case 'success':
                return <CheckCircle2 className="h-4 w-4 text-green-500" />;
            case 'error':
                return <XCircle className="h-4 w-4 text-red-500" />;
            case 'timeout':
                return <Clock className="h-4 w-4 text-orange-500" />;
            case 'running':
                return <Loader2 className="h-4 w-4 text-blue-500 animate-spin" />;
            default:
                return <Clock className="h-4 w-4 text-gray-500" />;
        }
    };

    const getStatusColor = (status: TestResult['status']) => {
        switch (status) {
            case 'success':
                return 'text-green-600 dark:text-green-400';
            case 'error':
                return 'text-red-600 dark:text-red-400';
            case 'timeout':
                return 'text-orange-600 dark:text-orange-400';
            case 'running':
                return 'text-blue-600 dark:text-blue-400';
            default:
                return 'text-gray-600 dark:text-gray-400';
        }
    };

    return (
        <Card className={cn("w-full", className)}>
            <CardHeader>
                <div className="flex items-center justify-between">
                    <CardTitle className="flex items-center gap-2">
                        <BarChart3 className="h-5 w-5" />
                        Tests TDD - Commandes Console
                    </CardTitle>
                    <div className="flex items-center gap-2">
                        {isRunning ? (
                            <Button
                                variant="destructive"
                                size="sm"
                                onClick={handleStopTests}
                            >
                                <Square className="h-4 w-4 mr-2" />
                                Arrêter
                            </Button>
                        ) : (
                            <Button
                                variant="default"
                                size="sm"
                                onClick={handleRunTests}
                                disabled={isRunning}
                            >
                                <Play className="h-4 w-4 mr-2" />
                                Lancer les tests
                            </Button>
                        )}
                    </div>
                </div>
            </CardHeader>

            <CardContent className="space-y-4">
                {/* Statistiques globales */}
                {stats.totalTests > 0 && (
                    <div className="grid grid-cols-2 md:grid-cols-4 gap-4 p-4 bg-muted rounded-lg">
                        <div>
                            <p className="text-xs text-muted-foreground">Total</p>
                            <p className="text-2xl font-bold">{stats.totalTests}</p>
                        </div>
                        <div>
                            <p className="text-xs text-muted-foreground">Succès</p>
                            <p className="text-2xl font-bold text-green-600">{stats.successCount}</p>
                        </div>
                        <div>
                            <p className="text-xs text-muted-foreground">Erreurs</p>
                            <p className="text-2xl font-bold text-red-600">{stats.errorCount}</p>
                        </div>
                        <div>
                            <p className="text-xs text-muted-foreground">Temps moyen</p>
                            <p className="text-2xl font-bold">{stats.avgDuration}ms</p>
                        </div>
                    </div>
                )}

                {/* Barre de progression */}
                {activeSuite && (
                    <div className="space-y-2">
                        <div className="flex items-center justify-between text-sm">
                            <span className="font-medium">{activeSuite.name}</span>
                            <span className="text-muted-foreground">
                                {activeSuite.results.length} / {activeSuite.commands.length}
                            </span>
                        </div>
                        <Progress 
                            value={(activeSuite.results.length / activeSuite.commands.length) * 100} 
                            className="h-2"
                        />
                        {activeSuite.totalDuration && (
                            <p className="text-xs text-muted-foreground">
                                Durée totale: {activeSuite.totalDuration}ms
                            </p>
                        )}
                    </div>
                )}

                {/* Liste des résultats */}
                {activeSuite && activeSuite.results.length > 0 && (
                    <div className="space-y-2 max-h-[400px] overflow-y-auto">
                        <div className="flex items-center justify-between mb-2">
                            <h4 className="text-sm font-semibold">Résultats des tests</h4>
                            <Button
                                variant="ghost"
                                size="sm"
                                onClick={() => setShowDetails(!showDetails)}
                            >
                                {showDetails ? 'Masquer' : 'Afficher'} détails
                            </Button>
                        </div>
                        {activeSuite.results.map((result, index) => (
                            <div
                                key={`${result.command}-${index}`}
                                className={cn(
                                    "flex items-center gap-3 p-3 rounded-lg border transition-colors",
                                    result.status === 'success' && "bg-green-50 dark:bg-green-900/10 border-green-200 dark:border-green-800",
                                    result.status === 'error' && "bg-red-50 dark:bg-red-900/10 border-red-200 dark:border-red-800",
                                    result.status === 'timeout' && "bg-orange-50 dark:bg-orange-900/10 border-orange-200 dark:border-orange-800",
                                    result.status === 'running' && "bg-blue-50 dark:bg-blue-900/10 border-blue-200 dark:border-blue-800",
                                    result.status === 'pending' && "bg-gray-50 dark:bg-gray-900/10 border-gray-200 dark:border-gray-800"
                                )}
                            >
                                {getStatusIcon(result.status)}
                                <div className="flex-1 min-w-0">
                                    <div className="flex items-center gap-2 flex-wrap">
                                        <code className="text-sm font-mono">{result.command}</code>
                                        <Badge variant="outline" className={cn("text-xs", getStatusColor(result.status))}>
                                            {result.status}
                                        </Badge>
                                        {result.duration !== undefined && (
                                            <Badge variant="secondary" className="text-xs">
                                                {result.duration}ms
                                            </Badge>
                                        )}
                                    </div>
                                    {showDetails && result.error && (
                                        <p className="text-xs text-red-600 dark:text-red-400 mt-1">
                                            {result.error}
                                        </p>
                                    )}
                                    {showDetails && result.output && (
                                        <p className="text-xs text-muted-foreground mt-1 font-mono">
                                            {result.output}
                                        </p>
                                    )}
                                </div>
                            </div>
                        ))}
                    </div>
                )}

                {/* Message d'état initial */}
                {!isRunning && testSuites.length === 0 && (
                    <div className="flex flex-col items-center justify-center py-8 text-center">
                        <AlertCircle className="h-12 w-12 text-muted-foreground mb-4" />
                        <p className="text-sm text-muted-foreground">
                            Aucun test exécuté
                        </p>
                        <p className="text-xs text-muted-foreground mt-1">
                            Cliquez sur "Lancer les tests" pour commencer
                        </p>
                    </div>
                )}
            </CardContent>
        </Card>
    );
}

