import { useCallback, useEffect, useState } from 'react';
import { safeGetItem, safeSetItem } from '@/utils/safe-storage';

export type Appearance = 'light' | 'dark' | 'system';

// Helper pour accéder à localStorage de manière sécurisée (utilise le polyfill)
const getLocalStorage = (key: string, defaultValue: string | null = null): string | null => {
    return safeGetItem(key, defaultValue);
};

const setLocalStorage = (key: string, value: string): boolean => {
    safeSetItem(key, value);
    return true; // Toujours retourner true car le fallback mémoire fonctionne toujours
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
