import { useEffect } from 'react';
import { useCmlToken } from '@/hooks/useCmlToken';
import { Loader2 } from 'lucide-react';

interface CmlTokenRefresherProps {
    /**
     * Afficher un loader pendant le rafraîchissement
     */
    showLoader?: boolean;
    /**
     * Rafraîchir automatiquement si le token est absent
     */
    autoRefresh?: boolean;
    /**
     * Intervalle de vérification en millisecondes (désactivé si 0)
     */
    checkInterval?: number;
    /**
     * Callback appelé quand le token est rafraîchi
     */
    onTokenRefreshed?: () => void;
    /**
     * Callback appelé quand le rafraîchissement échoue
     */
    onRefreshFailed?: () => void;
}

/**
 * Composant pour gérer automatiquement le rafraîchissement du token CML
 * Affiche un loader pendant le rafraîchissement et rafraîchit silencieusement si nécessaire
 */
export function CmlTokenRefresher({
    showLoader = true,
    autoRefresh = true,
    checkInterval = 0,
    onTokenRefreshed,
    onRefreshFailed,
}: CmlTokenRefresherProps) {
    const { hasToken, isRefreshing, ensureToken } = useCmlToken();

    useEffect(() => {
        // Vérifier et rafraîchir au montage si nécessaire (une seule fois)
        if (autoRefresh && !hasToken) {
            ensureToken(true, false).then((success) => {
                if (success && onTokenRefreshed) {
                    onTokenRefreshed();
                } else if (!success && onRefreshFailed) {
                    onRefreshFailed();
                }
            });
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []); // Vérifier seulement au montage

    useEffect(() => {
        if (checkInterval <= 0 || !autoRefresh) {
            return;
        }

        // Vérifier périodiquement le token seulement si on n'a pas de token
        // Si on a un token, on vérifie moins souvent (toutes les 10 minutes au lieu de 5)
        const interval = setInterval(() => {
            // Ne vérifier que si on n'a pas de token ou si le cache est expiré
            if (!hasToken) {
                ensureToken(autoRefresh, false).then((success) => {
                    if (success && onTokenRefreshed) {
                        onTokenRefreshed();
                    } else if (!success && onRefreshFailed) {
                        onRefreshFailed();
                    }
                });
            }
        }, hasToken ? checkInterval * 2 : checkInterval); // Vérifier moins souvent si on a un token

        return () => clearInterval(interval);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [checkInterval, autoRefresh, hasToken]); // Ne pas inclure les callbacks dans les dépendances

    // Afficher un loader pendant le rafraîchissement
    if (showLoader && isRefreshing) {
        return (
            <div className="fixed inset-0 bg-background/80 backdrop-blur-sm z-50 flex items-center justify-center">
                <div className="flex flex-col items-center gap-4">
                    <Loader2 className="h-8 w-8 animate-spin text-primary" />
                    <p className="text-sm text-muted-foreground">
                        Rafraîchissement de la connexion NETLABS...
                    </p>
                </div>
            </div>
        );
    }

    return null;
}

