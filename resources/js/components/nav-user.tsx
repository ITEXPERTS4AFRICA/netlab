/**
 * Composant de navigation utilisateur pour la barre latérale
 *
 * Ce composant gère l'affichage et les interactions du menu utilisateur
 * dans la barre latérale de l'application. Il fournit un accès rapide
 * aux informations utilisateur et aux actions contextuelles.
 *
 * Fonctionnalités principales :
 * - Affichage des informations utilisateur (avatar, nom, email)
 * - Menu déroulant avec options utilisateur
 * - Adaptation responsive pour mobile et desktop
 * - Gestion intelligente du positionnement selon l'état de la sidebar
 * - Intégration avec le système d'authentification Inertia
 *
 * Comportements :
 * - Sur mobile : menu aligné en bas pour une meilleure accessibilité
 * - Sidebar réduite : menu aligné à gauche pour éviter le débordement
 * - Sidebar normale : menu aligné en bas par défaut
 * - Animation fluide avec indicateur visuel d'ouverture
 *
 * @author NetLab Team
 * @version 1.0.0
 * @since 2025-01-01
 */

import { DropdownMenu, DropdownMenuContent, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { SidebarMenu, SidebarMenuButton, SidebarMenuItem, useSidebar } from '@/components/ui/sidebar';
import { UserInfo } from '@/components/user-info';
import { UserMenuContent } from '@/components/user-menu-content';
import { useIsMobile } from '@/hooks/use-mobile';
import { type SharedData } from '@/types';
import { usePage } from '@inertiajs/react';
import { ChevronsUpDown } from 'lucide-react';

/**
 * Composant principal de navigation utilisateur
 *
 * Gère l'affichage du menu utilisateur dans la sidebar avec
 * un système de dropdown contextuel et responsive.
 *
 * @returns {JSX.Element} Élément du menu utilisateur
 */
export function NavUser() {
    // Récupération des données d'authentification depuis Inertia
    const { auth } = usePage<SharedData>().props;

    // Hooks pour la gestion de l'état de la sidebar et de la responsivité
    const { state } = useSidebar();
    const isMobile = useIsMobile();

    return (
        <SidebarMenu>
            <SidebarMenuItem>
                <DropdownMenu>
                    {/* Déclencheur du menu avec informations utilisateur */}
                    <DropdownMenuTrigger asChild>
                        <SidebarMenuButton
                            size="lg"
                            className="group text-sidebar-accent-foreground data-[state=open]:bg-sidebar-accent"
                        >
                            {/* Affichage des informations utilisateur (avatar, nom) */}
                            <UserInfo user={auth.user} />

                            {/* Indicateur visuel d'ouverture du menu */}
                            <ChevronsUpDown className="ml-auto size-4" />
                        </SidebarMenuButton>
                    </DropdownMenuTrigger>

                    {/* Contenu du menu déroulant avec positionnement intelligent */}
                    <DropdownMenuContent
                        className="w-(--radix-dropdown-menu-trigger-width) min-w-56 rounded-lg"
                        align="end"
                        side={
                            // Positionnement adaptatif selon le contexte
                            isMobile
                                ? 'bottom'     // Sur mobile : en bas pour accessibilité
                                : state === 'collapsed'
                                ? 'left'      // Sidebar réduite : à gauche pour éviter débordement
                                : 'bottom'    // Cas normal : en bas par défaut
                        }
                    >
                        {/* Contenu du menu utilisateur (profil, paramètres, déconnexion) */}
                        <UserMenuContent user={auth.user} />
                    </DropdownMenuContent>
                </DropdownMenu>
            </SidebarMenuItem>
        </SidebarMenu>
    );
}
