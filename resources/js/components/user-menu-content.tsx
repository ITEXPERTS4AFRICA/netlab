/**
 * Contenu du menu utilisateur pour l'application NetLab
 *
 * Ce composant gère le contenu du menu déroulant utilisateur avec
 * les options de profil, paramètres et déconnexion. Il assure
 * une gestion propre de la navigation mobile et du cache.
 *
 * Fonctionnalités principales :
 * - Affichage des informations détaillées de l'utilisateur
 * - Lien vers les paramètres utilisateur
 * - Gestion sécurisée de la déconnexion avec nettoyage du cache
 * - Intégration avec la navigation mobile
 * - Gestion des événements de clic avec nettoyage automatique
 *
 * Sécurité et performance :
 * - Nettoyage automatique du cache lors de la déconnexion
 * - Gestion propre de la navigation mobile
 * - Préchargement des pages cibles pour de meilleures performances
 * - Séparateurs visuels pour une meilleure organisation
 *
 * @author NetLab Team
 * @version 1.0.0
 * @since 2025-01-01
 */

import { DropdownMenuGroup, DropdownMenuItem, DropdownMenuLabel, DropdownMenuSeparator } from '@/components/ui/dropdown-menu';
import { UserInfo } from '@/components/user-info';
import { useMobileNavigation } from '@/hooks/use-mobile-navigation';
import { logout, settings } from '@/routes';

// import { edit } from '@/routes/profile';
import { type User } from '@/types';
import { Link, router } from '@inertiajs/react';
import { LogOut, Settings } from 'lucide-react';

/**
 * Interface des propriétés du composant UserMenuContent
 */
interface UserMenuContentProps {
    /** Objet utilisateur contenant les informations d'authentification */
    user: User;
}

/**
 * Composant principal du contenu du menu utilisateur
 *
 * Gère l'affichage et les interactions du menu déroulant utilisateur
 * avec gestion du cache et de la navigation mobile.
 *
 * @param props - Propriétés du composant
 * @returns {JSX.Element} Contenu du menu utilisateur
 */
export function UserMenuContent({ user }: UserMenuContentProps) {
    // Hook pour la gestion de la navigation mobile
    const cleanup = useMobileNavigation();

    /**
     * Gestionnaire de déconnexion avec nettoyage du cache
     *
     * Effectue la déconnexion de l'utilisateur en nettoyant
     * la navigation mobile et en vidant le cache des pages.
     */
    const handleLogout = () => {
        // Nettoyage de la navigation mobile
        cleanup();

        // Vidage du cache des pages Inertia pour une déconnexion propre
        router.flushAll();
    };

    return (
        <>
            {/* Section d'informations utilisateur */}
            <DropdownMenuLabel className="p-0 font-normal">
                <div className="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
                    {/* Affichage détaillé des informations utilisateur */}
                    <UserInfo user={user} showEmail={true} />
                </div>
            </DropdownMenuLabel>

            {/* Séparateur visuel */}
            <DropdownMenuSeparator />

            {/* Groupe des actions principales */}
            <DropdownMenuGroup>
                {/* Lien vers les paramètres utilisateur */}
                <DropdownMenuItem asChild>
                    <Link
                        className="block w-full"
                        href={settings()}
                        as="button"
                        prefetch // Préchargement de la page pour de meilleures performances
                        onClick={cleanup} // Nettoyage de la navigation mobile
                    >
                        <Settings className="mr-2" />
                        Settings
                    </Link>
                </DropdownMenuItem>
            </DropdownMenuGroup>

            {/* Séparateur visuel */}
            <DropdownMenuSeparator />

            {/* Action de déconnexion */}
            <DropdownMenuItem asChild>
                <Link
                    className="block w-full"
                    href={logout()}
                    as="button"
                    onClick={handleLogout} // Gestionnaire de déconnexion personnalisé
                    data-test="logout-button" // Attribut de test pour l'automatisation
                >
                    <LogOut className="mr-2" />
                    Log out
                </Link>
            </DropdownMenuItem>
        </>
    );
}
