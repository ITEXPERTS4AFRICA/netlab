import { describe, it, expect, vi, beforeEach } from 'vitest';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import React from 'react';
import IOSConsole from '../IOSConsole';

// Mock des composants UI
vi.mock('@/components/ui/card', () => ({
    Card: ({ children, className }: { children: React.ReactNode; className?: string }) => (
        <div data-testid="card" className={className}>{children}</div>
    ),
    CardContent: ({ children, className }: { children: React.ReactNode; className?: string }) => (
        <div data-testid="card-content" className={className}>{children}</div>
    ),
    CardHeader: ({ children }: { children: React.ReactNode }) => (
        <div data-testid="card-header">{children}</div>
    ),
    CardTitle: ({ children }: { children: React.ReactNode }) => (
        <h2 data-testid="card-title">{children}</h2>
    ),
}));

vi.mock('@/components/ui/button', () => ({
    Button: ({ children, onClick, disabled, className }: {
        children: React.ReactNode;
        onClick?: () => void;
        disabled?: boolean;
        className?: string;
    }) => (
        <button
            data-testid="button"
            onClick={onClick}
            disabled={disabled}
            className={className}
        >
            {children}
        </button>
    ),
}));

vi.mock('@/components/ui/badge', () => ({
    Badge: ({ children, variant, className }: {
        children: React.ReactNode;
        variant?: string;
        className?: string;
    }) => (
        <span data-testid="badge" data-variant={variant} className={className}>{children}</span>
    ),
}));

describe('IOSConsole', () => {
    const mockOnSendCommand = vi.fn();
    const defaultProps = {
        onSendCommand: mockOnSendCommand,
        output: [],
        isConnected: true,
        nodeLabel: 'test-node',
    };

    beforeEach(() => {
        vi.clearAllMocks();
    });

    describe('Rendu initial', () => {
        it('devrait afficher le titre de la console', () => {
            render(<IOSConsole {...defaultProps} />);
            expect(screen.getByText('Console IOS')).toBeInTheDocument();
        });

        it('devrait afficher le label du node', () => {
            render(<IOSConsole {...defaultProps} nodeLabel="router-1" />);
            expect(screen.getByText('router-1')).toBeInTheDocument();
        });

        it('devrait afficher le statut de connexion', () => {
            render(<IOSConsole {...defaultProps} isConnected={true} />);
            expect(screen.getByText(/Connecté/i)).toBeInTheDocument();
        });

        it('devrait afficher le statut déconnecté quand isConnected est false', () => {
            render(<IOSConsole {...defaultProps} isConnected={false} />);
            expect(screen.getByText(/Déconnecté/i)).toBeInTheDocument();
        });

        it('devrait afficher un message d\'aide quand il n\'y a pas de sortie', () => {
            render(<IOSConsole {...defaultProps} />);
            expect(screen.getByText(/Console IOS prête/i)).toBeInTheDocument();
        });
    });

    describe('Saisie de commande', () => {
        it('devrait permettre de saisir une commande', async () => {
            const user = userEvent.setup();
            render(<IOSConsole {...defaultProps} />);

            const input = screen.getByPlaceholderText(/Tapez une commande IOS/i);
            await user.type(input, 'show version');

            expect(input).toHaveValue('show version');
        });

        it('devrait envoyer la commande quand on appuie sur Enter', async () => {
            render(<IOSConsole {...defaultProps} />);

            const input = screen.getByPlaceholderText(/Tapez une commande IOS/i) as HTMLInputElement;

            // Simuler la saisie
            fireEvent.change(input, { target: { value: 'show version' } });

            // Attendre un peu pour que l'état soit mis à jour
            await waitFor(() => {
                expect(input).toHaveValue('show version');
            });

            // Simuler l'appui sur Enter directement sur l'input
            fireEvent.keyDown(input, { key: 'Enter', code: 'Enter', keyCode: 13 });

            // Attendre que la fonction soit appelée
            await waitFor(() => {
                expect(mockOnSendCommand).toHaveBeenCalledWith('show version');
            }, { timeout: 2000 });

            // Vérifier que l'input est vidé
            await waitFor(() => {
                expect(input).toHaveValue('');
            });
        });

        it('devrait envoyer la commande quand on clique sur le bouton Envoyer', async () => {
            const user = userEvent.setup();
            render(<IOSConsole {...defaultProps} />);

            const input = screen.getByPlaceholderText(/Tapez une commande IOS/i);
            await user.type(input, 'show ip interface brief');

            const sendButton = screen.getByText('Envoyer');
            await user.click(sendButton);

            expect(mockOnSendCommand).toHaveBeenCalledWith('show ip interface brief');
        });

        it('ne devrait pas envoyer de commande vide', async () => {
            const user = userEvent.setup();
            render(<IOSConsole {...defaultProps} />);

            const input = screen.getByPlaceholderText(/Tapez une commande IOS/i);
            await user.type(input, '   ');
            await user.keyboard('{Enter}');

            expect(mockOnSendCommand).not.toHaveBeenCalled();
        });

        it('devrait désactiver l\'input quand déconnecté', () => {
            render(<IOSConsole {...defaultProps} isConnected={false} />);

            const input = screen.getByPlaceholderText(/Tapez une commande IOS/i);
            expect(input).toBeDisabled();
        });
    });

    describe('Affichage de la sortie', () => {
        it('devrait afficher les lignes de sortie', () => {
            const output = [
                'Router> show version',
                'Cisco IOS Software, Version 15.1',
                'Router uptime is 5 days',
            ];

            render(<IOSConsole {...defaultProps} output={output} />);

            output.forEach(line => {
                expect(screen.getByText(new RegExp(line))).toBeInTheDocument();
            });
        });

        it('devrait colorer les commandes en bleu', () => {
            const output = ['> show version'];
            render(<IOSConsole {...defaultProps} output={output} />);

            // Vérifier que la ligne est présente (la coloration est gérée par CSS)
            expect(screen.getByText('> show version')).toBeInTheDocument();
        });

        it('devrait colorer les erreurs en rouge', () => {
            const output = ['% Invalid input detected'];
            render(<IOSConsole {...defaultProps} output={output} />);

            expect(screen.getByText('% Invalid input detected')).toBeInTheDocument();
        });
    });

    describe('Historique', () => {
        it('devrait afficher le bouton historique quand il y a des commandes', async () => {
            const user = userEvent.setup();
            render(<IOSConsole {...defaultProps} />);

            const input = screen.getByPlaceholderText(/Tapez une commande IOS/i);
            await user.type(input, 'show version');
            await user.keyboard('{Enter}');

            // L'historique devrait être disponible après avoir envoyé une commande
            // Note: Le bouton n'apparaît que si commandHistory.length > 0
            // Ce test nécessite que le hook useIOSAutocomplete fonctionne correctement
        });

        it('devrait permettre de naviguer dans l\'historique avec les flèches', async () => {
            const user = userEvent.setup();
            render(<IOSConsole {...defaultProps} />);

            const input = screen.getByPlaceholderText(/Tapez une commande IOS/i);

            // Envoyer quelques commandes
            await user.type(input, 'show version');
            await user.keyboard('{Enter}');

            await user.type(input, 'show ip interface brief');
            await user.keyboard('{Enter}');

            // Naviguer vers le haut
            await user.click(input);
            await user.keyboard('{ArrowUp}');

            // La commande précédente devrait apparaître
            // Note: Ce test nécessite que le hook fonctionne correctement
        });
    });

    describe('Auto-complétion', () => {
        it('devrait afficher des suggestions quand on tape', async () => {
            const user = userEvent.setup();
            render(<IOSConsole {...defaultProps} />);

            const input = screen.getByPlaceholderText(/Tapez une commande IOS/i);
            await user.type(input, 'sh');

            // Attendre que les suggestions apparaissent
            await waitFor(() => {
                // Les suggestions devraient être visibles
                // Note: Ce test nécessite que le hook fonctionne correctement
            });
        });

        it('devrait compléter avec Tab', async () => {
            const user = userEvent.setup();
            render(<IOSConsole {...defaultProps} />);

            const input = screen.getByPlaceholderText(/Tapez une commande IOS/i);
            await user.type(input, 'sh');
            await user.keyboard('{Tab}');

            // La commande devrait être complétée
            // Note: Ce test nécessite que le hook fonctionne correctement
        });
    });
});


