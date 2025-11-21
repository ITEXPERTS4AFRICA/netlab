import { useCallback, useEffect, useState } from 'react';

export type Appearance = 'light' | 'dark' | 'system';

// Helper pour accéder à localStorage de manière sécurisée
const getLocalStorage = (key: string, defaultValue: string | null = null): string | null => {
    try {
        if (typeof window === 'undefined' || !window.localStorage) {
            return defaultValue;
        }
        return window.localStorage.getItem(key);
    } catch (error) {
        // localStorage n'est pas disponible (bloqué par le navigateur, mode privé, etc.)
        console.warn('localStorage is not available:', error);
        return defaultValue;
    }
};

const setLocalStorage = (key: string, value: string): boolean => {
    try {
        if (typeof window === 'undefined' || !window.localStorage) {
            return false;
        }
        window.localStorage.setItem(key, value);
        return true;
    } catch (error) {
        // localStorage n'est pas disponible
        console.warn('localStorage is not available:', error);
        return false;
    }
};

const prefersDark = () => {
    if (typeof window === 'undefined') {
        return false;
    }

    return window.matchMedia('(prefers-color-scheme: dark)').matches;
};

const setCookie = (name: string, value: string, days = 365) => {
    if (typeof document === 'undefined') {
        return;
    }

    const maxAge = days * 24 * 60 * 60;
    document.cookie = `${name}=${value};path=/;max-age=${maxAge};SameSite=Lax`;
};

const applyTheme = (appearance: Appearance) => {
    const isDark = appearance === 'dark' || (appearance === 'system' && prefersDark());

    document.documentElement.classList.toggle('dark', isDark);
    document.documentElement.style.colorScheme = isDark ? 'dark' : 'light';
};

const mediaQuery = () => {
    if (typeof window === 'undefined') {
        return null;
    }

    return window.matchMedia('(prefers-color-scheme: dark)');
};

const handleSystemThemeChange = () => {
    const currentAppearance = (getLocalStorage('appearance') as Appearance) || 'system';
    applyTheme(currentAppearance);
};

export function initializeTheme() {
    const savedAppearance = (getLocalStorage('appearance') as Appearance) || 'system';

    applyTheme(savedAppearance);

    // Add the event listener for system theme changes...
    mediaQuery()?.addEventListener('change', handleSystemThemeChange);
}

export function useAppearance() {
    const [appearance, setAppearance] = useState<Appearance>('system');

    const updateAppearance = useCallback((mode: Appearance) => {
        setAppearance(mode);

        // Store in localStorage for client-side persistence (si disponible)...
        setLocalStorage('appearance', mode);

        // Store in cookie for SSR (toujours disponible)...
        setCookie('appearance', mode);

        applyTheme(mode);
    }, []);

    useEffect(() => {
        const savedAppearance = (getLocalStorage('appearance') as Appearance | null) || 'system';
        updateAppearance(savedAppearance);

        return () => mediaQuery()?.removeEventListener('change', handleSystemThemeChange);
    }, [updateAppearance]);

    return { appearance, updateAppearance } as const;
}
