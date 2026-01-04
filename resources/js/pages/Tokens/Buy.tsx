import { Head, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Coins, Check, ArrowLeft } from 'lucide-react';
import { toast } from 'sonner';
import { useState } from 'react';

interface TokenPackage {
    id: number;
    name: string;
    tokens: number;
    price_cents: number;
    currency: string;
    description?: string;
    icon_svg?: string;
    is_active: boolean;
}

interface Props {
    packages: TokenPackage[];
    balance: number;
}

export default function BuyTokens({ packages, balance }: Props) {
    const [processing, setProcessing] = useState(false);

    const handleBuyPackage = async (packageId: number) => {
        setProcessing(true);
        try {
            const csrfToken = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content;

            const response = await fetch('/api/tokens/buy', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken || '',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ package_id: packageId }),
            });

            const result = await response.json();

            if (!response.ok) {
                throw new Error(result.error || 'Erreur lors de l\'achat');
            }

            // Redirect to payment URL
            if (result.payment_url) {
                window.location.href = result.payment_url;
            }

        } catch (error: unknown) {
            toast.error(error instanceof Error ? error.message : 'Impossible d\'initier le paiement');
            setProcessing(false);
        }
    };

    const formatPrice = (cents: number, currency: string) => {
        return `${(cents / 100).toLocaleString('fr-FR')} ${currency}`;
    };

    return (
        <AppLayout>
            <Head title="Acheter des Tokens" />

            <div className="container mx-auto py-8 px-4">
                <Button
                    variant="ghost"
                    className="mb-4"
                    onClick={() => router.visit('/settings/profile')}
                >
                    <ArrowLeft className="h-4 w-4 mr-2" />
                    Retour au profil
                </Button>

                <div className="mb-8">
                    <h1 className="text-3xl font-bold">Acheter des Tokens</h1>
                    <p className="text-muted-foreground mt-2">
                        Choisissez un package de tokens pour réserver vos labs
                    </p>
                    <div className="mt-4 p-4 bg-primary/10 border border-primary/20 rounded-lg inline-flex items-center gap-2">
                        <Coins className="h-5 w-5 text-primary" />
                        <span className="font-medium">Solde actuel:</span>
                        <span className="text-2xl font-bold text-primary">{balance} Tokens</span>
                    </div>
                </div>

                <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    {packages.map((pkg) => (
                        <Card key={pkg.id} className="relative overflow-hidden hover:shadow-lg transition-shadow">
                            <CardHeader>
                                <div className="flex items-start justify-between">
                                    <div className="flex items-center gap-3">
                                        {pkg.icon_svg ? (
                                            <div
                                                className="w-12 h-12 flex items-center justify-center"
                                                dangerouslySetInnerHTML={{ __html: pkg.icon_svg }}
                                            />
                                        ) : (
                                            <div className="w-12 h-12 bg-gradient-to-br from-yellow-400 to-orange-500 rounded-lg flex items-center justify-center">
                                                <Coins className="w-8 h-8 text-white" />
                                            </div>
                                        )}
                                        <div>
                                            <CardTitle className="text-xl">{pkg.name}</CardTitle>
                                            <CardDescription className="text-lg font-semibold text-primary">
                                                {pkg.tokens} Tokens
                                            </CardDescription>
                                        </div>
                                    </div>
                                </div>
                            </CardHeader>
                            <CardContent>
                                <div className="space-y-4">
                                    <div>
                                        <p className="text-3xl font-bold text-primary">
                                            {formatPrice(pkg.price_cents, pkg.currency)}
                                        </p>
                                        {pkg.description && (
                                            <p className="text-sm text-muted-foreground mt-2">
                                                {pkg.description}
                                            </p>
                                        )}
                                    </div>

                                    <div className="space-y-2 text-sm">
                                        <div className="flex items-center gap-2 text-muted-foreground">
                                            <Check className="h-4 w-4 text-green-600" />
                                            <span>Paiement sécurisé</span>
                                        </div>
                                        <div className="flex items-center gap-2 text-muted-foreground">
                                            <Check className="h-4 w-4 text-green-600" />
                                            <span>Tokens crédités instantanément</span>
                                        </div>
                                    </div>

                                    <Button
                                        className="w-full"
                                        onClick={() => handleBuyPackage(pkg.id)}
                                        disabled={processing}
                                    >
                                        {processing ? 'Traitement...' : 'Acheter maintenant'}
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>
                    ))}
                </div>

                {packages.length === 0 && (
                    <Card>
                        <CardContent className="py-12 text-center">
                            <Coins className="h-16 w-16 mx-auto text-muted-foreground mb-4" />
                            <p className="text-lg font-medium">Aucun package disponible</p>
                            <p className="text-sm text-muted-foreground mt-2">
                                Les packages de tokens seront bientôt disponibles.
                            </p>
                        </CardContent>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}
