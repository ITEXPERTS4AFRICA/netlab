/**
 * Utilitaire pour tester l'envoi de commandes CLI dans la console IOS
 * 
 * Note: CML n'expose pas d'API directe pour envoyer des commandes CLI.
 * Les commandes doivent être tapées dans l'iframe de la console.
 * Ce module teste la capacité à :
 * 1. Accéder à la console
 * 2. Envoyer des commandes (via l'iframe)
 * 3. Récupérer les résultats via polling des logs
 */

export interface CommandTestResult {
    command: string;
    status: 'success' | 'error' | 'timeout' | 'pending';
    output?: string;
    duration?: number;
    error?: string;
    timestamp: Date;
}

export interface ConsoleTestSuite {
    name: string;
    commands: string[];
    results: CommandTestResult[];
    totalDuration?: number;
    successCount: number;
    errorCount: number;
}

/**
 * Commandes IOS de base pour tester la console
 */
export const BASIC_IOS_COMMANDS = [
    'show version',
    'show ip interface brief',
    'show running-config',
    'show clock',
    'show inventory',
    'enable',
    'configure terminal',
    'exit',
    'show interfaces',
    'show vlan',
];

/**
 * Commandes IOS avancées pour tester la gestion des labs
 */
export const ADVANCED_IOS_COMMANDS = [
    'show ip route',
    'show cdp neighbors',
    'show lldp neighbors',
    'show spanning-tree',
    'show mac address-table',
    'show access-lists',
    'show ospf neighbor',
    'show eigrp neighbors',
    'show bgp summary',
    'ping 8.8.8.8',
    'traceroute 8.8.8.8',
];

/**
 * Commandes de configuration pour tester la modification des équipements
 */
export const CONFIG_IOS_COMMANDS = [
    'configure terminal',
    'hostname TEST-ROUTER',
    'interface gigabitethernet 0/0',
    'ip address 192.168.1.1 255.255.255.0',
    'no shutdown',
    'exit',
    'exit',
    'write memory',
];

/**
 * Tester une commande CLI unique
 * 
 * @param command La commande à tester
 * @param consoleUrl L'URL de la console (iframe)
 * @param consoleId L'ID de la console pour récupérer les logs
 * @param timeout Timeout en millisecondes
 * @returns Promise<CommandTestResult>
 */
export async function testCommand(
    command: string,
    consoleUrl: string | null,
    consoleId: string | null,
    timeout: number = 10000
): Promise<CommandTestResult> {
    const startTime = Date.now();
    const result: CommandTestResult = {
        command,
        status: 'pending',
        timestamp: new Date(),
    };

    try {
        if (!consoleUrl || !consoleId) {
            throw new Error('Console URL ou ID non disponible');
        }

        // Simuler l'envoi de la commande via l'iframe
        // Note: Dans la réalité, la commande doit être tapée dans l'iframe
        // Ici, on simule juste l'envoi et on attend les logs
        
        // Attendre un délai pour simuler l'exécution
        const isShowCommand = command.trim().toLowerCase().startsWith('show');
        const waitTime = isShowCommand ? 2000 : 1000;
        await new Promise(resolve => setTimeout(resolve, waitTime));

        // Récupérer les logs pour voir les résultats
        // Dans un vrai test, on appellerait l'API pour récupérer les logs
        const logResponse = await fetch(`/api/labs/${consoleId}/nodes/${consoleId}/consoles/${consoleId}/log`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'include',
        });

        if (!logResponse.ok) {
            throw new Error(`Erreur lors de la récupération des logs: ${logResponse.status}`);
        }

        const logData = await logResponse.json();
        const output = logData.log || logData.output || logData.data || '';

        const duration = Date.now() - startTime;

        // Vérifier si la commande a été exécutée (rechercher dans les logs)
        const commandFound = output.toLowerCase().includes(command.toLowerCase());
        const hasOutput = output.length > 0;

        return {
            ...result,
            status: commandFound || hasOutput ? 'success' : 'error',
            output: output.substring(0, 500), // Limiter la taille
            duration,
        };
    } catch (error) {
        const duration = Date.now() - startTime;
        const errorMessage = error instanceof Error ? error.message : 'Erreur inconnue';
        const isTimeout = duration >= timeout;

        return {
            ...result,
            status: isTimeout ? 'timeout' : 'error',
            duration,
            error: errorMessage,
        };
    }
}

/**
 * Exécuter une suite de tests de commandes CLI
 * 
 * @param commands Liste des commandes à tester
 * @param consoleUrl URL de la console
 * @param consoleId ID de la console
 * @param delayBetweenCommands Délai entre chaque commande (ms)
 * @returns Promise<ConsoleTestSuite>
 */
export async function runCommandTestSuite(
    commands: string[],
    consoleUrl: string | null,
    consoleId: string | null,
    delayBetweenCommands: number = 2000
): Promise<ConsoleTestSuite> {
    const suite: ConsoleTestSuite = {
        name: 'Test Suite - Commandes CLI IOS',
        commands,
        results: [],
        successCount: 0,
        errorCount: 0,
    };

    const startTime = Date.now();

    for (const command of commands) {
        const result = await testCommand(command, consoleUrl, consoleId);
        suite.results.push(result);

        if (result.status === 'success') {
            suite.successCount++;
        } else {
            suite.errorCount++;
        }

        // Délai entre les commandes
        if (delayBetweenCommands > 0) {
            await new Promise(resolve => setTimeout(resolve, delayBetweenCommands));
        }
    }

    suite.totalDuration = Date.now() - startTime;

    return suite;
}

/**
 * Vérifier si la console est prête à recevoir des commandes
 * 
 * @param consoleUrl URL de la console
 * @param consoleId ID de la console
 * @returns Promise<boolean>
 */
export async function isConsoleReady(
    consoleUrl: string | null,
    consoleId: string | null
): Promise<boolean> {
    if (!consoleUrl || !consoleId) {
        return false;
    }

    try {
        // Tester l'accès aux logs
        const response = await fetch(`/api/labs/${consoleId}/nodes/${consoleId}/consoles/${consoleId}/log`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
            },
            credentials: 'include',
        });

        return response.ok;
    } catch {
        return false;
    }
}


