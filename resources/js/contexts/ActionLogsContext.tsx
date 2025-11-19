import { createContext, useContext, useState, useCallback, ReactNode } from 'react';

export type ActionLogEntry = {
    id: string;
    timestamp: Date;
    type: 'command' | 'session' | 'system' | 'error';
    action: string;
    details?: string;
    status: 'pending' | 'sent' | 'success' | 'error';
    nodeId?: string;
    command?: string;
};

const MAX_ACTION_LOGS = 200;

type ActionLogsContextType = {
    actionLogs: ActionLogEntry[];
    addActionLog: (entry: Omit<ActionLogEntry, 'id' | 'timestamp'>) => void;
    updateActionLogStatus: (id: string, status: ActionLogEntry['status'], details?: string) => void;
    clearLogs: () => void;
};

const ActionLogsContext = createContext<ActionLogsContextType | undefined>(undefined);

export function ActionLogsProvider({ children }: { children: ReactNode }) {
    const [actionLogs, setActionLogs] = useState<ActionLogEntry[]>([]);

    const addActionLog = useCallback((entry: Omit<ActionLogEntry, 'id' | 'timestamp'>) => {
        const newEntry: ActionLogEntry = {
            ...entry,
            id: `${Date.now()}-${Math.random().toString(36).substr(2, 9)}`,
            timestamp: new Date(),
        };
        setActionLogs(prev => {
            const next = [newEntry, ...prev];
            if (next.length > MAX_ACTION_LOGS) {
                return next.slice(0, MAX_ACTION_LOGS);
            }
            return next;
        });
    }, []);

    const updateActionLogStatus = useCallback((id: string, status: ActionLogEntry['status'], details?: string) => {
        setActionLogs(prev => prev.map(log => 
            log.id === id 
                ? { ...log, status, details: details || log.details }
                : log
        ));
    }, []);

    const clearLogs = useCallback(() => {
        setActionLogs([]);
    }, []);

    return (
        <ActionLogsContext.Provider value={{ actionLogs, addActionLog, updateActionLogStatus, clearLogs }}>
            {children}
        </ActionLogsContext.Provider>
    );
}

export function useActionLogs() {
    const context = useContext(ActionLogsContext);
    if (context === undefined) {
        throw new Error('useActionLogs must be used within an ActionLogsProvider');
    }
    return context;
}

