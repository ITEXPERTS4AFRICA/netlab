import { useEffect, useRef, useState } from 'react';
import { cn } from '@/lib/utils';

interface ConsoleTerminalProps {
    output: string[];
    className?: string;
    showCursor?: boolean;
    typingSpeed?: number;
}

/**
 * Composant terminal avec curseur clignotant et effet de frappe
 */
export default function ConsoleTerminal({
    output,
    className = '',
    showCursor = true,
    typingSpeed = 0, // 0 = pas d'animation, > 0 = vitesse en ms par caractère
}: ConsoleTerminalProps) {
    const [displayedOutput, setDisplayedOutput] = useState<string[]>([]);
    const [cursorVisible, setCursorVisible] = useState(true);
    const outputRef = useRef<HTMLDivElement>(null);
    const cursorIntervalRef = useRef<NodeJS.Timeout | null>(null);
    const typingTimeoutRef = useRef<NodeJS.Timeout | null>(null);

    // Animation du curseur clignotant
    useEffect(() => {
        if (!showCursor) return;

        cursorIntervalRef.current = setInterval(() => {
            setCursorVisible(prev => !prev);
        }, 530); // Vitesse de clignotement

        return () => {
            if (cursorIntervalRef.current) {
                clearInterval(cursorIntervalRef.current);
            }
        };
    }, [showCursor]);

    // Effet de frappe pour les nouvelles lignes
    useEffect(() => {
        if (typingSpeed === 0) {
            // Pas d'animation, afficher directement
            setDisplayedOutput(output);
            return;
        }

        // Annuler l'animation précédente
        if (typingTimeoutRef.current) {
            clearTimeout(typingTimeoutRef.current);
        }

        // Si c'est la première fois ou si de nouvelles lignes sont ajoutées
        const newLines = output.slice(displayedOutput.length);
        
        if (newLines.length === 0) return;

        let currentLineIndex = 0;
        let currentCharIndex = 0;
        const linesToAdd: string[] = [];

        const typeNextChar = () => {
            if (currentLineIndex >= newLines.length) {
                setDisplayedOutput([...displayedOutput, ...linesToAdd]);
                return;
            }

            const currentLine = newLines[currentLineIndex];
            
            if (currentCharIndex < currentLine.length) {
                linesToAdd[currentLineIndex] = currentLine.substring(0, currentCharIndex + 1);
                currentCharIndex++;
                typingTimeoutRef.current = setTimeout(typeNextChar, typingSpeed);
            } else {
                // Ligne complète, passer à la suivante
                currentLineIndex++;
                currentCharIndex = 0;
                if (currentLineIndex < newLines.length) {
                    linesToAdd.push('');
                }
                typingTimeoutRef.current = setTimeout(typeNextChar, typingSpeed * 2); // Pause entre les lignes
            }
        };

        typeNextChar();

        return () => {
            if (typingTimeoutRef.current) {
                clearTimeout(typingTimeoutRef.current);
            }
        };
    }, [output, typingSpeed, displayedOutput]);

    // Auto-scroll vers le bas
    useEffect(() => {
        if (outputRef.current) {
            outputRef.current.scrollTop = outputRef.current.scrollHeight;
        }
    }, [displayedOutput]);

    return (
        <div
            ref={outputRef}
            className={cn(
                'flex-1 bg-black rounded-lg p-4 overflow-y-auto font-mono text-sm',
                'text-green-400 selection:bg-green-900 selection:text-green-100',
                'scrollbar-thin scrollbar-thumb-gray-700 scrollbar-track-transparent',
                className
            )}
            style={{
                textShadow: '0 0 5px rgba(74, 222, 128, 0.5)',
            }}
        >
            {displayedOutput.length === 0 ? (
                <div className="text-gray-500">
                    <p>Console IOS prête. Tapez vos commandes ci-dessous.</p>
                    <p className="mt-2 text-xs">
                        Astuce : Utilisez Tab pour l&apos;auto-complétion, ↑↓ pour l&apos;historique
                    </p>
                </div>
            ) : (
                displayedOutput.map((line, index) => (
                    <div key={index} className="mb-1 animate-fade-in">
                        <TerminalLine line={line} />
                    </div>
                ))
            )}
            {showCursor && (
                <span
                    className={cn(
                        'inline-block w-2 h-4 bg-green-400 ml-1',
                        cursorVisible ? 'opacity-100' : 'opacity-0',
                        'transition-opacity duration-75'
                    )}
                    style={{
                        boxShadow: '0 0 5px rgba(74, 222, 128, 0.8)',
                    }}
                />
            )}
        </div>
    );
}

/**
 * Composant pour formater une ligne de terminal avec coloration syntaxique
 */
function TerminalLine({ line }: { line: string }) {
    // Commandes (commencent par >)
    if (line.startsWith('> ')) {
        return <span className="text-blue-400 font-mono">{line}</span>;
    }

    // Erreurs
    if (line.toLowerCase().includes('error') || 
        line.toLowerCase().includes('invalid') || 
        line.includes('%') ||
        line.toLowerCase().includes('failed')) {
        return <span className="text-red-400 font-mono">{line}</span>;
    }

    // Succès
    if (line.toLowerCase().includes('success') || 
        line.toLowerCase().includes('ok') ||
        line.toLowerCase().includes('up') ||
        line.toLowerCase().includes('enabled')) {
        return <span className="text-green-400 font-mono">{line}</span>;
    }

    // Adresses IP
    const ipRegex = /\b(\d{1,3}\.){3}\d{1,3}\b/g;
    if (ipRegex.test(line)) {
        const parts = line.split(ipRegex);
        const matches = line.match(ipRegex) || [];
        return (
            <span className="font-mono">
                {parts.map((part, i) => (
                    <span key={i}>
                        {part}
                        {matches[i] && <span className="text-cyan-400">{matches[i]}</span>}
                    </span>
                ))}
            </span>
        );
    }

    // Interfaces réseau
    if (line.match(/\b(GigabitEthernet|FastEthernet|Ethernet|Serial|Loopback|Vlan)\d+/i)) {
        return <span className="text-yellow-400 font-mono">{line}</span>;
    }

    // Warnings
    if (line.toLowerCase().includes('warning') || line.includes('!')) {
        return <span className="text-yellow-300 font-mono">{line}</span>;
    }

    // Texte par défaut
    return <span className="font-mono text-green-300">{line}</span>;
}



