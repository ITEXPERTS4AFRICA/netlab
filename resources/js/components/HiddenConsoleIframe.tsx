import { useEffect, useRef, useState } from 'react';

type Props = {
    consoleUrl: string | null;
    command: string | null;
    onCommandSent: () => void;
    enabled: boolean;
};

/**
 * Composant iframe caché pour envoyer des commandes à la console CML
 * 
 * Note: CML n'a pas d'API REST pour envoyer des commandes directement.
 * Ce composant charge l'iframe de la console CML et tente d'envoyer les commandes
 * via le DOM de l'iframe. Cependant, cela peut ne pas fonctionner à cause des
 * restrictions de sécurité du navigateur (CORS, same-origin policy).
 */
export default function HiddenConsoleIframe({ consoleUrl, command, onCommandSent, enabled }: Props) {
    const iframeRef = useRef<HTMLIFrameElement>(null);
    const [isReady, setIsReady] = useState(false);
    const [lastCommand, setLastCommand] = useState<string | null>(null);

    useEffect(() => {
        if (!enabled || !consoleUrl) {
            return;
        }

        const iframe = iframeRef.current;
        if (!iframe) return;

        // Attendre que l'iframe soit chargé
        const handleLoad = () => {
            setIsReady(true);
            console.log('✅ Iframe console CML chargée:', consoleUrl);
        };

        iframe.addEventListener('load', handleLoad);

        // Charger l'URL de la console
        if (iframe.src !== consoleUrl) {
            iframe.src = consoleUrl;
        }

        return () => {
            iframe.removeEventListener('load', handleLoad);
        };
    }, [consoleUrl, enabled]);

    useEffect(() => {
        if (!enabled || !command || !isReady || command === lastCommand) {
            return;
        }

        const iframe = iframeRef.current;
        if (!iframe || !iframe.contentWindow || !iframe.contentDocument) {
            console.warn('⚠️ Iframe console non accessible (restrictions CORS)');
            // Même si on ne peut pas envoyer la commande via l'iframe,
            // on notifie que la commande a été "envoyée" pour déclencher le polling
            setLastCommand(command);
            onCommandSent();
            return;
        }

        try {
            // Tenter d'envoyer la commande via le DOM de l'iframe
            const iframeDoc = iframe.contentDocument;
            const iframeWindow = iframe.contentWindow;

            // Chercher un élément input ou textarea dans l'iframe avec plusieurs tentatives
            // La console CML peut prendre du temps à charger
            let attempts = 0;
            const maxAttempts = 10;
            
            const trySendCommand = () => {
                attempts++;
                
                // Chercher différents types de champs de saisie
                const input = iframeDoc.querySelector('input[type="text"]') as HTMLInputElement ||
                             iframeDoc.querySelector('input:not([type="hidden"])') as HTMLInputElement ||
                             iframeDoc.querySelector('textarea') as HTMLTextAreaElement ||
                             iframeDoc.querySelector('[contenteditable="true"]') as HTMLElement ||
                             iframeDoc.activeElement as HTMLInputElement;

                if (input) {
                    try {
                        // Simuler la saisie de la commande
                        if (input instanceof HTMLInputElement || input instanceof HTMLTextAreaElement) {
                            input.value = command;
                            input.focus();
                            
                            // Déclencher les événements de saisie
                            const inputEvent = new Event('input', { bubbles: true });
                            input.dispatchEvent(inputEvent);
                        } else if (input instanceof HTMLElement && input.contentEditable === 'true') {
                            input.textContent = command;
                            input.focus();
                        }

                        // Attendre un peu avant d'envoyer Enter
                        setTimeout(() => {
                            // Simuler l'appui sur Enter
                            const enterEvent = new KeyboardEvent('keydown', {
                                key: 'Enter',
                                code: 'Enter',
                                keyCode: 13,
                                which: 13,
                                bubbles: true,
                                cancelable: true,
                            });

                            input.dispatchEvent(enterEvent);

                            // Aussi envoyer un keyup
                            const enterUpEvent = new KeyboardEvent('keyup', {
                                key: 'Enter',
                                code: 'Enter',
                                keyCode: 13,
                                which: 13,
                                bubbles: true,
                                cancelable: true,
                            });

                            input.dispatchEvent(enterUpEvent);
                            
                            // Et un keypress
                            const enterPressEvent = new KeyboardEvent('keypress', {
                                key: 'Enter',
                                code: 'Enter',
                                keyCode: 13,
                                which: 13,
                                bubbles: true,
                                cancelable: true,
                            });

                            input.dispatchEvent(enterPressEvent);

                            console.log('✅ Commande envoyée via iframe:', command);
                            setLastCommand(command);
                            onCommandSent();
                        }, 100);
                    } catch (err) {
                        console.warn('⚠️ Erreur lors de l\'envoi de la commande:', err);
                        if (attempts < maxAttempts) {
                            setTimeout(trySendCommand, 200);
                        } else {
                            setLastCommand(command);
                            onCommandSent();
                        }
                    }
                } else if (attempts < maxAttempts) {
                    // Réessayer après un court délai
                    setTimeout(trySendCommand, 200);
                } else {
                    console.warn('⚠️ Aucun champ de saisie trouvé dans l\'iframe console après', maxAttempts, 'tentatives');
                    // Même si on ne trouve pas de champ, on déclenche le polling
                    setLastCommand(command);
                    onCommandSent();
                }
            };
            
            // Commencer la tentative d'envoi
            trySendCommand();
        } catch (err) {
            // Erreur probablement due aux restrictions CORS
            console.warn('⚠️ Impossible d\'envoyer la commande via l\'iframe (CORS):', err);
            // Même en cas d'erreur, on déclenche le polling pour récupérer les logs
            setLastCommand(command);
            onCommandSent();
        }
    }, [command, isReady, enabled, lastCommand, onCommandSent]);

    if (!enabled || !consoleUrl) {
        return null;
    }

    return (
        <iframe
            ref={iframeRef}
            src={consoleUrl}
            className="hidden"
            style={{
                position: 'absolute',
                width: '1px',
                height: '1px',
                opacity: 0,
                pointerEvents: 'none',
                border: 'none',
            }}
            title="Console CML cachée"
            sandbox="allow-same-origin allow-scripts"
        />
    );
}

