import { Head, Link, useForm } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Checkbox } from '@/components/ui/checkbox';
import { ArrowLeft } from 'lucide-react';
import InputError from '@/components/input-error';

interface Lab {
    id: number;
    cml_id: string;
    lab_title: string;
    lab_description?: string;
    short_description?: string;
    price_cents?: number;
    currency?: string;
    readme?: string;
    tags?: string[];
    categories?: string[];
    difficulty_level?: string;
    estimated_duration_minutes?: number;
    is_featured: boolean;
    is_published: boolean;
    requirements?: string[];
    learning_objectives?: string[];
}

interface Props {
    lab: Lab;
}

export default function LabEdit({ lab }: Props) {
    const { data, setData, put, processing, errors } = useForm({
        lab_title: lab.lab_title || '',
        lab_description: lab.lab_description || '',
        short_description: lab.short_description || '',
        price_cents: lab.price_cents?.toString() || '',
        currency: lab.currency || 'XOF',
        readme: lab.readme || '',
        tags: lab.tags || [],
        categories: lab.categories || [],
        difficulty_level: lab.difficulty_level || '',
        estimated_duration_minutes: lab.estimated_duration_minutes?.toString() || '',
        is_featured: lab.is_featured || false,
        is_published: lab.is_published || false,
        requirements: lab.requirements || [],
        learning_objectives: lab.learning_objectives || [],
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        const submitData = {
            ...data,
            price_cents: data.price_cents ? parseInt(data.price_cents) : null,
            estimated_duration_minutes: data.estimated_duration_minutes ? parseInt(data.estimated_duration_minutes) : null,
        };
        put(`/admin/labs/${lab.id}`, {
            data: submitData,
        });
    };

    return (
        <AppLayout>
            <Head title={`Modifier ${lab.lab_title}`} />

            <div className="container mx-auto py-8 max-w-4xl">
                <div className="flex items-center gap-4 mb-6">
                    <Link href="/admin/labs">
                        <Button variant="ghost" size="sm">
                            <ArrowLeft className="h-4 w-4 mr-2" />
                            Retour
                        </Button>
                    </Link>
                    <div>
                        <h1 className="text-3xl font-bold">Modifier le lab</h1>
                        <p className="text-muted-foreground">{lab.lab_title}</p>
                    </div>
                </div>

                <form onSubmit={submit}>
                    <Card>
                        <CardHeader>
                            <CardTitle>Informations de base</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid gap-2">
                                <Label htmlFor="lab_title">Titre *</Label>
                                <Input
                                    id="lab_title"
                                    value={data.lab_title}
                                    onChange={(e) => setData('lab_title', e.target.value)}
                                    required
                                />
                                <InputError message={errors.lab_title} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="short_description">Description courte</Label>
                                <Textarea
                                    id="short_description"
                                    value={data.short_description}
                                    onChange={(e) => setData('short_description', e.target.value)}
                                    rows={3}
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="lab_description">Description complète</Label>
                                <Textarea
                                    id="lab_description"
                                    value={data.lab_description}
                                    onChange={(e) => setData('lab_description', e.target.value)}
                                    rows={5}
                                />
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="mt-6">
                        <CardHeader>
                            <CardTitle>Métadonnées</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid grid-cols-2 gap-4">
                                <div className="grid gap-2">
                                    <Label htmlFor="price_cents">Prix (en centimes)</Label>
                                    <Input
                                        id="price_cents"
                                        type="number"
                                        value={data.price_cents}
                                        onChange={(e) => setData('price_cents', e.target.value)}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="currency">Devise</Label>
                                    <Select value={data.currency} onValueChange={(value) => setData('currency', value)}>
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="XOF">XOF</SelectItem>
                                            <SelectItem value="EUR">EUR</SelectItem>
                                            <SelectItem value="USD">USD</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>

                            <div className="grid grid-cols-2 gap-4">
                                <div className="grid gap-2">
                                    <Label htmlFor="difficulty_level">Niveau de difficulté</Label>
                                    <Select value={data.difficulty_level} onValueChange={(value) => setData('difficulty_level', value)}>
                                        <SelectTrigger>
                                            <SelectValue placeholder="Sélectionner" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="beginner">Débutant</SelectItem>
                                            <SelectItem value="intermediate">Intermédiaire</SelectItem>
                                            <SelectItem value="advanced">Avancé</SelectItem>
                                            <SelectItem value="expert">Expert</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="estimated_duration_minutes">Durée estimée (minutes)</Label>
                                    <Input
                                        id="estimated_duration_minutes"
                                        type="number"
                                        value={data.estimated_duration_minutes}
                                        onChange={(e) => setData('estimated_duration_minutes', e.target.value)}
                                    />
                                </div>
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="readme">README (Markdown)</Label>
                                <Textarea
                                    id="readme"
                                    value={data.readme}
                                    onChange={(e) => setData('readme', e.target.value)}
                                    rows={10}
                                />
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="mt-6">
                        <CardHeader>
                            <CardTitle>Options</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="flex items-center space-x-2">
                                <Checkbox
                                    id="is_featured"
                                    checked={data.is_featured}
                                    onCheckedChange={(checked) => setData('is_featured', checked as boolean)}
                                />
                                <Label htmlFor="is_featured" className="cursor-pointer">
                                    Mettre en avant
                                </Label>
                            </div>

                            <div className="flex items-center space-x-2">
                                <Checkbox
                                    id="is_published"
                                    checked={data.is_published}
                                    onCheckedChange={(checked) => setData('is_published', checked as boolean)}
                                />
                                <Label htmlFor="is_published" className="cursor-pointer">
                                    Publier (rendre visible)
                                </Label>
                            </div>
                        </CardContent>
                    </Card>

                    <div className="flex justify-end gap-4 mt-6">
                        <Link href="/admin/labs">
                            <Button type="button" variant="outline">
                                Annuler
                            </Button>
                        </Link>
                        <Button type="submit" disabled={processing}>
                            {processing ? 'Enregistrement...' : 'Enregistrer'}
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}

