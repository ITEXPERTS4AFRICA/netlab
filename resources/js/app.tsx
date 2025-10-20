/**
 * Point d'entrée principal de l'application React NetLab
 *
 * Ce fichier configure et initialise l'application React avec Inertia.js
 * pour une intégration transparente entre Laravel et React. Il gère
 * l'initialisation du thème, l'écran de démarrage et la configuration
 * globale de l'application.
 *
 * Fonctionnalités principales :
 * - Configuration d'Inertia.js pour le rendu côté serveur
 * - Gestion de l'écran de démarrage avec transition fluide
 * - Initialisation automatique du thème (clair/sombre)
 * - Résolution automatique des composants de page
 * - Configuration de la barre de progression de chargement
 * - Gestion du titre de page dynamique
 *
 * Architecture :
 * - Utilise React 18 avec createRoot pour le rendu moderne
 * - Intégration avec Laravel via Inertia.js
 * - Système de thème automatique au chargement
 * - Transition en douceur depuis l'écran de démarrage
 * - Résolution dynamique des pages depuis le dossier pages/
 *
 * @author NetLab Team
 * @version 2.0.0
 * @since 2025-01-01
 */

import '../css/app.css';

import React, { useState } from 'react';
import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';
import { initializeTheme } from './hooks/use-appearance';
import SplashScreen from './components/splash-screen';

/**
 * Nom de l'application depuis les variables d'environnement
 * Fallback vers 'Laravel' si non défini
 */
const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

/**
 * Configuration et initialisation de l'application Inertia.js
 *
 * Configure le comportement global de l'application incluant :
 * - Formatage automatique du titre de page
 * - Résolution des composants de page
 * - Configuration du rendu React
 * - Gestion de l'écran de démarrage
 */
createInertiaApp({
    /**
     * Configuration du titre de page dynamique
     * Ajoute automatiquement le nom de l'application à chaque titre
     *
     * @param title Titre spécifique de la page ou null
     * @returns Titre formaté avec le nom de l'application
     */
    title: (title) => title ? `${title} - ${appName}` : appName,

    /**
     * Résolution des composants de page
     * Recherche automatiquement les composants dans le dossier pages/
     * Supporte la structure de fichiers dynamique avec glob patterns
     */
    resolve: (name) => resolvePageComponent(
        `./pages/${name}.tsx`,
        import.meta.glob('./pages/**/*.tsx')
    ),

    /**
     * Configuration du rendu React
     * Met en place le système de démarrage avec écran splash
     * et transition fluide vers l'application principale
     */
    setup({ el, App, props }) {
        const root = createRoot(el);

        /**
         * Composant wrapper pour gérer l'écran de démarrage
         * Contrôle l'affichage de l'écran splash et la transition
         * vers l'application principale
         */
        const AppWrapper = () => {
            // État de visibilité de l'écran de démarrage
            const [showSplash, setShowSplash] = useState(true);

            /**
             * Gestionnaire de fin de chargement de l'écran splash
             * Déclenche la transition vers l'application principale
             */
            const handleSplashComplete = () => {
                setShowSplash(false);
            };

            return (
                <>
                    {/* Écran de démarrage conditionnel */}
                    {showSplash && (
                        <SplashScreen onLoadingComplete={handleSplashComplete} />
                    )}

                    {/* Application principale avec transition d'opacité */}
                    <div className={`${
                        showSplash ? 'opacity-0' : 'opacity-100'
                    } transition-opacity duration-500`}>
                        <App {...props} />
                    </div>
                </>
            );
        };

        // Rendu de l'application avec le wrapper
        root.render(<AppWrapper />);
    },

    /**
     * Configuration de la barre de progression
     * Définit l'apparence du loader pendant les changements de page
     */
    progress: {
        color: '#4B5563',
    },
});

/**
 * Initialisation du système de thème
 * Configure automatiquement le thème (clair/sombre) au chargement
 * de l'application selon les préférences utilisateur
 */
initializeTheme();
