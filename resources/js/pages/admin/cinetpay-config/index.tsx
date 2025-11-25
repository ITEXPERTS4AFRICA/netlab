import { Head, useForm } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Alert, AlertDescription } from '@/components/ui/alert';
import {
    Settings, Save, TestTube, CheckCircle2, XCircle, LoaderCircle,
    CreditCard, Key, Globe, AlertCircle
} from 'lucide-react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

interface CinetPayConfig {
    api_key: string;
    secret_key: string | null;
    site_id: string;
    api_url: string;
    notify_url: string;
    return_url: string;
    cancel_url: string;
    mode: string;
}

interface Props {
    config: CinetPayConfig;
}

interface TestResult {
    success: boolean;
    message: string;
    health?: {
        status?: string;
        overall_health?: string;
        configuration?: Record<string, string>;
        connectivity?: Record<string, unknown>;
        api_status?: Record<string, unknown>;
    };
    details?: Record<string, unknown>;
}

export default function CinetPayConfigIndex({ config }: Props) {
    const [testResult, setTestResult] = useState<TestResult | null>(null);
    const [isTesting, setIsTesting] = useState(false);

    const { data, setData, put, processing, errors, recentlySuccessful } = useForm({
        api_key: config.api_key || '',
        secret_key: config.secret_key === '••••••••' ? '' : (config.secret_key || ''),
        site_id: config.site_id || '',
        api_url: config.api_url || 'https://api-checkout.cinetpay.com',
        notify_url: config.notify_url || '',
        return_url: config.return_url || '',
        cancel_url: config.cancel_url || '',
        mode: config.mode || 'sandbox',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        put('/admin/cinetpay-config', {
            preserveScroll: true,
        });
    };

    const getCsrfToken = () => {
        const element = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]');
        return element?.content || '';
    };

    const testConnection = async () => {
        setIsTesting(true);
        setTestResult(null);

        const csrfToken = getCsrfToken();
        if (!csrfToken) {
            setTestResult({
                success: false,
                message: 'Token CSRF introuvable. Veuillez rafraîchir la page.',
            });
            setIsTesting(false);
            return;
        }

        try {
            const response = await fetch('/admin/cinetpay-config/test', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'include',
                body: JSON.stringify({
                    api_key: data.api_key,
                    secret_key: data.secret_key,
                    site_id: data.site_id,
                    api_url: data.api_url,
                    notify_url: data.notify_url,
                    return_url: data.return_url,
                    cancel_url: data.cancel_url,
                    mode: data.mode,
                }),
            });

            let result: TestResult;
            
            if (!response.ok) {
                // Essayer de parser le JSON même si le statut n'est pas OK
                const contentType = response.headers.get('content-type');
                if (contentType && contentType.includes('application/json')) {
                    try {
                        result = await response.json();
                        // Si on a un JSON valide, l'utiliser même avec un statut d'erreur
                        if (result.message) {
                            setTestResult(result);
                            setIsTesting(false);
                            return;
                        }
                    } catch (e) {
                        // Si le JSON est invalide, continuer avec l'erreur
                    }
                }
                const text = await response.text();
                throw new Error(`Réponse non-JSON reçue (${response.status}): ${text.substring(0, 200)}`);
            }

            result = await response.json();
            
            // Extraire les suggestions et connection_error depuis details si elles existent
            if (result.details && typeof result.details === 'object') {
                const details = result.details as Record<string, unknown>;
                if ('suggestions' in details && Array.isArray(details.suggestions)) {
                    (result as unknown as { suggestions?: string[] }).suggestions = details.suggestions as string[];
                }
            }
            
            setTestResult(result);
        } catch (error) {
            setTestResult({
                success: false,
                message: 'Erreur lors du test de connexion : ' + (error instanceof Error ? error.message : 'Erreur inconnue'),
            });
        } finally {
            setIsTesting(false);
        }
    };

    return (
        <AppLayout>
            <Head title="Configuration CinetPay" />

            <div className="container mx-auto py-8 max-w-4xl space-y-6">
                {/* En-tête */}
                <div>
                    <h1 className="text-3xl font-bold flex items-center gap-2">
                        <CreditCard className="h-8 w-8" />
                        Configuration CinetPay
                    </h1>
                    <p className="text-muted-foreground mt-1">
                        Gérez les credentials et la configuration de l'API de paiement CinetPay
                    </p>
                </div>

                {/* Résultat du test */}
                {testResult && (
                    <Alert
                        className={
                            (testResult.success
                                ? 'border-green-500 bg-green-50 dark:bg-green-950'
                                : 'border-red-500 bg-red-50 dark:bg-red-950'
                            ) + " flex w-full"
                        }
                    >
                        <div className="flex items-start gap-3 flex-1 w-full">
                            {testResult.success ? (
                                <CheckCircle2 className="h-5 w-5 text-green-600 mt-0.5" />
                            ) : (
                                <XCircle className="h-5 w-5 text-red-600 mt-0.5" />
                            )}
                            <div className="flex-1 w-full flex flex-col">
                                <AlertDescription className="flex flex-col flex-1 w-full">
                                    <div className="font-semibold mb-1">{testResult.message}</div>
                                    
                                    {/* Détails de santé */}
                                    {testResult.health && (
                                        <div className="mt-2 space-y-2">
                                            {testResult.health.configuration && (
                                                <div className="p-3 bg-blue-50 dark:bg-blue-900/20 rounded-md border border-blue-200 dark:border-blue-800">
                                                    <div className="text-sm font-medium text-blue-900 dark:text-blue-100 mb-2">
                                                        Configuration :
                                                    </div>
                                                    <ul className="text-xs text-blue-800 dark:text-blue-200 space-y-1">
                                                        {Object.entries(testResult.health.configuration).map(([key, value]) => (
                                                            <li key={key} className="flex justify-between">
                                                                <span className="font-medium">{key}:</span>
                                                                <span>{value}</span>
                                                            </li>
                                                        ))}
                                                    </ul>
                                                </div>
                                            )}
                                            
                                            {testResult.health.connectivity && (
                                                <div className="p-3 bg-green-50 dark:bg-green-900/20 rounded-md border border-green-200 dark:border-green-800">
                                                    <div className="text-sm font-medium text-green-900 dark:text-green-100 mb-2">
                                                        Connectivité :
                                                    </div>
                                                    <ul className="text-xs text-green-800 dark:text-green-200 space-y-1">
                                                        {Object.entries(testResult.health.connectivity).map(([key, value]) => (
                                                            <li key={key} className="flex justify-between">
                                                                <span className="font-medium">{key}:</span>
                                                                <span>{String(value)}</span>
                                                            </li>
                                                        ))}
                                                    </ul>
                                                </div>
                                            )}
                                        </div>
                                    )}

                                    {/* Détails d'erreur */}
                                    {!testResult.success && testResult.details && (
                                        <div className="mt-2 p-3 bg-red-100 dark:bg-red-900/20 rounded border border-red-200 dark:border-red-800 text-sm">
                                            <div className="font-medium mb-2 text-red-800 dark:text-red-200">Détails de l'erreur :</div>
                                            <pre className="text-xs overflow-auto text-red-700 dark:text-red-300 max-h-60">
                                                {JSON.stringify(testResult.details, null, 2)}
                                            </pre>
                                        </div>
                                    )}
                                </AlertDescription>
                            </div>
                        </div>
                    </Alert>
                )}

                {/* Formulaire de configuration */}
                <form onSubmit={submit} className="space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Key className="h-5 w-5" />
                                Credentials CinetPay
                            </CardTitle>
                            <CardDescription>
                                Identifiants requis pour l'authentification auprès de l'API CinetPay
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="space-y-2">
                                <Label htmlFor="api_key">Clé API (API Key) *</Label>
                                <Input
                                    id="api_key"
                                    type="text"
                                    value={data.api_key}
                                    onChange={(e) => setData('api_key', e.target.value)}
                                    placeholder="447088687629111c58c3573.70152188"
                                    required
                                />
                                <InputError message={errors.api_key} />
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="secret_key">Clé Secrète (Secret Key)</Label>
                                <Input
                                    id="secret_key"
                                    type="password"
                                    value={data.secret_key}
                                    onChange={(e) => setData('secret_key', e.target.value)}
                                    placeholder="Laissez vide pour conserver la valeur actuelle"
                                />
                                <InputError message={errors.secret_key} />
                                <p className="text-xs text-muted-foreground">
                                    Laissez vide si vous ne souhaitez pas modifier la clé secrète actuelle
                                </p>
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="site_id">ID du Site (Site ID) *</Label>
                                <Input
                                    id="site_id"
                                    type="text"
                                    value={data.site_id}
                                    onChange={(e) => setData('site_id', e.target.value)}
                                    placeholder="911501"
                                    required
                                />
                                <InputError message={errors.site_id} />
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="mode">Mode *</Label>
                                <Select
                                    value={data.mode}
                                    onValueChange={(value) => setData('mode', value)}
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Sélectionner le mode" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="sandbox">Sandbox (Test)</SelectItem>
                                        <SelectItem value="production">Production</SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.mode} />
                                <p className="text-xs text-muted-foreground">
                                    Utilisez "Sandbox" pour les tests et "Production" pour les paiements réels
                                </p>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Globe className="h-5 w-5" />
                                URLs de Configuration
                            </CardTitle>
                            <CardDescription>
                                URLs de callback et de redirection pour les paiements
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="space-y-2">
                                <Label htmlFor="api_url">URL de l'API *</Label>
                                <Input
                                    id="api_url"
                                    type="url"
                                    value={data.api_url}
                                    onChange={(e) => setData('api_url', e.target.value)}
                                    placeholder="https://api-checkout.cinetpay.com"
                                    required
                                />
                                <InputError message={errors.api_url} />
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="notify_url">URL de Notification (Webhook) *</Label>
                                <Input
                                    id="notify_url"
                                    type="url"
                                    value={data.notify_url}
                                    onChange={(e) => setData('notify_url', e.target.value)}
                                    placeholder="http://10.10.10.20/api/payments/cinetpay/webhook"
                                    required
                                />
                                <InputError message={errors.notify_url} />
                                <p className="text-xs text-muted-foreground">
                                    URL appelée par CinetPay pour notifier les changements de statut de paiement
                                </p>
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="return_url">URL de Retour *</Label>
                                <Input
                                    id="return_url"
                                    type="url"
                                    value={data.return_url}
                                    onChange={(e) => setData('return_url', e.target.value)}
                                    placeholder="http://10.10.10.20/api/payments/return"
                                    required
                                />
                                <InputError message={errors.return_url} />
                                <p className="text-xs text-muted-foreground">
                                    URL de redirection après un paiement réussi
                                </p>
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="cancel_url">URL d'Annulation *</Label>
                                <Input
                                    id="cancel_url"
                                    type="url"
                                    value={data.cancel_url}
                                    onChange={(e) => setData('cancel_url', e.target.value)}
                                    placeholder="http://10.10.10.20/api/payments/cancel"
                                    required
                                />
                                <InputError message={errors.cancel_url} />
                                <p className="text-xs text-muted-foreground">
                                    URL de redirection après une annulation de paiement
                                </p>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Actions */}
                    <div className="flex items-center justify-between gap-4">
                        <div className="flex items-center gap-2">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={testConnection}
                                disabled={isTesting || processing}
                            >
                                {isTesting ? (
                                    <>
                                        <LoaderCircle className="h-4 w-4 mr-2 animate-spin" />
                                        Test en cours...
                                    </>
                                ) : (
                                    <>
                                        <TestTube className="h-4 w-4 mr-2" />
                                        Tester la connexion
                                    </>
                                )}
                            </Button>
                        </div>

                        <div className="flex items-center gap-2">
                            {recentlySuccessful && (
                                <Badge variant="outline" className="bg-green-50 text-green-700 border-green-200">
                                    <CheckCircle2 className="h-3 w-3 mr-1" />
                                    Configuration sauvegardée
                                </Badge>
                            )}
                            <Button type="submit" disabled={processing}>
                                {processing ? (
                                    <>
                                        <LoaderCircle className="h-4 w-4 mr-2 animate-spin" />
                                        Enregistrement...
                                    </>
                                ) : (
                                    <>
                                        <Save className="h-4 w-4 mr-2" />
                                        Enregistrer
                                    </>
                                )}
                            </Button>
                        </div>
                    </div>
                </form>

                {/* Aide */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <AlertCircle className="h-5 w-5" />
                            Informations importantes
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-2 text-sm text-muted-foreground">
                        <p>
                            • Les credentials sont stockés de manière sécurisée dans la base de données et cryptés si nécessaire.
                        </p>
                        <p>
                            • Les URLs de callback doivent être accessibles depuis Internet pour que CinetPay puisse les appeler.
                        </p>
                        <p>
                            • En production, utilisez des URLs HTTPS pour garantir la sécurité des transactions.
                        </p>
                        <p>
                            • La clé secrète est cryptée dans la base de données. Laissez le champ vide si vous ne souhaitez pas la modifier.
                        </p>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}

