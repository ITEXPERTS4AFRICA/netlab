import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
// import { send } from '@/routes/verification';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Transition } from '@headlessui/react';
import { Form, Head, Link, usePage } from '@inertiajs/react';

import DeleteUser from '@/components/delete-user';
import HeadingSmall from '@/components/heading-small';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { edit } from '@/routes/profile';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Profile settings',
        href: edit().url,
    },
];

export default function Profile({ mustVerifyEmail, status }: { mustVerifyEmail: boolean; status?: string }) {
    const { auth } = usePage<SharedData>().props;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Profile settings" />

            <SettingsLayout>
                <div className="space-y-6">
                    <HeadingSmall title="Profile information" description="Update your name and email address" />

                    <Form
                        {...ProfileController.update.form()}
                        options={{
                            preserveScroll: true,
                        }}
                        className="space-y-6"
                    >
                        {({ processing, recentlySuccessful, errors }) => (
                            <>
                                <div className="grid gap-2">
                                    <Label htmlFor="name">Name</Label>

                                    <Input
                                        id="name"
                                        className="mt-1 block w-full"
                                        defaultValue={auth.user.name}
                                        name="name"
                                        required
                                        autoComplete="name"
                                        placeholder="Full name"
                                    />

                                    <InputError className="mt-2" message={errors.name} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="email">Email address</Label>

                                    <Input
                                        id="email"
                                        type="email"
                                        className="mt-1 block w-full"
                                        defaultValue={auth.user.email}
                                        name="email"
                                        required
                                        autoComplete="username"
                                        placeholder="Email address"
                                    />

                                    <InputError className="mt-2" message={errors.email} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="phone">Téléphone</Label>

                                    <Input
                                        id="phone"
                                        type="tel"
                                        className="mt-1 block w-full"
                                        defaultValue={auth.user.phone || ''}
                                        name="phone"
                                        autoComplete="tel"
                                        placeholder="+33 6 12 34 56 78"
                                    />

                                    <InputError className="mt-2" message={errors.phone} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="organization">Organisation</Label>

                                    <Input
                                        id="organization"
                                        type="text"
                                        className="mt-1 block w-full"
                                        defaultValue={auth.user.organization || ''}
                                        name="organization"
                                        placeholder="Nom de l'organisation"
                                    />

                                    <InputError className="mt-2" message={errors.organization} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="department">Département</Label>

                                    <Input
                                        id="department"
                                        type="text"
                                        className="mt-1 block w-full"
                                        defaultValue={auth.user.department || ''}
                                        name="department"
                                        placeholder="Département"
                                    />

                                    <InputError className="mt-2" message={errors.department} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="position">Poste</Label>

                                    <Input
                                        id="position"
                                        type="text"
                                        className="mt-1 block w-full"
                                        defaultValue={auth.user.position || ''}
                                        name="position"
                                        placeholder="Votre poste"
                                    />

                                    <InputError className="mt-2" message={errors.position} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="bio">Biographie</Label>

                                    <textarea
                                        id="bio"
                                        name="bio"
                                        className="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                        defaultValue={auth.user.bio || ''}
                                        placeholder="Parlez-nous de vous..."
                                        rows={4}
                                    />

                                    <InputError className="mt-2" message={errors.bio} />
                                </div>

                                <Separator />

                                <div className="space-y-4">
                                    <HeadingSmall title="Métadonnées professionnelles" description="Informations supplémentaires pour votre profil" />
                                    
                                    <div className="grid gap-2">
                                        <Label htmlFor="skills">Compétences (séparées par des virgules)</Label>
                                        <Input
                                            id="skills"
                                            type="text"
                                            className="mt-1 block w-full"
                                            defaultValue={Array.isArray(auth.user.skills) ? auth.user.skills.join(', ') : auth.user.skills || ''}
                                            name="skills"
                                            placeholder="Ex: Réseaux, Sécurité, Cloud..."
                                        />
                                        <InputError className="mt-2" message={errors.skills} />
                                        <p className="text-xs text-muted-foreground">
                                            Séparez les compétences par des virgules
                                        </p>
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="certifications">Certifications (séparées par des virgules)</Label>
                                        <Input
                                            id="certifications"
                                            type="text"
                                            className="mt-1 block w-full"
                                            defaultValue={Array.isArray(auth.user.certifications) ? auth.user.certifications.join(', ') : auth.user.certifications || ''}
                                            name="certifications"
                                            placeholder="Ex: CCNA, CCNP, AWS..."
                                        />
                                        <InputError className="mt-2" message={errors.certifications} />
                                        <p className="text-xs text-muted-foreground">
                                            Séparez les certifications par des virgules
                                        </p>
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="education">Formation</Label>
                                        <textarea
                                            id="education"
                                            name="education"
                                            className="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                            defaultValue={typeof auth.user.education === 'string' ? auth.user.education : (Array.isArray(auth.user.education) ? JSON.stringify(auth.user.education) : '')}
                                            placeholder="Votre parcours académique..."
                                            rows={3}
                                        />
                                        <InputError className="mt-2" message={errors.education} />
                                    </div>
                                </div>

                                {mustVerifyEmail && auth.user.email_verified_at === null && (
                                    <div>
                                        <p className="-mt-4 text-sm text-muted-foreground">
                                            Your email address is unverified.{' '}
                                            <Link
                                                href={
                                                    // send()
                                                '#'}
                                                as="button"
                                                className="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                                            >
                                                Click here to resend the verification email.
                                            </Link>
                                        </p>

                                        {status === 'verification-link-sent' && (
                                            <div className="mt-2 text-sm font-medium text-green-600">
                                                A new verification link has been sent to your email address.
                                            </div>
                                        )}
                                    </div>
                                )}

                                <div className="flex items-center gap-4">
                                    <Button disabled={processing} data-test="update-profile-button">Save</Button>

                                    <Transition
                                        show={recentlySuccessful}
                                        enter="transition ease-in-out"
                                        enterFrom="opacity-0"
                                        leave="transition ease-in-out"
                                        leaveTo="opacity-0"
                                    >
                                        <p className="text-sm text-neutral-600">Saved</p>
                                    </Transition>
                                </div>
                            </>
                        )}
                    </Form>
                </div>

                <DeleteUser />
            </SettingsLayout>
        </AppLayout>
    );
}
