import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import {
    FlaskConical, Search, Edit, Trash2, Eye, Filter,
    Star, Globe, Clock, Users, TrendingUp, RefreshCw, Lock
} from 'lucide-react';
import { useState } from 'react';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

interface Lab {
    id: number;
    cml_id: string;
    lab_title: string;
    lab_description?: string;
    short_description?: string;
    state?: string;
    node_count?: number;
    link_count?: number;
    price_cents?: number;
    currency?: string;
    difficulty_level?: string;
    is_featured: boolean;
    is_published: boolean;
    view_count: number;
    reservation_count: number;
    rating?: number;
    rating_count: number;
    estimated_duration_minutes?: number;
    created_at: string;
    reservations_count?: number;
    documentation_media_count?: number;
    metadata?: {
        is_restricted?: boolean;
        [key: string]: unknown;
    };
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface Props {
    labs: {
        data: Lab[];
        links?: PaginationLink[];
        total?: number;
        current_page?: number;
        last_page?: number;
        per_page?: number;
    };
    filters: {
        search?: string;
        state?: string;
        is_published?: boolean;
        is_featured?: boolean;
        difficulty_level?: string;
    };
    stats: {
        total: number;
        published: number;
        featured: number;
        pending: number;
    };
}

export default function LabsIndex({ labs, filters, stats }: Props) {
    const [search, setSearch] = useState(filters.search || '');
    const [state, setState] = useState(filters.state || 'all');
    const [isPublished, setIsPublished] = useState(filters.is_published?.toString() || 'all');
    const [difficulty, setDifficulty] = useState(filters.difficulty_level || 'all');

    const handleSearch = () => {
        router.get('/admin/labs', {
            search,
            state: state !== 'all' ? state : undefined,
            is_published: isPublished !== 'all' ? isPublished === 'true' : undefined,
            difficulty_level: difficulty !== 'all' ? difficulty : undefined,
        }, { preserveState: true });
    };

    const getStateBadge = (state?: string) => {
        const colors: Record<string, string> = {
            'STARTED': 'bg-green-500',
            'STOPPED': 'bg-gray-500',
            'DEFINED_ONLY': 'bg-yellow-500',
            'BOOTED': 'bg-blue-500',
        };
        return colors[state || ''] || 'bg-gray-500';
    };

    const getDifficultyBadge = (level?: string) => {
        const colors: Record<string, string> = {
            'beginner': 'bg-green-500',
            'intermediate': 'bg-blue-500',
            'advanced': 'bg-orange-500',
            'expert': 'bg-red-500',
        };
        return colors[level || ''] || 'bg-gray-500';
    };

    const formatPrice = (cents?: number, currency = 'XOF') => {
        if (!cents) return 'Gratuit';
        return `${(cents / 100).toLocaleString('fr-FR')} ${currency}`;
    };

    const toggleFeatured = (lab: Lab) => {
        router.patch(`/admin/labs/${lab.id}/toggle-featured`, {}, {
            preserveScroll: true,
            onSuccess: () => {
                router.reload({ only: ['labs', 'stats'] });
            }
        });
    };

    const togglePublished = (lab: Lab) => {
        router.patch(`/admin/labs/${lab.id}/toggle-published`, {}, {
            preserveScroll: true,
            onSuccess: () => {
                router.reload({ only: ['labs', 'stats'] });
            }
        });
    };

    const toggleRestricted = (lab: Lab) => {
        router.patch(`/admin/labs/${lab.id}/toggle-restricted`, {}, {
            preserveScroll: true,
            onSuccess: () => {
                router.reload({ only: ['labs', 'stats'] });
            }
        });
    };

    const isRestricted = (lab: Lab) => {
        return lab.metadata?.is_restricted === true;
    };

    const [isSyncing, setIsSyncing] = useState(false);

    const syncFromCml = () => {
        if (isSyncing) return;

        setIsSyncing(true);
        router.post('/admin/labs/sync-from-cml', {}, {
            onSuccess: () => {
                router.reload({ only: ['labs', 'stats'] });
                setIsSyncing(false);
            },
            onError: () => {
                setIsSyncing(false);
            },
            onFinish: () => {
                setIsSyncing(false);
            }
        });
    };

    return (
        <AppLayout>
            <Head title="Gestion des labs" />

            <div className="container mx-auto py-8 space-y-6">
                {/* En-tête */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold flex items-center gap-2">
                            <FlaskConical className="h-8 w-8" />
                            Gestion des labs
                        </h1>
                        <p className="text-muted-foreground mt-1">
                            Gérez les laboratoires CML et leurs métadonnées
                        </p>
                    </div>
                    <div className="flex gap-2">
                        <Button
                            variant="outline"
                            onClick={syncFromCml}
                            disabled={isSyncing}
                        >
                            <RefreshCw className={`h-4 w-4 mr-2 ${isSyncing ? 'animate-spin' : ''}`} />
                            {isSyncing ? 'Synchronisation...' : 'Synchroniser depuis CML'}
                        </Button>
                    </div>
                </div>

                {/* Statistiques */}
                <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <Card>
                        <CardHeader className="pb-2">
                            <CardDescription>Total</CardDescription>
                            <CardTitle className="text-2xl">{stats?.total ?? 0}</CardTitle>
                        </CardHeader>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2">
                            <CardDescription>Publiés</CardDescription>
                            <CardTitle className="text-2xl text-green-600">{stats?.published ?? 0}</CardTitle>
                        </CardHeader>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2">
                            <CardDescription>Mis en avant</CardDescription>
                            <CardTitle className="text-2xl text-yellow-600">{stats?.featured ?? 0}</CardTitle>
                        </CardHeader>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2">
                            <CardDescription>En attente</CardDescription>
                            <CardTitle className="text-2xl text-gray-600">{stats?.pending ?? 0}</CardTitle>
                        </CardHeader>
                    </Card>
                </div>

                {/* Filtres */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Filter className="h-5 w-5" />
                            Filtres
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="flex gap-4 flex-wrap">
                            <div className="flex-1 min-w-[200px]">
                                <Input
                                    placeholder="Rechercher par titre, description, CML ID..."
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    onKeyPress={(e) => e.key === 'Enter' && handleSearch()}
                                />
                            </div>
                            <Select value={state} onValueChange={setState}>
                                <SelectTrigger className="w-[180px]">
                                    <SelectValue placeholder="État" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Tous les états</SelectItem>
                                    <SelectItem value="STARTED">Démarré</SelectItem>
                                    <SelectItem value="STOPPED">Arrêté</SelectItem>
                                    <SelectItem value="DEFINED_ONLY">Défini</SelectItem>
                                    <SelectItem value="BOOTED">Booté</SelectItem>
                                </SelectContent>
                            </Select>
                            <Select value={isPublished} onValueChange={setIsPublished}>
                                <SelectTrigger className="w-[180px]">
                                    <SelectValue placeholder="Publication" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Tous</SelectItem>
                                    <SelectItem value="true">Publié</SelectItem>
                                    <SelectItem value="false">Non publié</SelectItem>
                                </SelectContent>
                            </Select>
                            <Select value={difficulty} onValueChange={setDifficulty}>
                                <SelectTrigger className="w-[180px]">
                                    <SelectValue placeholder="Difficulté" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Tous les niveaux</SelectItem>
                                    <SelectItem value="beginner">Débutant</SelectItem>
                                    <SelectItem value="intermediate">Intermédiaire</SelectItem>
                                    <SelectItem value="advanced">Avancé</SelectItem>
                                    <SelectItem value="expert">Expert</SelectItem>
                                </SelectContent>
                            </Select>
                            <Button onClick={handleSearch}>
                                <Search className="h-4 w-4 mr-2" />
                                Rechercher
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                {/* Liste des labs */}
                <Card>
                    <CardHeader>
                        <CardTitle>Labs</CardTitle>
                        <CardDescription>
                            {labs?.total ?? labs?.data?.length ?? 0} lab(s) trouvé(s)
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="space-y-4">
                            {labs?.data && labs.data.length > 0 ? labs.data.map((lab) => (
                                <div
                                    key={lab.id}
                                    className="flex items-center justify-between p-4 border rounded-lg hover:bg-muted/50 transition-colors"
                                >
                                    <div className="flex items-center gap-4 flex-1">
                                        <div className="w-12 h-12 rounded-lg bg-primary/10 flex items-center justify-center">
                                            <FlaskConical className="h-6 w-6 text-primary" />
                                        </div>
                                        <div className="flex-1 min-w-0">
                                            <div className="flex items-center gap-2 flex-wrap">
                                                <h3 className="font-semibold">{lab.lab_title || 'Sans titre'}</h3>
                                                {lab.is_featured && (
                                                    <Badge variant="default" className="bg-yellow-500">
                                                        <Star className="h-3 w-3 mr-1" />
                                                        En avant
                                                    </Badge>
                                                )}
                                                {lab.is_published ? (
                                                    <Badge variant="default" className="bg-green-500">
                                                        <Globe className="h-3 w-3 mr-1" />
                                                        Publié
                                                    </Badge>
                                                ) : (
                                                    <Badge variant="secondary">Non publié</Badge>
                                                )}
                                                {lab.state && (
                                                    <Badge className={getStateBadge(lab.state)}>
                                                        {lab.state}
                                                    </Badge>
                                                )}
                                                {lab.difficulty_level && (
                                                    <Badge className={getDifficultyBadge(lab.difficulty_level)}>
                                                        {lab.difficulty_level}
                                                    </Badge>
                                                )}
                                            </div>
                                            <p className="text-sm text-muted-foreground line-clamp-1">
                                                {lab.short_description || lab.lab_description || 'Aucune description'}
                                            </p>
                                            <div className="flex items-center gap-4 mt-2 text-xs text-muted-foreground">
                                                <span className="flex items-center gap-1">
                                                    <Users className="h-3 w-3" />
                                                    {lab.reservations_count || lab.reservation_count || 0} réservations
                                                </span>
                                                <span className="flex items-center gap-1">
                                                    <TrendingUp className="h-3 w-3" />
                                                    {lab.view_count} vues
                                                </span>
                                                {lab.estimated_duration_minutes && (
                                                    <span className="flex items-center gap-1">
                                                        <Clock className="h-3 w-3" />
                                                        {lab.estimated_duration_minutes} min
                                                    </span>
                                                )}
                                                <span className="font-medium">{formatPrice(lab.price_cents, lab.currency)}</span>
                                                {lab.rating && (
                                                    <span className="flex items-center gap-1">
                                                        <Star className="h-3 w-3 fill-yellow-400 text-yellow-400" />
                                                        {lab.rating.toFixed(1)} ({lab.rating_count})
                                                    </span>
                                                )}
                                            </div>
                                            <p className="text-xs text-muted-foreground mt-1">
                                                CML ID: {lab.cml_id} • {lab.node_count || 0} nœuds • {lab.link_count || 0} liens
                                            </p>
                                        </div>
                                    </div>
                                    <div className="flex items-center gap-2 ml-4">
                                        <Link href={`/admin/labs/${lab.id}`}>
                                            <Button variant="ghost" size="sm">
                                                <Eye className="h-4 w-4" />
                                            </Button>
                                        </Link>
                                        <Link href={`/admin/labs/${lab.id}/edit`}>
                                            <Button variant="ghost" size="sm">
                                                <Edit className="h-4 w-4" />
                                            </Button>
                                        </Link>
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            onClick={() => toggleFeatured(lab)}
                                            title={lab.is_featured ? 'Retirer des favoris' : 'Mettre en avant'}
                                        >
                                            <Star className={`h-4 w-4 ${lab.is_featured ? 'fill-yellow-400 text-yellow-400' : ''}`} />
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            onClick={() => togglePublished(lab)}
                                            title={lab.is_published ? 'Dépublier' : 'Publier'}
                                        >
                                            <Globe className={`h-4 w-4 ${lab.is_published ? 'text-green-500' : 'text-gray-400'}`} />
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            onClick={() => toggleRestricted(lab)}
                                            title={isRestricted(lab) ? 'Désactiver restriction' : 'Restreindre l\'accès'}
                                        >
                                            <Lock className={`h-4 w-4 ${isRestricted(lab) ? 'text-red-500 fill-red-500' : 'text-gray-400'}`} />
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            onClick={() => {
                                                if (confirm('Êtes-vous sûr de vouloir supprimer ce lab ?')) {
                                                    router.delete(`/admin/labs/${lab.id}`);
                                                }
                                            }}
                                        >
                                            <Trash2 className="h-4 w-4 text-destructive" />
                                        </Button>
                                    </div>
                                </div>
                            )) : (
                                <div className="text-center py-12">
                                    <FlaskConical className="h-16 w-16 mx-auto text-muted-foreground mb-4 opacity-50" />
                                    <h3 className="text-lg font-semibold mb-2">Aucun lab trouvé</h3>
                                    <p className="text-muted-foreground mb-4">
                                        {stats.total === 0
                                            ? "Aucun lab n'a encore été synchronisé depuis CML. Cliquez sur le bouton ci-dessous pour commencer."
                                            : "Aucun lab ne correspond à vos critères de recherche."}
                                    </p>
                                    {stats.total === 0 && (
                                        <Button
                                            onClick={syncFromCml}
                                            size="lg"
                                            disabled={isSyncing}
                                        >
                                            <RefreshCw className={`h-4 w-4 mr-2 ${isSyncing ? 'animate-spin' : ''}`} />
                                            {isSyncing ? 'Synchronisation en cours...' : 'Synchroniser depuis CML'}
                                        </Button>
                                    )}
                                </div>
                            )}
                        </div>

                        {/* Pagination */}
                        {labs?.links && Array.isArray(labs.links) && labs.links.length > 3 && (
                            <div className="flex items-center justify-center gap-2 mt-6">
                                {labs.links.map((link: PaginationLink, index: number) => (
                                    <Button
                                        key={index}
                                        variant={link.active ? 'default' : 'outline'}
                                        size="sm"
                                        disabled={!link.url}
                                        onClick={() => link.url && router.get(link.url)}
                                        dangerouslySetInnerHTML={{ __html: link.label }}
                                    />
                                ))}
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}

