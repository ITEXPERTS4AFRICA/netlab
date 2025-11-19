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
    connection_error?: boolean;
    is_timeout?: boolean;
    suggestions?: string[];
    debug?: {
        base_url?: string;
        username?: string;
        password_provided?: boolean;
        url_used?: string;
        error_type?: string;
    };
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
                credentials: 'include', // Important : inclure les cookies de session
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
                            
                            // Si c'est une erreur 403, afficher plus de détails
                            if (response.status === 403) {
                                const details: Record<string, unknown> = {
                                    message: errorMessage,
                                    user_role: errorData.user_role,
                                };
                                
                                setTestResult({
                                    success: false,
                                    message: errorMessage + (errorData.user_role ? ` (Rôle actuel: ${errorData.user_role})` : ''),
                                    details: details,
                                });
                                setIsTesting(false);
                                return;
                            }
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
                        
                        // Extraire les suggestions et connection_error depuis errorData
                        const testResult: TestResult = {
                            success: false,
                            message: errorMessage,
                            details: errorData.details || errorData,
                            debug: errorData.debug,
                        };
                        
                        if (errorData.connection_error) {
                            testResult.connection_error = errorData.connection_error;
                        }
                        if (errorData.suggestions) {
                            testResult.suggestions = errorData.suggestions;
                        } else if (errorData.details && typeof errorData.details === 'object' && 'suggestions' in errorData.details) {
                            testResult.suggestions = (errorData.details as { suggestions?: string[] }).suggestions;
                        }
                        if (errorData.is_timeout) {
                            testResult.is_timeout = errorData.is_timeout;
                        }
                        
                        setTestResult(testResult);
                        setIsTesting(false);
                        return;
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
            
            // Extraire les suggestions et connection_error depuis details si elles existent
            if (result.details && typeof result.details === 'object') {
                const details = result.details as Record<string, unknown>;
                if ('suggestions' in details && Array.isArray(details.suggestions)) {
                    result.suggestions = details.suggestions as string[];
                }
                if (!result.connection_error && 'connection_error' in details) {
                    result.connection_error = details.connection_error as boolean;
                }
                if (!result.is_timeout && 'is_timeout' in details) {
                    result.is_timeout = details.is_timeout as boolean;
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
                                    
                                    {/* Suggestions de dépannage pour les erreurs de connexion */}
                                    {!testResult.success && testResult.connection_error && testResult.suggestions && (
                                        <div className="mt-2 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-md border border-blue-200 dark:border-blue-800">
                                            <p className="text-sm font-medium text-blue-900 dark:text-blue-100 mb-2">
                                                💡 Suggestions de dépannage :
                                            </p>
                                            <ul className="text-xs text-blue-800 dark:text-blue-200 space-y-1 list-disc list-inside">
                                                {testResult.suggestions.map((suggestion, index) => (
                                                    <li key={index}>{suggestion}</li>
                                                ))}
                                            </ul>
                                        </div>
                                    )}

                                    {/* Détails d'erreur */}
                                    {!testResult.success && testResult.details && (() => {
                                        const errorMessage = typeof testResult.details.message === 'string' ? testResult.details.message : null;
                                        const errorUrl = typeof testResult.details.url_used === 'string' ? testResult.details.url_used : null;
                                        
                                        return (
                                            <div className="mt-2 p-3 bg-red-100 dark:bg-red-900/20 rounded border border-red-200 dark:border-red-800 text-sm">
                                                <div className="font-medium mb-2 text-red-800 dark:text-red-200">Détails de l'erreur :</div>
                                                {errorMessage && (
                                                    <div className="mb-2">
                                                        <strong className="text-xs">Message technique :</strong>
                                                        <code className="block mt-1 bg-red-200 dark:bg-red-800 p-2 rounded break-all text-xs">
                                                            {errorMessage}
                                                        </code>
                                                    </div>
                                                )}
                                                {errorUrl && (
                                                    <div className="mb-2">
                                                        <strong className="text-xs">URL utilisée :</strong>
                                                        <code className="block mt-1 bg-red-200 dark:bg-red-800 p-2 rounded break-all text-xs">
                                                            {errorUrl}
                                                        </code>
                                                    </div>
                                                )}
                                                <pre className="text-xs overflow-auto text-red-700 dark:text-red-300 max-h-60">
                                                    {JSON.stringify(testResult.details, null, 2)}
                                                </pre>
                                            </div>
                                        );
                                    })()}

                                    {/* Informations de débogage (mode local uniquement) */}
                                    {!testResult.success && testResult.debug && (
                                        <div className="mt-2 p-3 bg-amber-100 dark:bg-amber-900/20 rounded border border-amber-200 dark:border-amber-800 text-sm">
                                            <div className="font-medium mb-2 text-amber-800 dark:text-amber-200">Informations de débogage :</div>
                                            <ul className="space-y-1 text-xs text-amber-700 dark:text-amber-300">
                                                {testResult.debug.base_url && (
                                                    <li><strong>URL de base :</strong> {testResult.debug.base_url}</li>
                                                )}
                                                {testResult.debug.username && (
                                                    <li><strong>Utilisateur :</strong> {testResult.debug.username}</li>
                                                )}
                                                {testResult.debug.password_provided !== undefined && (
                                                    <li><strong>Mot de passe fourni :</strong> {testResult.debug.password_provided ? 'Oui' : 'Non'}</li>
                                                )}
                                                {testResult.debug.url_used && (
                                                    <li><strong>URL utilisée :</strong> <code className="bg-amber-200 dark:bg-amber-800 px-1 rounded">{testResult.debug.url_used}</code></li>
                                                )}
                                            </ul>
                                        </div>
                                    )}

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

