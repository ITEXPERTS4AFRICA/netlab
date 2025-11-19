import { useMemo, useState } from 'react';

// Base de données des commandes IOS avec leurs sous-commandes
const IOS_COMMANDS: Record<string, string[]> = {
    // Commandes de niveau utilisateur
    'show': [
        'running-config', 'startup-config', 'version', 'inventory', 'clock',
        'ip', 'interface', 'vlan', 'cdp', 'lldp', 'arp', 'route', 'ospf',
        'eigrp', 'bgp', 'access-lists', 'mac', 'spanning-tree', 'trunk',
        'port-security', 'users', 'sessions', 'processes', 'memory', 'cpu',
        'flash', 'boot', 'redundancy', 'standby', 'logging', 'snmp',
    ],
    'configure': ['terminal', 'memory', 'network'],
    'enable': ['password', 'secret'],
    'disable': [],
    'exit': [],
    'logout': [],
    'reload': [],
    'copy': ['running-config', 'startup-config', 'flash', 'tftp', 'ftp'],
    'write': ['memory', 'erase'],
    'erase': ['startup-config', 'flash'],
    'ping': [],
    'traceroute': [],
    'telnet': [],
    'ssh': [],
    'debug': [],
    'undebug': [],
    'terminal': ['length', 'width', 'monitor', 'no', 'history'],
    'clear': ['line', 'counters', 'arp', 'mac', 'spanning-tree'],
    'no': ['shutdown', 'shutdown'],
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
};

// Commandes de configuration d'interface
const INTERFACE_COMMANDS = [
    'ip address', 'ip access-group', 'shutdown', 'no shutdown',
    'description', 'duplex', 'speed', 'switchport', 'switchport mode',
    'switchport access vlan', 'switchport trunk', 'spanning-tree',
    'port-security', 'cdp', 'lldp',
];

// Commandes de configuration de ligne
const LINE_COMMANDS = [
    'password', 'login', 'exec-timeout', 'logging synchronous',
    'transport input', 'transport output', 'access-class',
];

/**
 * Hook pour l'auto-complétion des commandes IOS
 */
export const useIOSAutocomplete = () => {
    const [commandHistory, setCommandHistory] = useState<string[]>([]);
    const [historyIndex, setHistoryIndex] = useState(-1);

    /**
     * Analyse une commande et retourne les suggestions possibles
     */
    const getSuggestions = (input: string): string[] => {
        if (!input.trim()) {
            return Object.keys(IOS_COMMANDS).slice(0, 10);
        }

        const parts = input.trim().split(/\s+/);
        const command = parts[0].toLowerCase();
        const context = parts.slice(1);

        // Si on est en mode configuration
        const isConfigMode = commandHistory.some(cmd => 
            cmd.toLowerCase().startsWith('configure') || 
            cmd.toLowerCase() === 'conf t'
        );

        // Si on est dans une interface
        const isInInterface = commandHistory.some(cmd => 
            cmd.toLowerCase().startsWith('interface ')
        );

        // Si on est dans une ligne
        const isInLine = commandHistory.some(cmd => 
            cmd.toLowerCase().startsWith('line ')
        );

        // Suggestions pour la première partie (commande principale)
        if (parts.length === 1) {
            const matches = Object.keys(IOS_COMMANDS).filter(cmd =>
                cmd.toLowerCase().startsWith(command)
            );
            
            // Ajouter des commandes contextuelles
            if (isConfigMode) {
                matches.push(...['interface', 'ip', 'vlan', 'line', 'hostname', 'banner', 'enable', 'username', 'exit', 'end']);
            }
            
            return matches.slice(0, 20);
        }

        // Suggestions pour les sous-commandes
        const mainCommand = command;
        if (IOS_COMMANDS[mainCommand]) {
            const lastPart = context[context.length - 1] || '';
            const suggestions = IOS_COMMANDS[mainCommand].filter(sub =>
                sub.toLowerCase().startsWith(lastPart.toLowerCase())
            );

            // Suggestions contextuelles selon le mode
            if (mainCommand === 'interface' && context.length === 0) {
                return ['gigabitethernet', 'fastethernet', 'ethernet', 'serial', 'loopback', 'vlan', 'port-channel'];
            }

            if (isInInterface && context.length === 0) {
                return INTERFACE_COMMANDS.filter(cmd => 
                    cmd.toLowerCase().startsWith(lastPart.toLowerCase())
                );
            }

            if (isInLine && context.length === 0) {
                return LINE_COMMANDS.filter(cmd => 
                    cmd.toLowerCase().startsWith(lastPart.toLowerCase())
                );
            }

            return suggestions.slice(0, 20);
        }

        // Suggestions pour les numéros d'interface (ex: gigabitethernet 0/0)
        if (mainCommand === 'interface' && context.length === 1) {
            const interfaceType = context[0].toLowerCase();
            if (interfaceType.includes('gigabit') || interfaceType.includes('fast') || interfaceType.includes('ethernet')) {
                return ['0/0', '0/1', '0/2', '1/0', '1/1'];
            }
            if (interfaceType === 'serial') {
                return ['0/0/0', '0/0/1', '0/1/0'];
            }
            if (interfaceType === 'loopback') {
                return ['0', '1', '2'];
            }
            if (interfaceType === 'vlan') {
                return ['1', '10', '20', '100'];
            }
        }

        return [];
    };

    /**
     * Complète automatiquement une commande partielle
     */
    const autocomplete = (input: string): string => {
        const suggestions = getSuggestions(input);
        if (suggestions.length === 0) return input;

        const parts = input.trim().split(/\s+/);
        const lastPart = parts[parts.length - 1] || '';
        const prefix = parts.slice(0, -1).join(' ');

        // Trouver la suggestion la plus longue qui correspond
        const exactMatch = suggestions.find(s => s.toLowerCase() === lastPart.toLowerCase());
        if (exactMatch) {
            // Si on a une correspondance exacte, chercher la prochaine suggestion
            const nextIndex = suggestions.indexOf(exactMatch) + 1;
            if (nextIndex < suggestions.length) {
                return prefix ? `${prefix} ${suggestions[nextIndex]}` : suggestions[nextIndex];
            }
        }

        // Trouver la suggestion qui commence par le dernier mot
        const match = suggestions.find(s => 
            s.toLowerCase().startsWith(lastPart.toLowerCase()) && 
            s.toLowerCase() !== lastPart.toLowerCase()
        );

        if (match) {
            return prefix ? `${prefix} ${match}` : match;
        }

        return input;
    };

    /**
     * Ajoute une commande à l'historique
     */
    const addToHistory = (command: string) => {
        if (command.trim() && commandHistory[commandHistory.length - 1] !== command) {
            setCommandHistory(prev => [...prev, command].slice(-100)); // Garder les 100 dernières
            setHistoryIndex(-1);
        }
    };

    /**
     * Récupère la commande précédente de l'historique
     */
    const getPreviousHistory = (): string | null => {
        if (commandHistory.length === 0) return null;
        const newIndex = historyIndex === -1 ? commandHistory.length - 1 : Math.max(0, historyIndex - 1);
        setHistoryIndex(newIndex);
        return commandHistory[newIndex];
    };

    /**
     * Récupère la commande suivante de l'historique
     */
    const getNextHistory = (): string | null => {
        if (historyIndex === -1) return null;
        const newIndex = Math.min(commandHistory.length - 1, historyIndex + 1);
        if (newIndex >= commandHistory.length - 1) {
            setHistoryIndex(-1);
            return '';
        }
        setHistoryIndex(newIndex);
        return commandHistory[newIndex];
    };

    /**
     * Réinitialise l'index de l'historique
     */
    const resetHistoryIndex = () => {
        setHistoryIndex(-1);
    };

    return {
        getSuggestions,
        autocomplete,
        addToHistory,
        getPreviousHistory,
        getNextHistory,
        resetHistoryIndex,
        commandHistory,
    };
};


