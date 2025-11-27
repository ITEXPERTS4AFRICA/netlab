/**
 * Analyseur de logs système pour extraire des informations structurées
 * depuis les logs de démarrage Linux/CML
 */

export type LogLevel = 'info' | 'warning' | 'error' | 'success' | 'debug';

export interface ParsedLogEntry {
    timestamp?: string;
    level: LogLevel;
    category: string;
    message: string;
    raw: string;
    metadata?: Record<string, unknown>;
}

export interface SystemInfo {
    kernel?: {
        version: string;
        architecture: string;
        commandLine?: string;
    };
    hardware?: {
        cpu?: string;
        memory?: string;
        cpus?: number;
    };
    network?: {
        interfaces: Array<{
            name: string;
            mac?: string;
            up: boolean;
            address?: string;
            mask?: string;
        }>;
    };
    services?: {
        name: string;
        status: 'starting' | 'running' | 'stopped' | 'error';
        message?: string;
    }[];
    errors?: Array<{
        message: string;
        timestamp?: string;
        severity: 'warning' | 'error' | 'critical';
    }>;
    bootTime?: number; // en millisecondes
    systemReady?: boolean;
}

/**
 * Analyser une ligne de log et extraire des informations structurées
 */
export function parseLogLine(line: string): ParsedLogEntry {
    const raw = line.trim();
    if (!raw) {
        return {
            level: 'debug',
            category: 'empty',
            message: '',
            raw: '',
        };
    }

    // Extraire le timestamp si présent (format: |0000013694|)
    const timestampMatch = raw.match(/^\|(\d+)\|/);
    const timestamp = timestampMatch ? timestampMatch[1] : undefined;
    const content = timestampMatch ? raw.substring(timestampMatch[0].length) : raw;

    // Détecter le niveau de log
    let level: LogLevel = 'info';
    let category = 'system';
    let message = content;

    // Détecter les erreurs
    if (
        /error|Error|ERROR|fail|Fail|FAIL|fatal|Fatal|FATAL|critical|Critical|CRITICAL/i.test(content) ||
        /unable|Unable|UNABLE|cannot|Cannot|CANNOT|invalid|Invalid|INVALID/i.test(content)
    ) {
        level = 'error';
        category = 'error';
    }
    // Détecter les warnings
    else if (
        /warn|Warn|WARN|warning|Warning|WARNING|deprecated|Deprecated/i.test(content) ||
        /mostly harmless|differences|Dirty bit/i.test(content)
    ) {
        level = 'warning';
        category = 'warning';
    }
    // Détecter les succès
    else if (
        /success|Success|SUCCESS|ok|OK|enabled|Enabled|ENABLED|ready|Ready|READY|mounted|Mounted/i.test(content) ||
        /System Ready|Verified OK/i.test(content)
    ) {
        level = 'success';
        category = 'success';
    }
    // Détecter les catégories spécifiques
    else if (/kernel|Kernel|KERNEL|Linux version/i.test(content)) {
        category = 'kernel';
    } else if (/network|Network|NET|eth|interface|Interface|PCI|pci/i.test(content)) {
        category = 'network';
    } else if (/mount|Mount|filesystem|Filesystem|fsck/i.test(content)) {
        category = 'filesystem';
    } else if (/service|Service|starting|Starting|systemd/i.test(content)) {
        category = 'service';
    } else if (/cloud-init|Cloud-init/i.test(content)) {
        category = 'cloud-init';
    } else if (/login|Login|viptela/i.test(content)) {
        category = 'login';
    }

    return {
        timestamp,
        level,
        category,
        message: content,
        raw: line,
    };
}

/**
 * Analyser un ensemble de logs et extraire les informations système
 */
export function analyzeSystemLogs(logs: string[]): SystemInfo {
    const info: SystemInfo = {
        network: { interfaces: [] },
        services: [],
        errors: [],
    };

    let bootStartTime: number | null = null;
    let systemReadyTime: number | null = null;

    for (const line of logs) {
        const parsed = parseLogLine(line);
        const content = parsed.message;

        // Extraire la version du kernel
        const kernelMatch = content.match(/Linux version ([\d.]+-[^ ]+)/);
        if (kernelMatch && !info.kernel) {
            info.kernel = {
                version: kernelMatch[1],
                architecture: content.match(/\(([^)]+)\)/)?.[1] || 'unknown',
            };
        }

        // Extraire la ligne de commande du kernel
        const cmdlineMatch = content.match(/Kernel command line: (.+)/);
        if (cmdlineMatch && info.kernel) {
            info.kernel.commandLine = cmdlineMatch[1];
        }

        // Extraire les informations CPU
        const cpuMatch = content.match(/smpboot: CPU\d+: (.+?) \(/);
        if (cpuMatch && !info.hardware?.cpu) {
            if (!info.hardware) info.hardware = {};
            info.hardware.cpu = cpuMatch[1];
        }

        // Extraire le nombre de CPUs
        const cpusMatch = content.match(/Total of (\d+) processors activated/);
        if (cpusMatch && !info.hardware?.cpus) {
            if (!info.hardware) info.hardware = {};
            info.hardware.cpus = parseInt(cpusMatch[1], 10);
        }

        // Extraire la mémoire
        const memoryMatch = content.match(/Memory: ([\dK]+)\/([\dK]+)K available/);
        if (memoryMatch && !info.hardware?.memory) {
            if (!info.hardware) info.hardware = {};
            info.hardware.memory = `${memoryMatch[1]}/${memoryMatch[2]}K`;
        }

        // Extraire les interfaces réseau
        const interfaceMatch = content.match(/\| (eth\d+|lo|sit0) \| (True|False) \| ([^|]+) \| ([^|]+) \| ([^|]+) \| ([^|]+) \|/);
        if (interfaceMatch) {
            const [, name, up, address, mask, scope, mac] = interfaceMatch;
            if (!info.network) info.network = { interfaces: [] };
            info.network.interfaces.push({
                name: name.trim(),
                up: up.trim() === 'True',
                address: address.trim() !== '.' ? address.trim() : undefined,
                mask: mask.trim() !== '.' ? mask.trim() : undefined,
                mac: mac.trim() !== '.' ? mac.trim() : undefined,
            });
        }

        // Détecter le démarrage du système
        if (content.includes('Booting paravirtualized kernel') && !bootStartTime && parsed.timestamp) {
            bootStartTime = parseInt(parsed.timestamp, 10);
        }

        // Détecter quand le système est prêt
        if (content.includes('System Ready') && !systemReadyTime && parsed.timestamp) {
            systemReadyTime = parseInt(parsed.timestamp, 10);
            info.systemReady = true;
        }

        // Extraire les erreurs
        if (parsed.level === 'error') {
            info.errors = info.errors || [];
            info.errors.push({
                message: content,
                timestamp: parsed.timestamp,
                severity: content.match(/fatal|Fatal|FATAL|critical|Critical/i) ? 'critical' : 'error',
            });
        }

        // Extraire les services
        const serviceMatch = content.match(/(starting|Starting|registered|Registered|enabled|Enabled):\s*(.+?)(?:\s|$)/);
        if (serviceMatch) {
            const serviceName = serviceMatch[2].trim();
            const status = content.match(/starting|Starting/) ? 'starting' : 'running';
            if (!info.services?.some((s) => s.name === serviceName)) {
                info.services = info.services || [];
                info.services.push({
                    name: serviceName,
                    status,
                });
            }
        }
    }

    // Calculer le temps de boot
    if (bootStartTime !== null && systemReadyTime !== null) {
        info.bootTime = systemReadyTime - bootStartTime;
    }

    return info;
}

/**
 * Formater les informations système pour l'affichage
 */
export function formatSystemInfo(info: SystemInfo): string {
    const lines: string[] = [];

    if (info.kernel) {
        lines.push(`Kernel: ${info.kernel.version} (${info.kernel.architecture})`);
    }

    if (info.hardware) {
        if (info.hardware.cpu) {
            lines.push(`CPU: ${info.hardware.cpu}`);
        }
        if (info.hardware.cpus) {
            lines.push(`CPUs: ${info.hardware.cpus}`);
        }
        if (info.hardware.memory) {
            lines.push(`Memory: ${info.hardware.memory}`);
        }
    }

    if (info.network?.interfaces && info.network.interfaces.length > 0) {
        lines.push(`\nInterfaces réseau (${info.network.interfaces.length}):`);
        for (const iface of info.network.interfaces) {
            const status = iface.up ? 'UP' : 'DOWN';
            const addr = iface.address || 'N/A';
            lines.push(`  - ${iface.name}: ${status} (${addr})`);
        }
    }

    if (info.bootTime !== undefined) {
        lines.push(`\nTemps de boot: ${(info.bootTime / 1000).toFixed(2)}s`);
    }

    if (info.systemReady) {
        lines.push(`Système: ✅ Prêt`);
    }

    if (info.errors && info.errors.length > 0) {
        lines.push(`\n⚠️ Erreurs détectées: ${info.errors.length}`);
        for (const error of info.errors.slice(0, 5)) {
            lines.push(`  - ${error.message.substring(0, 80)}...`);
        }
    }

    return lines.join('\n');
}

/**
 * Filtrer les logs par catégorie
 */
export function filterLogsByCategory(logs: string[], category: string): string[] {
    return logs.filter((line) => {
        const parsed = parseLogLine(line);
        return parsed.category === category;
    });
}

/**
 * Obtenir un résumé des logs
 */
export function getLogSummary(logs: string[]): {
    total: number;
    byLevel: Record<LogLevel, number>;
    byCategory: Record<string, number>;
    errors: number;
    warnings: number;
} {
    const summary = {
        total: logs.length,
        byLevel: {
            info: 0,
            warning: 0,
            error: 0,
            success: 0,
            debug: 0,
        } as Record<LogLevel, number>,
        byCategory: {} as Record<string, number>,
        errors: 0,
        warnings: 0,
    };

    for (const line of logs) {
        const parsed = parseLogLine(line);
        summary.byLevel[parsed.level] = (summary.byLevel[parsed.level] || 0) + 1;
        summary.byCategory[parsed.category] = (summary.byCategory[parsed.category] || 0) + 1;

        if (parsed.level === 'error') summary.errors++;
        if (parsed.level === 'warning') summary.warnings++;
    }

    return summary;
}


