import '../css/app.css';

import React, { useState } from 'react';
import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';
import { initializeTheme } from './hooks/use-appearance';
import SplashScreen from './components/splash-screen';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => title ? `${title} - ${appName}` : appName,
    resolve: (name) => resolvePageComponent(`./pages/${name}.tsx`, import.meta.glob('./pages/**/*.tsx')),
    setup({ el, App, props }) {
        const root = createRoot(el);

        // Create a wrapper component that manages the splash screen
        const AppWrapper = () => {
            const [showSplash, setShowSplash] = useState(true);

            const handleSplashComplete = () => {
                setShowSplash(false);
            };

            return (
                <>
                    {showSplash && <SplashScreen onLoadingComplete={handleSplashComplete} />}
                    <div className={`${showSplash ? 'opacity-0' : 'opacity-100'} transition-opacity duration-500`}>
                        <App {...props} />
                    </div>
                </>
            );
        };

        root.render(<AppWrapper />);
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on load...
initializeTheme();
