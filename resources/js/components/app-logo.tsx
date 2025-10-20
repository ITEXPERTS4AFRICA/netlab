/**
 * Composant logo principal de l'application NetLab
 *
 * Ce composant gère l'affichage du logo et du nom de l'application
 * dans la barre latérale. Il combine l'icône personnalisée avec
 * le texte de la marque pour une identification visuelle cohérente.
 *
 * Fonctionnalités principales :
 * - Affichage de l'icône NetLab personnalisée
 * - Intégration du nom de l'application
 * - Design adaptatif avec thème sombre/clair
 * - Format optimisé pour la barre latérale
 * - Styles cohérents avec le système de design
 *
 * Design et accessibilité :
 * - Format carré pour l'icône avec fond coloré
 * - Typographie optimisée pour la lisibilité
 * - Support du thème sombre avec inversion des couleurs
 * - Espacement et proportions équilibrés
 * - Troncature élégante pour les petits espaces
 *
 * @author NetLab Team
 * @version 1.0.0
 * @since 2025-01-01
 */

/**
 * Composant principal du logo de l'application
 *
 * Combine l'icône et le texte de la marque dans un format
 * optimisé pour l'affichage dans la barre latérale.
 *
 * @returns {JSX.Element} Élément du logo avec icône et texte
 */
export default function AppLogo() {
    return (
        <>
            {/* Conteneur de l'icône avec fond stylisé */}
            <div className="flex aspect-square size-8 items-center justify-center rounded-md bg-sidebar-primary text-sidebar-primary-foreground">
                {/* Icône NetLab stylisée avec des initiales */}
                <div className="w-5 h-5 bg-gradient-to-br from-primary to-accent rounded flex items-center justify-center">
                    <span className="text-primary-foreground font-bold text-xs">NL</span>
                </div>
            </div>

            {/* Section texte avec nom de l'application */}
            <div className="ml-1 grid flex-1 text-left text-sm">
                {/* Nom de l'application avec style de titre */}
                <span className="mb-0.5 truncate leading-tight font-semibold">
                    NetLab
                </span>
            </div>
        </>
    );
}
