import { useState, useCallback, useRef } from 'react';

export type TestResult = {
    command: string;
    status: 'pending' | 'running' | 'success' | 'error' | 'timeout';
    duration?: number;
    error?: string;
    output?: string;
    timestamp: Date;
};

export type TestSuite = {
    name: string;
    commands: string[];
    results: TestResult[];
    totalDuration?: number;
    successCount: number;
    errorCount: number;
    timeoutCount: number;
};

interface UseConsoleCommandTesterOptions {
    onCommandSend: (command: string) => Promise<void> | void;
    timeout?: number; // Timeout en millisecondes pour chaque commande
    onTestComplete?: (suite: TestSuite) => void;
}

export const useConsoleCommandTester = ({
    onCommandSend,
    timeout = 10000, // 10 secondes par défaut
    onTestComplete,
}: UseConsoleCommandTesterOptions) => {
    const [testSuites, setTestSuites] = useState<TestSuite[]>([]);
    const [isRunning, setIsRunning] = useState(false);
    const [currentSuite, setCurrentSuite] = useState<TestSuite | null>(null);
    const timeoutRefs = useRef<Map<string, NodeJS.Timeout>>(new Map());

    // Extraire toutes les commandes du catalogue IOS
    const extractAllCommands = useCallback((catalog: Record<string, Record<string, string[]> | string[]>): string[] => {
        const commands: string[] = [];
        
        for (const [category, value] of Object.entries(catalog)) {
            if (Array.isArray(value)) {
                // Si c'est un tableau de commandes simples
                commands.push(...value);
            } else if (typeof value === 'object') {
                // Si c'est un objet avec des commandes principales et sous-commandes
                for (const [mainCmd, subCmds] of Object.entries(value)) {
                    commands.push(mainCmd);
                    if (Array.isArray(subCmds) && subCmds.length > 0) {
                        // Ajouter les combinaisons principales
                        subCmds.slice(0, 3).forEach(subCmd => {
                            commands.push(`${mainCmd} ${subCmd}`);
                        });
                    }
                }
            }
        }
        
        // Dédupliquer et retourner
        return Array.from(new Set(commands));
    }, []);

    // Tester une commande unique avec attente de réponse
    const testCommand = useCallback(async (
        command: string,
        suiteName: string
    ): Promise<TestResult> => {
        const startTime = Date.now();
        const testId = `${suiteName}-${command}-${startTime}`;
        
        // Créer le résultat initial
        const result: TestResult = {
            command,
            status: 'running',
            timestamp: new Date(),
        };

        // Mettre à jour le résultat dans la suite
        setTestSuites(prev => prev.map(suite => {
            if (suite.name === suiteName) {
                return {
                    ...suite,
                    results: [...suite.results, result],
                };
            }
            return suite;
        }));

        try {
            // Créer un timeout
            const timeoutPromise = new Promise<never>((_, reject) => {
                const timeoutId = setTimeout(() => {
                    reject(new Error(`Timeout après ${timeout}ms`));
                }, timeout);
                timeoutRefs.current.set(testId, timeoutId);
            });

            // Exécuter la commande et attendre un délai minimum pour simuler la réponse
            const commandPromise = (async () => {
                await Promise.resolve(onCommandSend(command));
                // Attendre un délai minimum pour permettre à la commande de s'exécuter
                // Pour les commandes show, on attend un peu plus longtemps
                const isShowCommand = command.trim().toLowerCase().startsWith('show');
                const waitTime = isShowCommand ? 1000 : 500;
                await new Promise(resolve => setTimeout(resolve, waitTime));
            })();

            // Attendre la commande ou le timeout
            await Promise.race([commandPromise, timeoutPromise]);

            // Nettoyer le timeout
            const timeoutId = timeoutRefs.current.get(testId);
            if (timeoutId) {
                clearTimeout(timeoutId);
                timeoutRefs.current.delete(testId);
            }

            const duration = Date.now() - startTime;

            const successResult: TestResult = {
                ...result,
                status: 'success',
                duration,
            };

            // Mettre à jour le résultat
            setTestSuites(prev => prev.map(suite => {
                if (suite.name === suiteName) {
                    const existingResult = suite.results.find(r => 
                        r.command === command && r.timestamp.getTime() === result.timestamp.getTime()
                    );
                    if (existingResult) {
                        return {
                            ...suite,
                            results: suite.results.map(r => 
                                r.command === command && r.timestamp.getTime() === result.timestamp.getTime()
                                    ? successResult
                                    : r
                            ),
                            successCount: suite.successCount + (existingResult.status === 'success' ? 0 : 1),
                        };
                    }
                    return {
                        ...suite,
                        results: [...suite.results, successResult],
                        successCount: suite.successCount + 1,
                    };
                }
                return suite;
            }));

            return successResult;
        } catch (err) {
            // Nettoyer le timeout
            const timeoutId = timeoutRefs.current.get(testId);
            if (timeoutId) {
                clearTimeout(timeoutId);
                timeoutRefs.current.delete(testId);
            }

            const duration = Date.now() - startTime;
            const errorMessage = err instanceof Error ? err.message : 'Erreur inconnue';
            const isTimeout = errorMessage.includes('Timeout');

            const errorResult: TestResult = {
                ...result,
                status: isTimeout ? 'timeout' : 'error',
                duration,
                error: errorMessage,
            };

            // Mettre à jour le résultat
            setTestSuites(prev => prev.map(suite => {
                if (suite.name === suiteName) {
                    const existingResult = suite.results.find(r => 
                        r.command === command && r.timestamp.getTime() === result.timestamp.getTime()
                    );
                    if (existingResult) {
                        return {
                            ...suite,
                            results: suite.results.map(r => 
                                r.command === command && r.timestamp.getTime() === result.timestamp.getTime()
                                    ? errorResult
                                    : r
                            ),
                            errorCount: isTimeout ? suite.errorCount : suite.errorCount + (existingResult.status === 'error' ? 0 : 1),
                            timeoutCount: isTimeout ? suite.timeoutCount + (existingResult.status === 'timeout' ? 0 : 1) : suite.timeoutCount,
                        };
                    }
                    return {
                        ...suite,
                        results: [...suite.results, errorResult],
                        errorCount: isTimeout ? suite.errorCount : suite.errorCount + 1,
                        timeoutCount: isTimeout ? suite.timeoutCount + 1 : suite.timeoutCount,
                    };
                }
                return suite;
            }));

            return errorResult;
        }
    }, [onCommandSend, timeout]);

    // Exécuter une suite de tests
    const runTestSuite = useCallback(async (
        name: string,
        commands: string[],
        delayBetweenCommands: number = 500
    ): Promise<TestSuite> => {
        setIsRunning(true);

        const suite: TestSuite = {
            name,
            commands,
            results: [],
            successCount: 0,
            errorCount: 0,
            timeoutCount: 0,
        };

        setCurrentSuite(suite);
        setTestSuites(prev => [...prev, suite]);

        const startTime = Date.now();

        // Exécuter chaque commande avec un délai
        for (let i = 0; i < commands.length; i++) {
            const command = commands[i];
            
            await testCommand(command, name);
            
            // Délai entre les commandes (sauf pour la dernière)
            if (i < commands.length - 1 && delayBetweenCommands > 0) {
                await new Promise(resolve => setTimeout(resolve, delayBetweenCommands));
            }
        }

        const totalDuration = Date.now() - startTime;

        // Mettre à jour la suite avec les statistiques finales
        const finalSuite: TestSuite = {
            ...suite,
            totalDuration,
        };

        setTestSuites(prev => prev.map(s => 
            s.name === name ? finalSuite : s
        ));

        setCurrentSuite(null);
        setIsRunning(false);

        // Appeler le callback
        if (onTestComplete) {
            onTestComplete(finalSuite);
        }

        return finalSuite;
    }, [testCommand, onTestComplete]);

    // Exécuter tous les tests depuis un catalogue de commandes
    const runAllTestsFromCatalog = useCallback(async (
        catalog: Record<string, Record<string, string[]> | string[]>,
        suiteName: string = 'Test Suite',
        delayBetweenCommands: number = 500
    ): Promise<TestSuite> => {
        const commands = extractAllCommands(catalog);
        return runTestSuite(suiteName, commands, delayBetweenCommands);
    }, [extractAllCommands, runTestSuite]);

    // Réinitialiser les tests
    const resetTests = useCallback(() => {
        // Nettoyer tous les timeouts
        timeoutRefs.current.forEach(timeoutId => clearTimeout(timeoutId));
        timeoutRefs.current.clear();
        
        setTestSuites([]);
        setCurrentSuite(null);
        setIsRunning(false);
    }, []);

    // Obtenir les statistiques globales
    const getGlobalStats = useCallback(() => {
        const allResults = testSuites.flatMap(suite => suite.results);
        const totalTests = allResults.length;
        const successCount = allResults.filter(r => r.status === 'success').length;
        const errorCount = allResults.filter(r => r.status === 'error').length;
        const timeoutCount = allResults.filter(r => r.status === 'timeout').length;
        const avgDuration = allResults
            .filter(r => r.duration !== undefined)
            .reduce((sum, r) => sum + (r.duration || 0), 0) / (totalTests || 1);

        return {
            totalTests,
            successCount,
            errorCount,
            timeoutCount,
            avgDuration: Math.round(avgDuration),
            successRate: totalTests > 0 ? (successCount / totalTests) * 100 : 0,
        };
    }, [testSuites]);

    return {
        testSuites,
        isRunning,
        currentSuite,
        runTestSuite,
        runAllTestsFromCatalog,
        testCommand,
        resetTests,
        getGlobalStats,
    };
};

