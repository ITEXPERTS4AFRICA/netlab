import { useState, useEffect, useCallback, useRef } from 'react';
import { toast } from 'sonner';

interface TokenStatus {
    has_token: boolean;
    is_refreshing: boolean;
    last_check: number | null;
}

// Cache global pour éviter les vérifications multiples simultanées
let globalTokenCheck: Promise<boolean> | null = null;
let globalTokenStatus: boolean | null = null;
let globalLastCheck: number = 0;
const CHECK_CACHE_DURATION = 30000; // Cache pendant 30 secondes

/**
 * Hook pour gérer le token CML et son rafraîchissement automatique
 */
export function useCmlToken() {
    // Tous les refs en premier pour garantir l'ordre constant
    const isMountedRef = useRef(true);
    const checkInProgressRef = useRef(false);
    const isRefreshingRef = useRef(false);

    const [status, setStatus] = useState<TokenStatus>({
        has_token: false,
        is_refreshing: false,
        last_check: null,
    });

    // Nettoyer au démontage
    useEffect(() => {
        return () => {
            isMountedRef.current = false;
        };
    }, []);

    /**
     * Vérifier si un token CML existe (avec cache pour éviter les requêtes multiples)
     */
    const checkToken = useCallback(async (force: boolean = false): Promise<boolean> => {
        // Utiliser le cache si disponible et récent
        const now = Date.now();
        if (!force && globalTokenStatus !== null && (now - globalLastCheck) < CHECK_CACHE_DURATION) {
            if (isMountedRef.current) {
                setStatus(prev => ({ ...prev, has_token: globalTokenStatus as boolean, last_check: now }));
            }
            return globalTokenStatus as boolean;
        }

        // Éviter les vérifications simultanées
        if (globalTokenCheck && !force) {
            return globalTokenCheck;
        }

        // Si une vérification est déjà en cours, attendre
        if (checkInProgressRef.current && !force) {
            return globalTokenCheck || Promise.resolve(false);
        }

        checkInProgressRef.current = true;

        globalTokenCheck = (async () => {
            try {
                const response = await fetch('/api/cml/token/check', {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'include',
                });

                const data = await response.json();
                const hasToken = data.has_token || data.token_exists;

                // Mettre à jour le cache global
                globalTokenStatus = hasToken;
                const checkTime = Date.now();
                globalLastCheck = checkTime;

                if (isMountedRef.current) {
                    setStatus(prev => ({ ...prev, has_token: hasToken, last_check: checkTime }));
                }

                return hasToken;
            } catch (error) {
                console.error('Erreur lors de la vérification du token CML:', error);
                globalTokenStatus = false;
                globalLastCheck = Date.now();
                return false;
            } finally {
                checkInProgressRef.current = false;
                globalTokenCheck = null;
            }
        })();

        return globalTokenCheck;
    }, []);

    /**
     * Rafraîchir le token CML silencieusement
     * @param showLoading Afficher un loader pendant le rafraîchissement
     */
    const refreshToken = useCallback(async (showLoading: boolean = false): Promise<boolean> => {
        if (isRefreshingRef.current) {
            return false; // Déjà en cours de rafraîchissement
        }

        isRefreshingRef.current = true;
        setStatus(prev => ({ ...prev, is_refreshing: true }));

        let loadingToast: string | number | null = null;
        if (showLoading) {
            loadingToast = toast.loading('Rafraîchissement de la connexion NETLAB...', {
                duration: Infinity,
            });
        }

        try {
            const response = await fetch('/api/cml/token/refresh', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content || '',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'include',
            });

            const data = await response.json();

            if (loadingToast) {
                toast.dismiss(loadingToast);
            }

            const now = Date.now();
            isRefreshingRef.current = false;
            if (data.success && data.has_token) {
                globalTokenStatus = true;
                globalLastCheck = now;
                setStatus({ has_token: true, is_refreshing: false, last_check: now });
                if (showLoading) {
                    toast.success('Connexion CML rétablie', { duration: 2000 });
                }
                return true;
            } else {
                globalTokenStatus = false;
                globalLastCheck = now;
                setStatus({ has_token: false, is_refreshing: false, last_check: now });
                if (showLoading) {
                    toast.error(data.message || 'Impossible de se connecter à CML', { duration: 3000 });
                }
                return false;
            }
        } catch (error) {
            console.error('Erreur lors du rafraîchissement du token CML:', error);
            const now = Date.now();
            isRefreshingRef.current = false;
            globalTokenStatus = false;
            globalLastCheck = now;
            setStatus({ has_token: false, is_refreshing: false, last_check: now });

            if (loadingToast) {
                toast.dismiss(loadingToast);
            }

            if (showLoading) {
                toast.error('Erreur lors du rafraîchissement de la connexion NETLAB', { duration: 3000 });
            }

            return false;
        }
    }, []); // Pas de dépendances - utilise des refs pour l'état

    /**
     * Vérifier et rafraîchir automatiquement le token si nécessaire
     */
    const ensureToken = useCallback(async (autoRefresh: boolean = true, force: boolean = false): Promise<boolean> => {
        const hasToken = await checkToken(force);

        if (!hasToken && autoRefresh) {
            // Pas de token, essayer de rafraîchir silencieusement
            const refreshed = await refreshToken(false);
            if (refreshed) {
                // Invalider le cache après rafraîchissement réussi
                globalTokenStatus = true;
                globalLastCheck = Date.now();
            }
            return refreshed;
        }

        return hasToken;
    }, [checkToken, refreshToken]);

    // Vérifier le token une seule fois au montage du composant
    useEffect(() => {
        // Vérifier seulement si on n'a pas encore vérifié ou si le cache est expiré
        const now = Date.now();
        if (status.last_check === null || (now - (status.last_check || 0)) > CHECK_CACHE_DURATION) {
            checkToken(false).catch(() => {
                // Ignorer les erreurs silencieusement
            });
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []); // Dépendances vides pour ne vérifier qu'une fois au montage

    return {
        hasToken: status.has_token,
        isRefreshing: status.is_refreshing,
        checkToken,
        refreshToken,
        ensureToken,
    };
}

