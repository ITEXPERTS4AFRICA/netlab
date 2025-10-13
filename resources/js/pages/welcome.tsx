
import { dashboard, login, register } from '@/routes';
import { type SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';

export default function Welcome() {
    const { auth } = usePage<SharedData>().props;

    return (
        <>
            <Head title="NetLab - Laboratoire Virtuel Cisco">
                <link rel="preconnect" href="https://fonts.bunny.net" />
                <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
            </Head>

            {/* Navigation Header */}
            <header className="fixed top-0 left-0 right-0 z-50 bg-background/80 backdrop-blur-md border-b border-border">
                <div className="container mx-auto px-6 py-4">
                    <nav className="flex items-center justify-between">
                        <div className="flex items-center space-x-2">
                            <div className="w-8 h-8 bg-gradient-to-br from-primary to-accent rounded-lg flex items-center justify-center">
                                <span className="text-primary-foreground font-bold text-sm">NL</span>
                            </div>
                            <span className="font-bold text-xl bg-gradient-to-r from-primary to-accent bg-clip-text text-transparent">
                                NetLab
                            </span>
                        </div>

                        <div className="flex items-center space-x-4">
                            {auth.user ? (
                                <Button asChild>
                                    <Link href={dashboard()}>
                                        Dashboard
                                    </Link>
                                </Button>
                            ) : (
                                <div className="flex items-center space-x-3">
                                    <Button variant="ghost" asChild>
                                        <Link href={login()}>
                                            Se connecter
                                        </Link>
                                    </Button>
                                    <Button asChild>
                                        <Link href={register()}>
                                            S'inscrire
                                        </Link>
                                    </Button>
                                </div>
                            )}
                        </div>
                    </nav>
                </div>
            </header>
            {/* Hero Section */}
            <section className="min-h-screen flex items-center justify-center bg-gradient-to-br from-background via-background to-muted/20 pt-20">
                <div className="container mx-auto px-6 py-12">
                    <div className="grid lg:grid-cols-2 gap-12 items-center">
                        {/* Left Column - Content */}
                        <div className="space-y-8">
                            <div className="space-y-4">
                                <h1 className="text-4xl lg:text-6xl font-bold leading-tight">
                                    Laboratoire Virtuel
                                    <span className="bg-gradient-to-r from-primary to-accent bg-clip-text text-transparent block">
                                    ITexperts4africa Networking
                                    </span>
                                </h1>
                                <p className="text-lg text-muted-foreground max-w-lg">
                                    Découvrez une plateforme interactive pour apprendre et pratiquer les technologies Cisco.
                                    Réservez vos laboratoires virtuels et développez vos compétences en réseau.
                                </p>
                            </div>

                            <div className="flex flex-col sm:flex-row gap-4">
                                {auth.user ? (
                                    <Button size="lg" className="text-lg px-8" asChild>
                                        <Link href={dashboard()}>
                                            Accéder au Dashboard
                                        </Link>
                                    </Button>
                                ) : (
                                    <>
                                        <Button size="lg" className="text-lg px-8" asChild>
                                            <Link href={register()}>
                                                Commencer maintenant
                                            </Link>
                                        </Button>
                                        <Button size="lg" variant="outline" className="text-lg px-8" asChild>
                                            <Link href={login()}>
                                                Se connecter
                                            </Link>
                                        </Button>
                                    </>
                                )}
                            </div>

                            {/* Features Grid */}
                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-6 pt-8">
                                <Card className="border-2 hover:border-primary/50 transition-all duration-300 hover:shadow-lg">
                                    <CardContent className="p-6">
                                        <div className="w-12 h-12 bg-primary/10 rounded-lg flex items-center justify-center mb-4">
                                            <svg className="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                                            </svg>
                                        </div>
                                        <h3 className="font-semibold mb-2">Laboratoires Virtuels</h3>
                                        <p className="text-sm text-muted-foreground">
                                            Accédez à des environnements Cisco complets directement depuis votre navigateur
                                        </p>
                                    </CardContent>
                                </Card>

                                <Card className="border-2 hover:border-primary/50 transition-all duration-300 hover:shadow-lg">
                                    <CardContent className="p-6">
                                        <div className="w-12 h-12 bg-accent/10 rounded-lg flex items-center justify-center mb-4">
                                            <svg className="w-6 h-6 text-accent-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                            </svg>
                                        </div>
                                        <h3 className="font-semibold mb-2">Apprentissage Guidé</h3>
                                        <p className="text-sm text-muted-foreground">
                                            Suivez des parcours d'apprentissage structurés avec annotations intelligentes
                                        </p>
                                    </CardContent>
                                </Card>

                                <Card className="border-2 hover:border-primary/50 transition-all duration-300 hover:shadow-lg">
                                    <CardContent className="p-6">
                                        <div className="w-12 h-12 bg-primary/10 rounded-lg flex items-center justify-center mb-4">
                                            <svg className="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 7V3a2 2 0 012-2h4a2 2 0 012 2v4m-6 9l6-6m0 0v6m0-6h-6" />
                                            </svg>
                                        </div>
                                        <h3 className="font-semibold mb-2">Réservation Flexible</h3>
                                        <p className="text-sm text-muted-foreground">
                                            Réservez vos créneaux selon vos disponibilités avec notre système de planification
                                        </p>
                                    </CardContent>
                                </Card>

                                <Card className="border-2 hover:border-primary/50 transition-all duration-300 hover:shadow-lg">
                                    <CardContent className="p-6">
                                        <div className="w-12 h-12 bg-accent/10 rounded-lg flex items-center justify-center mb-4">
                                            <svg className="w-6 h-6 text-accent-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 10V3L4 14h7v7l9-11h-7z" />
                                            </svg>
                                        </div>
                                        <h3 className="font-semibold mb-2">Performance Élevée</h3>
                                        <p className="text-sm text-muted-foreground">
                                            Bénéficiez de performances optimales avec notre infrastructure cloud dédiée
                                        </p>
                                    </CardContent>
                                </Card>
                            </div>
                        </div>

                        {/* Right Column - Visual */}
                        <div className="relative">
                            <div className="relative mx-auto max-w-md lg:max-w-none">
                                {/* Main Visual Card */}
                                <Card className="relative overflow-hidden border-2 shadow-2xl bg-gradient-to-br from-card via-card to-muted/30">
                                    <div className="absolute inset-0 bg-gradient-to-br from-primary/5 via-transparent to-accent/5" />
                                    <CardContent className="p-8 relative">
                                        <div className="space-y-6">
                                            {/* Network Topology Visualization */}
                                            <div className="relative">
                                                <div className="w-full h-48 bg-gradient-to-br from-muted/30 to-muted/10 rounded-lg border border-border/50 flex items-center justify-center">
                                                    <div className="relative">
                                                        {/* Central Router */}
                                                        <div className="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2">
                                                            <div className="w-16 h-16 bg-primary rounded-full flex items-center justify-center shadow-lg animate-pulse">
                                                                <svg className="w-8 h-8 text-primary-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01" />
                                                                </svg>
                                                            </div>
                                                        </div>

                                                        {/* Connected Devices */}
                                                        <div className="absolute top-8 left-8">
                                                            <div className="w-12 h-12 bg-accent/20 rounded-lg flex items-center justify-center border border-accent/30">
                                                                <svg className="w-6 h-6 text-accent-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                                                </svg>
                                                            </div>
                                                        </div>

                                                        <div className="absolute top-8 right-8">
                                                            <div className="w-12 h-12 bg-accent/20 rounded-lg flex items-center justify-center border border-accent/30">
                                                                <svg className="w-6 h-6 text-accent-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                                                </svg>
                                                            </div>
                                                        </div>

                                                        <div className="absolute bottom-8 left-1/2 transform -translate-x-1/2">
                                                            <div className="w-12 h-12 bg-accent/20 rounded-lg flex items-center justify-center border border-accent/30">
                                                                <svg className="w-6 h-6 text-accent-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                                                </svg>
                                                            </div>
                                                        </div>

                                                        {/* Connection Lines */}
                                                        <svg className="absolute inset-0 w-full h-full" style={{zIndex: 1}}>
                                                            <path
                                                                d="M120 96 L160 120"
                                                                stroke="currentColor"
                                                                strokeWidth="2"
                                                                className="text-primary/40"
                                                            />
                                                        </svg>
                                                    </div>
                                                </div>
                                            </div>

                                            {/* Status Indicators */}
                                            <div className="grid grid-cols-3 gap-4">
                                                <div className="text-center">
                                                    <div className="w-3 h-3 bg-green-500 rounded-full mx-auto mb-2 animate-pulse"></div>
                                                    <p className="text-xs text-muted-foreground">Système actif</p>
                                                </div>
                                                <div className="text-center">
                                                    <div className="w-3 h-3 bg-blue-500 rounded-full mx-auto mb-2 animate-pulse"></div>
                                                    <p className="text-xs text-muted-foreground">Connexions stables</p>
                                                </div>
                                                <div className="text-center">
                                                    <div className="w-3 h-3 bg-accent rounded-full mx-auto mb-2 animate-pulse"></div>
                                                    <p className="text-xs text-muted-foreground">Labs disponibles</p>
                                                </div>
                                            </div>
                                        </div>
                                    </CardContent>
                                </Card>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </>
    );
}
