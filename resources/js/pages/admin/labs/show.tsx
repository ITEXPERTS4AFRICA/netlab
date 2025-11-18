import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    FlaskConical, Edit, ArrowLeft, Star, Globe, Users, TrendingUp,
    Clock, DollarSign, FileText, Image, Video, Link as LinkIcon, Upload, Plus,
    Trash2, ChevronUp, ChevronDown, Save, X
} from 'lucide-react';
import { useState, useEffect } from 'react';

interface LabDocumentationMedia {
    id: number;
    type: string;
    title?: string;
    description?: string;
    file_url?: string;
    file_path?: string;
    mime_type?: string;
    order: number;
    is_active: boolean;
}

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
    readme?: string;
    difficulty_level?: string;
    estimated_duration_minutes?: number;
    is_featured: boolean;
    is_published: boolean;
    view_count: number;
    reservation_count: number;
    rating?: number;
    rating_count: number;
    created_at: string;
    documentation_media?: LabDocumentationMedia[];
}

interface Props {
    lab: Lab;
}

export default function LabShow({ lab }: Props) {
    const [isDialogOpen, setIsDialogOpen] = useState(false);
    const [mediaType, setMediaType] = useState<'file' | 'link'>('file');
    const [isUploading, setIsUploading] = useState(false);
    const [uploadError, setUploadError] = useState<string | null>(null);
    const [fileType, setFileType] = useState<string>('');
    const [editingMediaId, setEditingMediaId] = useState<number | null>(null);
    const [editFormData, setEditFormData] = useState<{
        title?: string;
        description?: string;
        url?: string;
    }>({});
    const [isDeleting, setIsDeleting] = useState<number | null>(null);
    const [isReordering, setIsReordering] = useState(false);

    // Réinitialiser l'erreur après 5 secondes
    useEffect(() => {
        if (uploadError) {
            const timer = setTimeout(() => {
                setUploadError(null);
            }, 5000);
            return () => clearTimeout(timer);
        }
    }, [uploadError]);

    const formatPrice = (cents?: number, currency = 'XOF') => {
        if (!cents) return 'Gratuit';
        return `${(cents / 100).toLocaleString('fr-FR')} ${currency}`;
    };

    const getMediaIcon = (type: string) => {
        switch (type) {
            case 'image': return <Image className="h-4 w-4" />;
            case 'video': return <Video className="h-4 w-4" />;
            case 'link': return <LinkIcon className="h-4 w-4" />;
            default: return <FileText className="h-4 w-4" />;
        }
    };

    const handleFileUpload = async (e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        setIsUploading(true);
        setUploadError(null);

        const formData = new FormData(e.currentTarget);
        const file = formData.get('file') as File;
        const title = formData.get('title') as string;
        const description = formData.get('description') as string;

        if (!file && mediaType === 'file') {
            setUploadError('Veuillez sélectionner un fichier');
            setIsUploading(false);
            return;
        }

        if (!fileType) {
            setUploadError('Veuillez sélectionner un type de fichier');
            setIsUploading(false);
            return;
        }

        try {
            const uploadFormData = new FormData();
            if (file) uploadFormData.append('file', file);
            uploadFormData.append('type', fileType);
            if (title) uploadFormData.append('title', title);
            if (description) uploadFormData.append('description', description);

            const response = await fetch(`/admin/labs/${lab.id}/media/upload`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: uploadFormData,
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || data.error || 'Erreur lors de l\'upload');
            }

            setIsDialogOpen(false);
            setFileType('');
            router.reload({ only: ['lab'] });
        } catch (error) {
            setUploadError(error instanceof Error ? error.message : 'Erreur lors de l\'upload');
        } finally {
            setIsUploading(false);
        }
    };

    const handleLinkAdd = async (e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        setIsUploading(true);
        setUploadError(null);

        const formData = new FormData(e.currentTarget);
        const url = formData.get('url') as string;
        const title = formData.get('title') as string;
        const description = formData.get('description') as string;

        if (!url) {
            setUploadError('Veuillez saisir une URL');
            setIsUploading(false);
            return;
        }

        try {
            const response = await fetch(`/admin/labs/${lab.id}/media/link`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify({ url, title, description }),
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || data.error || 'Erreur lors de l\'ajout du lien');
            }

            setIsDialogOpen(false);
            router.reload({ only: ['lab'] });
        } catch (error) {
            setUploadError(error instanceof Error ? error.message : 'Erreur lors de l\'ajout du lien');
        } finally {
            setIsUploading(false);
        }
    };

    const handleDialogClose = (open: boolean) => {
        setIsDialogOpen(open);
        if (!open) {
            setUploadError(null);
            setFileType('');
        }
    };

    const handleEditMedia = (media: LabDocumentationMedia) => {
        setEditingMediaId(media.id);
        setEditFormData({
            title: media.title || '',
            description: media.description || '',
            url: media.file_url || '',
        });
    };

    const handleCancelEdit = () => {
        setEditingMediaId(null);
        setEditFormData({});
    };

    const handleSaveEdit = async (mediaId: number) => {
        try {
            const response = await fetch(`/admin/labs/${lab.id}/media/${mediaId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify(editFormData),
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || data.error || 'Erreur lors de la modification');
            }

            setEditingMediaId(null);
            setEditFormData({});
            router.reload({ only: ['lab'] });
        } catch (error) {
            setUploadError(error instanceof Error ? error.message : 'Erreur lors de la modification');
        }
    };

    const handleDeleteMedia = async (mediaId: number) => {
        if (!confirm('Êtes-vous sûr de vouloir supprimer ce média ?')) {
            return;
        }

        setIsDeleting(mediaId);
        try {
            const response = await fetch(`/admin/labs/${lab.id}/media/${mediaId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || data.error || 'Erreur lors de la suppression');
            }

            router.reload({ only: ['lab'] });
        } catch (error) {
            setUploadError(error instanceof Error ? error.message : 'Erreur lors de la suppression');
        } finally {
            setIsDeleting(null);
        }
    };

    const handleMoveMedia = async (mediaId: number, direction: 'up' | 'down') => {
        if (!lab.documentation_media) return;

        const currentMedia = lab.documentation_media.find(m => m.id === mediaId);
        if (!currentMedia) return;

        const currentIndex = lab.documentation_media.findIndex(m => m.id === mediaId);
        const newIndex = direction === 'up' ? currentIndex - 1 : currentIndex + 1;

        if (newIndex < 0 || newIndex >= lab.documentation_media.length) return;

        setIsReordering(true);
        try {
            const reorderedMedia = [...lab.documentation_media];
            const [removed] = reorderedMedia.splice(currentIndex, 1);
            reorderedMedia.splice(newIndex, 0, removed);

            const mediaOrder = reorderedMedia.map((m, index) => ({
                id: m.id,
                order: index,
            }));

            const response = await fetch(`/admin/labs/${lab.id}/media/reorder`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify({ media: mediaOrder }),
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || data.error || 'Erreur lors du réordonnancement');
            }

            router.reload({ only: ['lab'] });
        } catch (error) {
            setUploadError(error instanceof Error ? error.message : 'Erreur lors du réordonnancement');
        } finally {
            setIsReordering(false);
        }
    };

    return (
        <AppLayout>
            <Head title={lab.lab_title} />

            <div className="container mx-auto py-8 max-w-6xl">
                <div className="flex items-center justify-between mb-6">
                    <div className="flex items-center gap-4">
                        <Link href="/admin/labs">
                            <Button variant="ghost" size="sm">
                                <ArrowLeft className="h-4 w-4 mr-2" />
                                Retour
                            </Button>
                        </Link>
                        <div>
                            <h1 className="text-3xl font-bold flex items-center gap-2">
                                <FlaskConical className="h-8 w-8" />
                                {lab.lab_title}
                            </h1>
                            <p className="text-muted-foreground mt-1">
                                CML ID: {lab.cml_id}
                            </p>
                        </div>
                    </div>
                    <Link href={`/admin/labs/${lab.id}/edit`}>
                        <Button>
                            <Edit className="h-4 w-4 mr-2" />
                            Modifier
                        </Button>
                    </Link>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {/* Colonne principale */}
                    <div className="lg:col-span-2 space-y-6">
                        {/* Informations principales */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Informations</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div>
                                    <h3 className="font-semibold mb-2">Description courte</h3>
                                    <p className="text-muted-foreground">
                                        {lab.short_description || lab.lab_description || 'Aucune description'}
                                    </p>
                                </div>

                                {lab.lab_description && (
                                    <div>
                                        <h3 className="font-semibold mb-2">Description complète</h3>
                                        <p className="text-muted-foreground whitespace-pre-wrap">
                                            {lab.lab_description}
                                        </p>
                                    </div>
                                )}

                                {lab.readme && (
                                    <div>
                                        <h3 className="font-semibold mb-2">README</h3>
                                        <div className="prose max-w-none">
                                            <pre className="bg-muted p-4 rounded-lg overflow-x-auto text-sm">
                                                {lab.readme}
                                            </pre>
                                        </div>
                                    </div>
                                )}
                            </CardContent>
                        </Card>

                        {/* Documentation média */}
                        <Card>
                            <CardHeader>
                                <div className="flex items-center justify-between">
                                    <CardTitle>Documentation & Médias</CardTitle>
                                    <Dialog open={isDialogOpen} onOpenChange={handleDialogClose}>
                                        <DialogTrigger asChild>
                                            <Button variant="outline" size="sm">
                                                <Plus className="h-4 w-4 mr-2" />
                                                Ajouter
                                            </Button>
                                        </DialogTrigger>
                                        <DialogContent className="max-w-2xl">
                                            <DialogHeader>
                                                <DialogTitle>Ajouter un média</DialogTitle>
                                                <DialogDescription>
                                                    Ajoutez un fichier (image, vidéo, document) ou un lien externe à la documentation du lab.
                                                </DialogDescription>
                                            </DialogHeader>

                                            <div className="space-y-4">
                                                <div className="flex gap-2">
                                                    <Button
                                                        type="button"
                                                        variant={mediaType === 'file' ? 'default' : 'outline'}
                                                        size="sm"
                                                        onClick={() => {
                                                            setMediaType('file');
                                                            setUploadError(null);
                                                        }}
                                                    >
                                                        <Upload className="h-4 w-4 mr-2" />
                                                        Fichier
                                                    </Button>
                                                    <Button
                                                        type="button"
                                                        variant={mediaType === 'link' ? 'default' : 'outline'}
                                                        size="sm"
                                                        onClick={() => {
                                                            setMediaType('link');
                                                            setUploadError(null);
                                                        }}
                                                    >
                                                        <LinkIcon className="h-4 w-4 mr-2" />
                                                        Lien
                                                    </Button>
                                                </div>

                                                {uploadError && (
                                                    <div className="bg-destructive/10 text-destructive p-3 rounded-md text-sm">
                                                        {uploadError}
                                                    </div>
                                                )}

                                                {mediaType === 'file' ? (
                                                    <form onSubmit={handleFileUpload} className="space-y-4">
                                                        <div>
                                                            <Label htmlFor="file">Fichier *</Label>
                                                            <Input
                                                                id="file"
                                                                name="file"
                                                                type="file"
                                                                accept="image/*,video/*,.pdf,.doc,.docx"
                                                                required
                                                                disabled={isUploading}
                                                            />
                                                            <p className="text-xs text-muted-foreground mt-1">
                                                                Formats acceptés: Images, Vidéos, Documents (max 10MB)
                                                            </p>
                                                        </div>

                                                        <div>
                                                            <Label htmlFor="type">Type *</Label>
                                                            <Select value={fileType} onValueChange={setFileType} disabled={isUploading}>
                                                                <SelectTrigger>
                                                                    <SelectValue placeholder="Sélectionner un type" />
                                                                </SelectTrigger>
                                                                <SelectContent>
                                                                    <SelectItem value="image">Image</SelectItem>
                                                                    <SelectItem value="video">Vidéo</SelectItem>
                                                                    <SelectItem value="document">Document</SelectItem>
                                                                </SelectContent>
                                                            </Select>
                                                        </div>

                                                        <div>
                                                            <Label htmlFor="title">Titre</Label>
                                                            <Input
                                                                id="title"
                                                                name="title"
                                                                placeholder="Titre du média (optionnel)"
                                                                disabled={isUploading}
                                                            />
                                                        </div>

                                                        <div>
                                                            <Label htmlFor="description">Description</Label>
                                                            <Textarea
                                                                id="description"
                                                                name="description"
                                                                placeholder="Description du média (optionnel)"
                                                                rows={3}
                                                                disabled={isUploading}
                                                            />
                                                        </div>

                                                        <div className="flex justify-end gap-2">
                                                            <Button
                                                                type="button"
                                                                variant="outline"
                                                                onClick={() => setIsDialogOpen(false)}
                                                                disabled={isUploading}
                                                            >
                                                                Annuler
                                                            </Button>
                                                            <Button type="submit" disabled={isUploading}>
                                                                {isUploading ? 'Upload en cours...' : 'Ajouter'}
                                                            </Button>
                                                        </div>
                                                    </form>
                                                ) : (
                                                    <form onSubmit={handleLinkAdd} className="space-y-4">
                                                        <div>
                                                            <Label htmlFor="url">URL *</Label>
                                                            <Input
                                                                id="url"
                                                                name="url"
                                                                type="url"
                                                                placeholder="https://example.com"
                                                                required
                                                                disabled={isUploading}
                                                            />
                                                        </div>

                                                        <div>
                                                            <Label htmlFor="link-title">Titre</Label>
                                                            <Input
                                                                id="link-title"
                                                                name="title"
                                                                placeholder="Titre du lien (optionnel, sera généré depuis l'URL si vide)"
                                                                disabled={isUploading}
                                                            />
                                                        </div>

                                                        <div>
                                                            <Label htmlFor="link-description">Description</Label>
                                                            <Textarea
                                                                id="link-description"
                                                                name="description"
                                                                placeholder="Description du lien (optionnel)"
                                                                rows={3}
                                                                disabled={isUploading}
                                                            />
                                                        </div>

                                                        <div className="flex justify-end gap-2">
                                                            <Button
                                                                type="button"
                                                                variant="outline"
                                                                onClick={() => setIsDialogOpen(false)}
                                                                disabled={isUploading}
                                                            >
                                                                Annuler
                                                            </Button>
                                                            <Button type="submit" disabled={isUploading}>
                                                                {isUploading ? 'Ajout en cours...' : 'Ajouter'}
                                                            </Button>
                                                        </div>
                                                    </form>
                                                )}
                                            </div>
                                        </DialogContent>
                                    </Dialog>
                                </div>
                            </CardHeader>
                            <CardContent>
                                {uploadError && (
                                    <div className="mb-4 bg-destructive/10 text-destructive p-3 rounded-md text-sm">
                                        {uploadError}
                                    </div>
                                )}
                                {lab.documentation_media && lab.documentation_media.length > 0 ? (
                                    <div className="space-y-3">
                                        {lab.documentation_media.map((media, index) => (
                                            <div
                                                key={media.id}
                                                className="flex items-start gap-3 p-3 border rounded-lg hover:bg-muted/50 transition-colors"
                                            >
                                                <div className="mt-1">
                                                    {getMediaIcon(media.type)}
                                                </div>
                                                <div className="flex-1 min-w-0">
                                                    {editingMediaId === media.id ? (
                                                        <div className="space-y-3">
                                                            <div>
                                                                <Label htmlFor={`edit-title-${media.id}`} className="text-xs">Titre</Label>
                                                                <Input
                                                                    id={`edit-title-${media.id}`}
                                                                    value={editFormData.title || ''}
                                                                    onChange={(e) => setEditFormData({ ...editFormData, title: e.target.value })}
                                                                    className="mt-1"
                                                                />
                                                            </div>
                                                            <div>
                                                                <Label htmlFor={`edit-description-${media.id}`} className="text-xs">Description</Label>
                                                                <Textarea
                                                                    id={`edit-description-${media.id}`}
                                                                    value={editFormData.description || ''}
                                                                    onChange={(e) => setEditFormData({ ...editFormData, description: e.target.value })}
                                                                    className="mt-1"
                                                                    rows={2}
                                                                />
                                                            </div>
                                                            {media.type === 'link' && (
                                                                <div>
                                                                    <Label htmlFor={`edit-url-${media.id}`} className="text-xs">URL</Label>
                                                                    <Input
                                                                        id={`edit-url-${media.id}`}
                                                                        type="url"
                                                                        value={editFormData.url || ''}
                                                                        onChange={(e) => setEditFormData({ ...editFormData, url: e.target.value })}
                                                                        className="mt-1"
                                                                    />
                                                                </div>
                                                            )}
                                                            <div className="flex gap-2">
                                                                <Button
                                                                    type="button"
                                                                    size="sm"
                                                                    onClick={() => handleSaveEdit(media.id)}
                                                                    disabled={isUploading}
                                                                >
                                                                    <Save className="h-3 w-3 mr-1" />
                                                                    Enregistrer
                                                                </Button>
                                                                <Button
                                                                    type="button"
                                                                    size="sm"
                                                                    variant="outline"
                                                                    onClick={handleCancelEdit}
                                                                    disabled={isUploading}
                                                                >
                                                                    <X className="h-3 w-3 mr-1" />
                                                                    Annuler
                                                                </Button>
                                                            </div>
                                                        </div>
                                                    ) : (
                                                        <>
                                                            <h4 className="font-medium">{media.title || 'Sans titre'}</h4>
                                                            {media.description && (
                                                                <p className="text-sm text-muted-foreground mt-1">{media.description}</p>
                                                            )}
                                                            {media.file_url && (
                                                                <a
                                                                    href={media.file_url}
                                                                    target="_blank"
                                                                    rel="noopener noreferrer"
                                                                    className="text-sm text-primary hover:underline mt-1 inline-block"
                                                                >
                                                                    {media.type === 'link' ? media.file_url : 'Voir le fichier'}
                                                                </a>
                                                            )}
                                                        </>
                                                    )}
                                                </div>
                                                {editingMediaId !== media.id && (
                                                    <div className="flex items-center gap-2 flex-shrink-0">
                                                        {/* Boutons de réorganisation */}
                                                        <div className="flex flex-col gap-1">
                                                            <Button
                                                                type="button"
                                                                variant="ghost"
                                                                size="sm"
                                                                className="h-6 w-6 p-0"
                                                                onClick={() => handleMoveMedia(media.id, 'up')}
                                                                disabled={index === 0 || isReordering}
                                                                title="Déplacer vers le haut"
                                                            >
                                                                <ChevronUp className="h-4 w-4" />
                                                            </Button>
                                                            <Button
                                                                type="button"
                                                                variant="ghost"
                                                                size="sm"
                                                                className="h-6 w-6 p-0"
                                                                onClick={() => handleMoveMedia(media.id, 'down')}
                                                                disabled={index === lab.documentation_media!.length - 1 || isReordering}
                                                                title="Déplacer vers le bas"
                                                            >
                                                                <ChevronDown className="h-4 w-4" />
                                                            </Button>
                                                        </div>
                                                        {/* Bouton modifier */}
                                                        <Button
                                                            type="button"
                                                            variant="ghost"
                                                            size="sm"
                                                            onClick={() => handleEditMedia(media)}
                                                            title="Modifier"
                                                        >
                                                            <Edit className="h-4 w-4" />
                                                        </Button>
                                                        {/* Bouton supprimer */}
                                                        <Button
                                                            type="button"
                                                            variant="ghost"
                                                            size="sm"
                                                            onClick={() => handleDeleteMedia(media.id)}
                                                            disabled={isDeleting === media.id}
                                                            className="text-destructive hover:text-destructive"
                                                            title="Supprimer"
                                                        >
                                                            {isDeleting === media.id ? (
                                                                <Clock className="h-4 w-4 animate-spin" />
                                                            ) : (
                                                                <Trash2 className="h-4 w-4" />
                                                            )}
                                                        </Button>
                                                    </div>
                                                )}
                                            </div>
                                        ))}
                                    </div>
                                ) : (
                                    <p className="text-muted-foreground text-center py-8">
                                        Aucun média ajouté
                                    </p>
                                )}
                            </CardContent>
                        </Card>
                    </div>

                    {/* Colonne latérale */}
                    <div className="space-y-6">
                        {/* Statut et badges */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Statut</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-3">
                                <div className="flex items-center justify-between">
                                    <span className="text-sm">État</span>
                                    <Badge>{lab.state || 'N/A'}</Badge>
                                </div>
                                <div className="flex items-center justify-between">
                                    <span className="text-sm">Publié</span>
                                    <Badge variant={lab.is_published ? 'default' : 'secondary'}>
                                        {lab.is_published ? 'Oui' : 'Non'}
                                    </Badge>
                                </div>
                                <div className="flex items-center justify-between">
                                    <span className="text-sm">Mis en avant</span>
                                    <Badge variant={lab.is_featured ? 'default' : 'secondary'}>
                                        {lab.is_featured ? 'Oui' : 'Non'}
                                    </Badge>
                                </div>
                                {lab.difficulty_level && (
                                    <div className="flex items-center justify-between">
                                        <span className="text-sm">Difficulté</span>
                                        <Badge>{lab.difficulty_level}</Badge>
                                    </div>
                                )}
                            </CardContent>
                        </Card>

                        {/* Statistiques */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Statistiques</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-3">
                                <div className="flex items-center justify-between">
                                    <span className="text-sm flex items-center gap-2">
                                        <Users className="h-4 w-4" />
                                        Réservations
                                    </span>
                                    <span className="font-semibold">{lab.reservation_count}</span>
                                </div>
                                <div className="flex items-center justify-between">
                                    <span className="text-sm flex items-center gap-2">
                                        <TrendingUp className="h-4 w-4" />
                                        Vues
                                    </span>
                                    <span className="font-semibold">{lab.view_count}</span>
                                </div>
                                {lab.rating && (
                                    <div className="flex items-center justify-between">
                                        <span className="text-sm flex items-center gap-2">
                                            <Star className="h-4 w-4" />
                                            Note
                                        </span>
                                        <span className="font-semibold">
                                            {lab.rating.toFixed(1)} ({lab.rating_count})
                                        </span>
                                    </div>
                                )}
                                {lab.estimated_duration_minutes && (
                                    <div className="flex items-center justify-between">
                                        <span className="text-sm flex items-center gap-2">
                                            <Clock className="h-4 w-4" />
                                            Durée
                                        </span>
                                        <span className="font-semibold">{lab.estimated_duration_minutes} min</span>
                                    </div>
                                )}
                                <div className="flex items-center justify-between">
                                    <span className="text-sm flex items-center gap-2">
                                        <DollarSign className="h-4 w-4" />
                                        Prix
                                    </span>
                                    <span className="font-semibold">{formatPrice(lab.price_cents, lab.currency)}</span>
                                </div>
                            </CardContent>
                        </Card>

                        {/* Détails techniques */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Détails techniques</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-2 text-sm">
                                <div className="flex justify-between">
                                    <span className="text-muted-foreground">Nœuds</span>
                                    <span>{lab.node_count || 0}</span>
                                </div>
                                <div className="flex justify-between">
                                    <span className="text-muted-foreground">Liens</span>
                                    <span>{lab.link_count || 0}</span>
                                </div>
                                <div className="flex justify-between">
                                    <span className="text-muted-foreground">Créé le</span>
                                    <span>{new Date(lab.created_at).toLocaleDateString('fr-FR')}</span>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}

