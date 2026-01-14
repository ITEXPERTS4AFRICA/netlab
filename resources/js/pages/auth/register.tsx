import { login } from '@/routes';
import { Form, Head } from '@inertiajs/react';
import RegisteredUserController from '@/actions/App/Http/Controllers/Auth/RegisteredUserController';
import { LoaderCircle } from 'lucide-react';
import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AuthLayout from '@/layouts/auth-layout';
import { useState } from 'react';

export default function Register() {
    const [showOptional, setShowOptional] = useState(false);

    return (
        <AuthLayout title="Créer un compte" description="Inscrivez-vous pour accéder à la plateforme de formation">
            <Head title="Inscription" />
            <Card>
                <CardContent className="pt-6">
                    <Form
                        {...RegisteredUserController.store()}
                        resetOnSuccess={['password', 'password_confirmation']}
                        disableWhileProcessing
                        className="flex flex-col gap-6"
                    >
                        {({ processing, errors }) => (
                            <>
                                <input type="hidden" name="_method" value="post" />
                                <div className="space-y-4">
                                    <div>
                                        <h3 className="mb-2 text-lg font-semibold">Informations de base</h3>
                                        <p className="mb-4 text-sm text-muted-foreground">Les champs marqués d'un * sont obligatoires</p>
                                    </div>

                                    <div className="grid gap-4">
                                        <div className="grid gap-2">
                                            <Label htmlFor="name">Nom complet *</Label>
                                            <Input
                                                id="name"
                                                type="text"
                                                required
                                                autoFocus
                                                tabIndex={1}
                                                autoComplete="name"
                                                name="name"
                                                placeholder="Jean Dupont"
                                            />
                                            <InputError message={errors.name} className="mt-1" />
                                        </div>

                                        <div className="grid gap-2">
                                            <Label htmlFor="email">Adresse email *</Label>
                                            <Input
                                                id="email"
                                                type="email"
                                                required
                                                tabIndex={2}
                                                autoComplete="email"
                                                name="email"
                                                placeholder="jean.dupont@example.com"
                                            />
                                            <InputError message={errors.email} className="mt-1" />
                                        </div>

                                        <div className="grid gap-2">
                                            <Label htmlFor="password">Mot de passe *</Label>
                                            <Input
                                                id="password"
                                                type="password"
                                                required
                                                tabIndex={3}
                                                autoComplete="new-password"
                                                name="password"
                                                placeholder="••••••••"
                                            />
                                            <p className="text-xs text-muted-foreground">Minimum 8 caractères</p>
                                            <InputError message={errors.password} className="mt-1" />
                                        </div>

                                        <div className="grid gap-2">
                                            <Label htmlFor="password_confirmation">Confirmer le mot de passe *</Label>
                                            <Input
                                                id="password_confirmation"
                                                type="password"
                                                required
                                                tabIndex={4}
                                                autoComplete="new-password"
                                                name="password_confirmation"
                                                placeholder="••••••••"
                                            />
                                            <InputError message={errors.password_confirmation} className="mt-1" />
                                        </div>

                                        <div className="grid gap-2">
                                            <Label htmlFor="phone">Téléphone N° Whatsapp</Label>
                                            <Input
                                                id="phone"
                                                type="tel"
                                                tabIndex={6}
                                                autoComplete="tel"
                                                name="phone"
                                                placeholder="+225 6 12 34 56 78"
                                            />
                                            <InputError message={errors.phone} className="mt-1" />
                                        </div>

                                        <div className="grid gap-2">
                                            <Label htmlFor="role">Type de compte</Label>
                                            <Select name="role" defaultValue="student">
                                                <SelectTrigger tabIndex={5}>
                                                    <SelectValue placeholder="Sélectionner un type" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="student">Étudiant</SelectItem>
                                                    <SelectItem value="instructor">Instructeur</SelectItem>
                                                    <SelectItem value="user">Utilisateur</SelectItem>
                                                </SelectContent>
                                            </Select>
                                            <p className="text-xs text-muted-foreground">Sélectionnez votre rôle principal</p>
                                            <InputError message={errors.role} className="mt-1" />
                                        </div>
                                    </div>
                                </div>

                                <div className="space-y-4">
                                    <button
                                        type="button"
                                        onClick={() => setShowOptional(!showOptional)}
                                        className="text-sm text-primary hover:underline"
                                    >
                                        {showOptional ? 'Masquer' : 'Afficher'} les informations optionnelles
                                    </button>

                                    {showOptional && (
                                        <div className="grid gap-4 border-t pt-4">
                                            <div className="grid gap-2">
                                                <Label htmlFor="organization">Organisation</Label>
                                                <Input
                                                    id="organization"
                                                    type="text"
                                                    tabIndex={7}
                                                    autoComplete="organization"
                                                    name="organization"
                                                    placeholder="Nom de votre organisation"
                                                />
                                                <InputError message={errors.organization} className="mt-1" />
                                            </div>

                                            <div className="grid gap-2">
                                                <Label htmlFor="department">Département</Label>
                                                <Input id="department" type="text" tabIndex={8} name="department" placeholder="Votre département" />
                                                <InputError message={errors.department} className="mt-1" />
                                            </div>

                                            <div className="grid gap-2">
                                                <Label htmlFor="position">Poste</Label>
                                                <Input id="position" type="text" tabIndex={9} name="position" placeholder="Votre poste" />
                                                <InputError message={errors.position} className="mt-1" />
                                            </div>
                                        </div>
                                    )}
                                </div>

                                <Button type="submit" className="w-full" tabIndex={10} disabled={processing} data-test="register-user-button">
                                    {processing && <LoaderCircle className="mr-2 h-4 w-4 animate-spin" />}
                                    Créer mon compte
                                </Button>

                                <div className="text-center text-sm text-muted-foreground">
                                    Vous avez déjà un compte ?{' '}
                                    <TextLink href={login()} tabIndex={11}>
                                        Se connecter
                                    </TextLink>
                                </div>
                            </>
                        )}
                    </Form>
                </CardContent>
            </Card>
        </AuthLayout>
    );
}
