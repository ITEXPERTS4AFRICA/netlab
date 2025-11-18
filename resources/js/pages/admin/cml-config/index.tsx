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
    Server, User, Lock, AlertCircle, Copy, Check
} from 'lucide-react';
import { useState } from 'react';
import InputError from '@/components/input-error';

interface CmlConfig {
    base_url: string;
    username: string;
    password: string | null;
    default_username: string;
    default_password: string | null;
}

interface Props {
    config: CmlConfig;
}

interface TestResult {
    success: boolean;
    message: string;
    token?: string;
    labs_count?: number;
    details?: Record<string, unknown>;
}

export default function CmlConfigIndex({ config }: Props) {
    const [testResult, setTestResult] = useState<TestResult | null>(null);
    const [isTesting, setIsTesting] = useState(false);
    const [copiedToken, setCopiedToken] = useState(false);

    const { data, setData, put, processing, errors, recentlySuccessful } = useForm({
        base_url: config.base_url || '',
        username: config.username || '',
        password: config.password === '••••••••' ? '' : (config.password || ''),
        default_username: config.default_username || 'cheick',
        default_password: config.default_password === '••••••••' ? '' : (config.default_password || ''),
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        put('/admin/cml-config', {
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
            const response = await fetch('/admin/cml-config/test', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    base_url: data.base_url,
                    username: data.username,
                    password: data.password,
                }),
            });

            // Vérifier le Content-Type avant de lire le body
            const contentType = response.headers.get('content-type') || '';
            const isJson = contentType.includes('application/json');

            // Gérer les erreurs HTTP
            if (!response.ok) {
                let errorMessage = '';

                // Si c'est une erreur 419 (CSRF token mismatch) ou 403
                if (response.status === 419 || response.status === 403) {
                    if (isJson) {
                        try {
                            const errorData = await response.json();
                            errorMessage = errorData.message || `Erreur d'authentification (${response.status}). Veuillez rafraîchir la page.`;
                        } catch {
                            errorMessage = `Erreur d'authentification (${response.status}). Veuillez rafraîchir la page.`;
                        }
                    } else {
                        const text = await response.text();
                        errorMessage = `Erreur d'authentification (${response.status}): ${text.substring(0, 200)}`;
                    }
                    throw new Error(errorMessage);
                }

                // Pour les autres erreurs, essayer de parser le JSON
                if (isJson) {
                    try {
                        const errorData = await response.json();
                        errorMessage = errorData.message || `Erreur ${response.status}`;
                    } catch {
                        errorMessage = `Erreur ${response.status}`;
                    }
                } else {
                    const text = await response.text();
                    errorMessage = `Erreur ${response.status}: ${text.substring(0, 200)}`;
                }
                throw new Error(errorMessage);
            }

            // Si la réponse est OK, parser le JSON
            if (!isJson) {
                const text = await response.text();
                throw new Error(`Réponse non-JSON reçue (${response.status}): ${text.substring(0, 200)}`);
            }

            const result: TestResult = await response.json();
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
            <Head title="Configuration CML" />

            <div className="container mx-auto py-8 max-w-4xl space-y-6">
                {/* En-tête */}
                <div>
                    <h1 className="text-3xl font-bold flex items-center gap-2">
                        <Settings className="h-8 w-8" />
                        Configuration CML
                    </h1>
                    <p className="text-muted-foreground mt-1">
                        Gérez les accès et la configuration de Cisco Modeling Labs
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
                                    {testResult.success && (
                                        <div className="text-sm space-y-2 mt-2 flex flex-col flex-1">
                                            {testResult.token && (
                                                <div className="space-y-1 flex flex-col flex-1">
                                                    <div className="flex items-center justify-between gap-2">
                                                        <span className="font-medium">Token CML:</span>
                                                        <Button
                                                            variant="outline"
                                                            size="sm"
                                                            onClick={async () => {
                                                                try {
                                                                    await navigator.clipboard.writeText(testResult.token || '');
                                                                    setCopiedToken(true);
                                                                    setTimeout(() => setCopiedToken(false), 2000);
                                                                } catch (err) {
                                                                    console.error('Erreur lors de la copie:', err);
                                                                }
                                                            }}
                                                            className="h-7 text-xs"
                                                        >
                                                            {copiedToken ? (
                                                                <>
                                                                    <Check className="h-3 w-3 mr-1" />
                                                                    Copié
                                                                </>
                                                            ) : (
                                                                <>
                                                                    <Copy className="h-3 w-3 mr-1" />
                                                                    Copier
                                                                </>
                                                            )}
                                                        </Button>
                                                    </div>
                                                    <div className="flex items-center gap-2 p-2 bg-muted rounded-md w-full">
                                                        <code className="text-xs flex-1 break-all font-mono w-full">
                                                            {testResult.token}
                                                        </code>
                                                    </div>
                                                </div>
                                            )}
                                            {testResult.labs_count !== undefined && (
                                                <div className="flex items-center gap-2">
                                                    <span>Labs disponibles:</span>
                                                    <Badge variant="secondary">{testResult.labs_count}</Badge>
                                                </div>
                                            )}
                                        </div>
                                    )}
                                    {!testResult.success && testResult.details && (
                                        <details className="mt-2 text-sm">
                                            <summary className="cursor-pointer text-xs text-muted-foreground">Détails de l'erreur</summary>
                                            <pre className="mt-2 text-xs bg-white dark:bg-gray-800 p-2 rounded overflow-auto">
                                                {JSON.stringify(testResult.details, null, 2)}
                                            </pre>
                                        </details>
                                    )}
                                </AlertDescription>
                            </div>
                        </div>
                    </Alert>
                )}

                {/* Formulaire de configuration */}
                <Card>
                    <CardHeader>
                        <CardTitle>Paramètres de connexion CML</CardTitle>
                        <CardDescription>
                            Configurez l'URL de base et les identifiants pour accéder à l'API Cisco Modeling Labs
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={submit} className="space-y-6">
                            {/* URL de base */}
                            <div className="grid gap-2">
                                <Label htmlFor="base_url" className="flex items-center gap-2">
                                    <Server className="h-4 w-4" />
                                    URL de base de l'API CML
                                </Label>
                                <Input
                                    id="base_url"
                                    type="url"
                                    value={data.base_url}
                                    onChange={(e) => setData('base_url', e.target.value)}
                                    placeholder="https://54.38.146.213"
                                    required
                                />
                                <p className="text-xs text-muted-foreground">
                                    URL complète du serveur CML (sans /api à la fin)
                                </p>
                                <InputError message={errors.base_url} />
                            </div>

                            {/* Nom d'utilisateur */}
                            <div className="grid gap-2">
                                <Label htmlFor="username" className="flex items-center gap-2">
                                    <User className="h-4 w-4" />
                                    Nom d'utilisateur CML
                                </Label>
                                <Input
                                    id="username"
                                    type="text"
                                    value={data.username}
                                    onChange={(e) => setData('username', e.target.value)}
                                    placeholder="votre_username"
                                    required
                                />
                                <InputError message={errors.username} />
                            </div>

                            {/* Mot de passe */}
                            <div className="grid gap-2">
                                <Label htmlFor="password" className="flex items-center gap-2">
                                    <Lock className="h-4 w-4" />
                                    Mot de passe CML
                                </Label>
                                <Input
                                    id="password"
                                    type="password"
                                    value={data.password}
                                    onChange={(e) => setData('password', e.target.value)}
                                    placeholder={config.password === '••••••••' ? "Laissez vide pour ne pas modifier" : "••••••••"}
                                />
                                <p className="text-xs text-muted-foreground">
                                    {config.password === '••••••••' && (
                                        <span className="flex items-center gap-1 text-amber-600">
                                            <AlertCircle className="h-3 w-3" />
                                            Un mot de passe est déjà configuré. Laissez vide pour ne pas le modifier, ou entrez un nouveau mot de passe.
                                        </span>
                                    )}
                                </p>
                                <InputError message={errors.password} />
                            </div>

                            {/* Séparateur */}
                            <div className="border-t pt-6 mt-6">
                                <h3 className="text-lg font-semibold mb-4 flex items-center gap-2">
                                    <Settings className="h-5 w-5" />
                                    Credentials par défaut (Rafraîchissement automatique)
                                </h3>
                                <p className="text-sm text-muted-foreground mb-4">
                                    Ces identifiants sont utilisés pour le rafraîchissement automatique du token CML. 
                                    Ils permettent de maintenir la disponibilité du système même en cas de changement des credentials principaux.
                                </p>
                            </div>

                            {/* Nom d'utilisateur par défaut */}
                            <div className="grid gap-2">
                                <Label htmlFor="default_username" className="flex items-center gap-2">
                                    <User className="h-4 w-4" />
                                    Nom d'utilisateur par défaut
                                </Label>
                                <Input
                                    id="default_username"
                                    type="text"
                                    value={data.default_username}
                                    onChange={(e) => setData('default_username', e.target.value)}
                                    placeholder="cheick"
                                    required
                                />
                                <p className="text-xs text-muted-foreground">
                                    Utilisé pour le rafraîchissement automatique du token CML
                                </p>
                                <InputError message={errors.default_username} />
                            </div>

                            {/* Mot de passe par défaut */}
                            <div className="grid gap-2">
                                <Label htmlFor="default_password" className="flex items-center gap-2">
                                    <Lock className="h-4 w-4" />
                                    Mot de passe par défaut
                                </Label>
                                <Input
                                    id="default_password"
                                    type="password"
                                    value={data.default_password}
                                    onChange={(e) => setData('default_password', e.target.value)}
                                    placeholder={config.default_password === '••••••••' ? "Laissez vide pour ne pas modifier" : "••••••••"}
                                />
                                <p className="text-xs text-muted-foreground">
                                    {config.default_password === '••••••••' && (
                                        <span className="flex items-center gap-1 text-amber-600">
                                            <AlertCircle className="h-3 w-3" />
                                            Un mot de passe par défaut est déjà configuré. Laissez vide pour ne pas le modifier, ou entrez un nouveau mot de passe.
                                        </span>
                                    )}
                                    {config.default_password !== '••••••••' && (
                                        <span>Utilisé pour le rafraîchissement automatique du token CML</span>
                                    )}
                                </p>
                                <InputError message={errors.default_password} />
                            </div>

                            {/* Actions */}
                            <div className="flex items-center justify-between pt-4 border-t">
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={testConnection}
                                    disabled={isTesting || !data.base_url || !data.username || !data.password}
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

                                <div className="flex gap-2">
                                    {recentlySuccessful && (
                                        <div className="flex items-center gap-2 text-sm text-green-600">
                                            <CheckCircle2 className="h-4 w-4" />
                                            Configuration sauvegardée
                                        </div>
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
                    </CardContent>
                </Card>

                {/* Informations */}
                <Card>
                    <CardHeader>
                        <CardTitle>Informations</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-2 text-sm text-muted-foreground">
                        <p>
                            • La configuration est sauvegardée dans la base de données et synchronisée avec le fichier <code className="bg-muted px-1 py-0.5 rounded">.env</code>
                        </p>
                        <p>
                            • Après modification, la configuration est automatiquement rechargée
                        </p>
                        <p>
                            • Utilisez le bouton "Tester la connexion" pour vérifier que les identifiants sont corrects
                        </p>
                        <p>
                            • Les <strong>credentials par défaut</strong> sont utilisés pour le rafraîchissement automatique du token CML
                        </p>
                        <p>
                            • Le système rafraîchit automatiquement le token si nécessaire en utilisant les credentials par défaut
                        </p>
                        <p>
                            • Les utilisateurs pourront voir les labs CML une fois la configuration validée
                        </p>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}

