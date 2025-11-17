import AuthenticatedSessionController from '@/actions/App/Http/Controllers/Auth/AuthenticatedSessionController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/layouts/auth-layout';
import { Form, Head } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import {Card,CardContent} from "@/components/ui/card";
import {toast} from "sonner";
import {  useEffect } from 'react';


interface LoginProps {
    status?: string;
    error?:string;
}

export default function Login({ status, error }: LoginProps) {

    useEffect(() => {
        if (status) {
            toast.error(status);
        }
    },[status]);

    return (
        <AuthLayout title="Connexion" description="Connectez-vous à votre compte pour accéder à la plateforme">
            <Head title="Connexion" />

            <Card>
                <CardContent>
                    <Form {...AuthenticatedSessionController.store.form()} resetOnSuccess={['password']} className="flex flex-col gap-6 ">
                        {({ processing, errors }) => (
                            <>
                                <div className="grid gap-6">
                                    <div className="grid gap-2">
                                        <Label htmlFor="email">Email ou nom d'utilisateur</Label>
                                        <Input
                                            id="email"
                                            type="text"
                                            name="email"
                                            required
                                            autoFocus
                                            tabIndex={1}
                                            autoComplete="email"
                                            placeholder="email@example.com ou nom d'utilisateur"
                                        />
                                        <InputError message={errors.email} />
                                    </div>
                                    <div className="grid gap-2">
                                        <div className="flex items-center justify-between">
                                            <Label htmlFor="password">Mot de passe</Label>
                                            <a href="/forgot-password" className="text-sm text-primary hover:underline">
                                                Mot de passe oublié ?
                                            </a>
                                        </div>
                                        <Input
                                            id="password"
                                            type="password"
                                            name="password"
                                            required
                                            tabIndex={2}
                                            autoComplete="current-password"
                                            placeholder="••••••••"
                                        />
                                        <InputError message={errors.password} />
                                    </div>
                                    <div className="flex items-center space-x-3">
                                        <Checkbox id="remember" name="remember" tabIndex={3} />
                                        <Label htmlFor="remember" className="font-normal">Se souvenir de moi</Label>
                                    </div>

                                    <Button type="submit"
                                        className="mt-4 w-full" tabIndex={4} disabled={processing} data-test="login-button">
                                        {processing && <LoaderCircle className="h-4 w-4 animate-spin mr-2" />}
                                        Se connecter
                                    </Button>
                                </div>
                                <div className="text-center text-sm text-muted-foreground">
                                    {error && <p className="text-destructive mb-2">{error}</p>}
                                    Vous n'avez pas de compte ?{' '}
                                    <a href="/register" className="text-primary hover:underline font-medium">
                                        Créer un compte
                                    </a>
                                </div>
                            </>
                        )}
                    </Form>
                </CardContent>
            </Card>
        </AuthLayout>
    );
}
