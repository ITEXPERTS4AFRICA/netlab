/**
 * Utilitaire sécurisé pour localStorage avec fallback
 * Gère les cas où localStorage est bloqué (Edge, mode privé, iframe, etc.)
 */

// Vérifier si localStorage est disponible
const isLocalStorageAvailable = (): boolean => {
    try {
        if (typeof window === 'undefined') {
            return false;
        }
        
        // Test d'écriture/lecture
        const testKey = '__localStorage_test__';
        window.localStorage.setItem(testKey, 'test');
        window.localStorage.removeItem(testKey);
        return true;
    } catch {
        // localStorage bloqué ou indisponible
        return false;
    }
};

// Fallback en mémoire si localStorage n'est pas disponible
const memoryStorage: Record<string, string> = {};

/**
 * Récupérer une valeur depuis localStorage (ou fallback mémoire)
 */
export const safeGetItem = (key: string, defaultValue: string | null = null): string | null => {
    try {
        if (isLocalStorageAvailable()) {
            return window.localStorage.getItem(key);
        }
    } catch {
        // Ignorer silencieusement les erreurs de sécurité (localStorage bloqué)
    }
    
    // Fallback en mémoire
    return memoryStorage[key] ?? defaultValue;
};

/**
 * Stocker une valeur dans localStorage (ou fallback mémoire)
 */
export const safeSetItem = (key: string, value: string): boolean => {
    try {
        if (isLocalStorageAvailable()) {
            window.localStorage.setItem(key, value);
            return true;
        }
    } catch {
        // Ignorer silencieusement les erreurs de sécurité (localStorage bloqué)
    }
    
    // Fallback en mémoire
    memoryStorage[key] = value;
    return false; // Retourne false pour indiquer qu'on utilise le fallback
};

/**
 * Supprimer une valeur de localStorage (ou fallback mémoire)
 */
export const safeRemoveItem = (key: string): void => {
    try {
        if (isLocalStorageAvailable()) {
            window.localStorage.removeItem(key);
            return;
        }
    } catch {
        // Ignorer silencieusement les erreurs de sécurité (localStorage bloqué)
    }
    
    // Fallback en mémoire
    delete memoryStorage[key];
};

/**
 * Vider tout le localStorage (ou fallback mémoire)
 */
export const safeClear = (): void => {
    try {
        if (isLocalStorageAvailable()) {
            window.localStorage.clear();
            return;
        }
    } catch {
        // Ignorer silencieusement les erreurs de sécurité (localStorage bloqué)
    }
    
    // Fallback en mémoire
    Object.keys(memoryStorage).forEach(key => delete memoryStorage[key]);
};

/**
 * Créer un polyfill pour window.localStorage si nécessaire
 * À appeler au démarrage de l'application
 */
export const setupLocalStoragePolyfill = (): void => {
    if (typeof window === 'undefined') {
        return;
    }
    
    // Si localStorage n'est pas disponible, créer un polyfill
    if (!isLocalStorageAvailable()) {
        try {
            // Créer un objet localStorage factice
            Object.defineProperty(window, 'localStorage', {
                value: {
                    getItem: (key: string) => safeGetItem(key),
                    setItem: (key: string, value: string) => safeSetItem(key, value),
                    removeItem: (key: string) => safeRemoveItem(key),
                    clear: () => safeClear(),
                    get length() {
                        return Object.keys(memoryStorage).length;
                    },
                    key: (index: number) => {
                        const keys = Object.keys(memoryStorage);
                        return keys[index] ?? null;
                    },
                },
                writable: false,
                configurable: false,
            });
            
            console.info('localStorage polyfill activé (utilise le stockage en mémoire)');
        } catch (error) {
            console.warn('Impossible de créer le polyfill localStorage:', error);
        }
    }
};

