import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
// import { send } from '@/routes/verification';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Transition } from '@headlessui/react';
import { Form, Head, Link, usePage } from '@inertiajs/react';

import DeleteUser from '@/components/delete-user';
import HeadingSmall from '@/components/heading-small';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
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

type ProfileReservation = {
    id: number;
    lab_title: string;
    status: string;
    start_at?: string;
    end_at?: string;
    estimated_cents?: number | null;
    created_at?: string;
};

type ProfilePayment = {
    id: number;
    status: string;
    amount: number;
    currency?: string | null;
    created_at?: string;
    reservation?: {
        id: number;
        lab_title: string;
    } | null;
};

type ProfileDashboard = {
    stats: {
        total_reservations: number;
        active_reservations: number;
        pending_payments: number;
        completed_hours: number;
        last_activity_at?: string | null;
    };
    active_reservations: ProfileReservation[];
    recent_reservations: ProfileReservation[];
    recent_payments: ProfilePayment[];
};

export default function Profile({ mustVerifyEmail, status }: { mustVerifyEmail: boolean; status?: string }) {
    const { auth, profileDashboard } = usePage<SharedData & { profileDashboard: ProfileDashboard }>().props;

    const formatDate = (value?: string | null) => {
        if (!value) return 'N/A';
        return new Date(value).toLocaleString('fr-FR', {
            day: '2-digit',
            month: 'short',
            hour: '2-digit',
            minute: '2-digit',
        });
    };

    const formatPrice = (cents?: number | null, currency = 'XOF') => {
        if (!cents) return '—';
        return `${(cents / 100).toLocaleString('fr-FR')} ${currency}`;
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Profile settings" />

            <SettingsLayout>
                <div className="space-y-4 mb-6 w-full">
                    <Card className="bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 border-0 text-white">
                        <CardContent className="p-6 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                            <div className="flex items-center gap-4">
                                <Avatar className="h-16 w-16 bg-white/10">
                                    <AvatarImage src={auth.user.avatar ?? undefined} />
                                    <AvatarFallback className="text-xl">
                                        {auth.user.name?.[0]?.toUpperCase() ?? '?'}
                                    </AvatarFallback>
                                </Avatar>
                                <div>
                                    <div className="flex items-center gap-3 flex-wrap">
                                        <h2 className="text-2xl font-semibold">{auth.user.name}</h2>
                                        {auth.user.role && (
                                            <Badge variant="destructive" className="uppercase">
                                                {auth.user.role}
                                            </Badge>
                                        )}
                                    </div>
                                    {auth.user.bio && (
                                        <p className="text-sm text-white/70">{auth.user.bio}</p>
                                    )}
                                    <div className="flex flex-wrap gap-3 text-xs text-white/70 mt-2">
                                        <span>{auth.user.email}</span>
                                        {auth.user.phone && <span>{auth.user.phone}</span>}
                                        {auth.user.organization && (
                                            <span>{auth.user.organization}</span>
                                        )}
                                        {auth.user.department && <span>{auth.user.department}</span>}
                                    </div>
                                </div>
                            </div>
                            <div className="flex flex-col gap-3 text-sm text-white/80">
                                <div>
                                    <p className="text-white/60 text-xs uppercase tracking-wide">
                                        Membre depuis
                                    </p>
                                    <p className="text-base font-semibold">
                                        {new Date(auth.user.created_at).toLocaleDateString('fr-FR')}
                                    </p>
                                </div>
                                <Button variant="secondary" size="sm" className="self-start" asChild>
                                    <a href="#profile-form">Modifier le profil</a>
                                </Button>
                            </div>
                        </CardContent>
                    </Card>

                <div className="space-y-4 mb-6 w-full">
                        <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                            <Card>
                                <CardHeader className="pb-2">
                                    <CardTitle className="text-sm text-muted-foreground">Réservations totales</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div className="text-3xl font-semibold">
                                        {profileDashboard.stats.total_reservations}
                                    </div>
                                    <p className="text-xs text-muted-foreground mt-1">
                                        Dernière activité : {formatDate(profileDashboard.stats.last_activity_at)}
                                    </p>
                                </CardContent>
                            </Card>
                            <Card>
                                <CardHeader className="pb-2">
                                    <CardTitle className="text-sm text-muted-foreground">Réservations actives</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div className="text-3xl font-semibold">
                                        {profileDashboard.stats.active_reservations}
                                    </div>
                                    <p className="text-xs text-muted-foreground mt-1">
                                        Sessions en cours
                                    </p>
                                </CardContent>
                            </Card>
                            <Card>
                                <CardHeader className="pb-2">
                                    <CardTitle className="text-sm text-muted-foreground">Heures complétées</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div className="text-3xl font-semibold">
                                        {profileDashboard.stats.completed_hours}
                                    </div>
                                    <p className="text-xs text-muted-foreground mt-1">
                                        Total des sessions terminées
                                    </p>
                                </CardContent>
                            </Card>
                            <Card>
                                <CardHeader className="pb-2">
                                    <CardTitle className="text-sm text-muted-foreground">Paiements en attente</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div className="text-3xl font-semibold">
                                        {profileDashboard.stats.pending_payments}
                                    </div>
                                    <p className="text-xs text-muted-foreground mt-1">
                                        Paiements à finaliser
                                    </p>
                                </CardContent>
                            </Card>
                        </div>

                        <div className="grid gap-4 lg:grid-cols-2">
                            <Card>
                                <CardHeader>
                                    <CardTitle>Sessions actives</CardTitle>
                                    <p className="text-sm text-muted-foreground">
                                        Vos réservations en cours ou programmées
                                    </p>
                                </CardHeader>
                                <CardContent>
                                    {profileDashboard.active_reservations.length > 0 ? (
                                        <div className="space-y-3">
                                            {profileDashboard.active_reservations.map((reservation) => (
                                                <div key={reservation.id} className="flex items-center justify-between rounded-lg border p-3">
                                                    <div>
                                                        <p className="font-semibold">{reservation.lab_title}</p>
                                                        <p className="text-xs text-muted-foreground">
                                                            {formatDate(reservation.start_at)} → {formatDate(reservation.end_at)}
                                                        </p>
                                                    </div>
                                                    <div className="text-right">
                                                        <p className="text-xs uppercase text-muted-foreground">{reservation.status}</p>
                                                        <p className="text-sm font-semibold">
                                                            {formatPrice(reservation.estimated_cents)}
                                                        </p>
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                    ) : (
                                        <p className="text-sm text-muted-foreground">Aucune session active.</p>
                                    )}
                                </CardContent>
                            </Card>

                            <Card>
                                <CardHeader>
                                    <CardTitle>Dernières réservations</CardTitle>
                                    <p className="text-sm text-muted-foreground">
                                        Historique des dernières interactions
                                    </p>
                                </CardHeader>
                                <CardContent>
                                    {profileDashboard.recent_reservations.length > 0 ? (
                                        <div className="space-y-3">
                                            {profileDashboard.recent_reservations.map((reservation) => (
                                                <div key={reservation.id} className="rounded-lg border p-3">
                                                    <div className="flex items-center justify-between">
                                                        <p className="font-medium">{reservation.lab_title}</p>
                                                        <span className="text-xs uppercase text-muted-foreground">
                                                            {reservation.status}
                                                        </span>
                                                    </div>
                                                    <p className="text-xs text-muted-foreground">
                                                        Créée le {formatDate(reservation.created_at)}
                                                    </p>
                                                    <p className="text-xs text-muted-foreground">
                                                        Créneau : {formatDate(reservation.start_at)} → {formatDate(reservation.end_at)}
                                                    </p>
                                                </div>
                                            ))}
                                        </div>
                                    ) : (
                                        <p className="text-sm text-muted-foreground">Aucune réservation récente.</p>
                                    )}
                                </CardContent>
                            </Card>

                            <Card className="lg:col-span-2">
                                <CardHeader>
                                    <CardTitle>Derniers paiements</CardTitle>
                                    <p className="text-sm text-muted-foreground">
                                        Suivi des transactions les plus récentes
                                    </p>
                                </CardHeader>
                                <CardContent>
                                    {profileDashboard.recent_payments.length > 0 ? (
                                        <div className="space-y-3">
                                            {profileDashboard.recent_payments.map((payment) => (
                                                <div key={payment.id} className="flex items-center justify-between rounded-lg border p-3">
                                                    <div>
                                                        <p className="font-medium">
                                                            {payment.reservation?.lab_title ?? 'Réservation'}
                                                        </p>
                                                        <p className="text-xs text-muted-foreground">
                                                            {formatDate(payment.created_at)}
                                                        </p>
                                                    </div>
                                                    <div className="text-right">
                                                        <p className="text-sm font-semibold">
                                                            {formatPrice(payment.amount, payment.currency || 'XOF')}
                                                        </p>
                                                        <p className="text-xs uppercase text-muted-foreground">
                                                            {payment.status}
                                                        </p>
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                    ) : (
                                        <p className="text-sm text-muted-foreground">Aucun paiement récent.</p>
                                    )}
                                </CardContent>
                            </Card>
                        </div>
                    </div>
                </div>

                <div className="space-y-6 w-full" id="profile-form">
                    <HeadingSmall title="Profile information" description="Update your name and email address" />

                    <Form
                        action={ProfileController.update().url}
                        method={ProfileController.update().method}
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
