import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Separator } from '@/components/ui/separator';
import { 
    ArrowLeft, Edit, Trash2, Mail, Phone, Building2, 
    Briefcase, Award, GraduationCap, Calendar, UserCheck, UserX
} from 'lucide-react';

interface User {
    id: string;
    name: string;
    email: string;
    role: string;
    is_active: boolean;
    avatar?: string;
    bio?: string;
    phone?: string;
    organization?: string;
    department?: string;
    position?: string;
    skills?: string[];
    certifications?: any[];
    education?: any[];
    total_reservations?: number;
    total_labs_completed?: number;
    created_at: string;
    last_activity_at?: string;
    reservations_count?: number;
    reservations?: any[];
}

interface Props {
    user: User;
}

export default function UserShow({ user }: Props) {
    const getRoleBadgeColor = (role: string) => {
        switch (role) {
            case 'admin': return 'bg-red-500';
            case 'instructor': return 'bg-blue-500';
            case 'student': return 'bg-green-500';
            default: return 'bg-gray-500';
        }
    };

    return (
        <AppLayout>
            <Head title={`Utilisateur: ${user.name}`} />
            
            <div className="container mx-auto py-8 space-y-6">
                {/* En-tête */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <Link href="/admin/users">
                            <Button variant="ghost" size="sm">
                                <ArrowLeft className="h-4 w-4 mr-2" />
                                Retour
                            </Button>
                        </Link>
                        <div>
                            <h1 className="text-3xl font-bold">{user.name}</h1>
                            <p className="text-muted-foreground">Détails de l'utilisateur</p>
                        </div>
                    </div>
                    <div className="flex gap-2">
                        <Link href={`/admin/users/${user.id}/edit`}>
                            <Button>
                                <Edit className="h-4 w-4 mr-2" />
                                Modifier
                            </Button>
                        </Link>
                        <Button
                            variant="destructive"
                            onClick={() => {
                                if (confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?')) {
                                    router.delete(`/admin/users/${user.id}`);
                                }
                            }}
                        >
                            <Trash2 className="h-4 w-4 mr-2" />
                            Supprimer
                        </Button>
                    </div>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {/* Informations principales */}
                    <div className="lg:col-span-2 space-y-6">
                        <Card>
                            <CardHeader>
                                <div className="flex items-center gap-4">
                                    <Avatar className="h-20 w-20">
                                        <AvatarImage src={user.avatar || undefined} />
                                        <AvatarFallback className="text-2xl">
                                            {user.name.charAt(0).toUpperCase()}
                                        </AvatarFallback>
                                    </Avatar>
                                    <div>
                                        <CardTitle className="flex items-center gap-2">
                                            {user.name}
                                            <Badge className={getRoleBadgeColor(user.role)}>
                                                {user.role}
                                            </Badge>
                                            {user.is_active ? (
                                                <Badge variant="outline" className="text-green-600">
                                                    <UserCheck className="h-3 w-3 mr-1" />
                                                    Actif
                                                </Badge>
                                            ) : (
                                                <Badge variant="outline" className="text-red-600">
                                                    <UserX className="h-3 w-3 mr-1" />
                                                    Inactif
                                                </Badge>
                                            )}
                                        </CardTitle>
                                        <CardDescription>{user.email}</CardDescription>
                                    </div>
                                </div>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                {user.bio && (
                                    <>
                                        <div>
                                            <h4 className="font-semibold mb-2">Biographie</h4>
                                            <p className="text-sm text-muted-foreground">{user.bio}</p>
                                        </div>
                                        <Separator />
                                    </>
                                )}
                                
                                <div className="grid grid-cols-2 gap-4">
                                    {user.phone && (
                                        <div className="flex items-center gap-2">
                                            <Phone className="h-4 w-4 text-muted-foreground" />
                                            <span className="text-sm">{user.phone}</span>
                                        </div>
                                    )}
                                    {user.organization && (
                                        <div className="flex items-center gap-2">
                                            <Building2 className="h-4 w-4 text-muted-foreground" />
                                            <span className="text-sm">{user.organization}</span>
                                        </div>
                                    )}
                                    {user.department && (
                                        <div className="flex items-center gap-2">
                                            <Briefcase className="h-4 w-4 text-muted-foreground" />
                                            <span className="text-sm">{user.department}</span>
                                        </div>
                                    )}
                                    {user.position && (
                                        <div className="flex items-center gap-2">
                                            <Briefcase className="h-4 w-4 text-muted-foreground" />
                                            <span className="text-sm">{user.position}</span>
                                        </div>
                                    )}
                                </div>
                            </CardContent>
                        </Card>

                        {/* Compétences */}
                        {user.skills && user.skills.length > 0 && (
                            <Card>
                                <CardHeader>
                                    <CardTitle className="flex items-center gap-2">
                                        <Award className="h-5 w-5" />
                                        Compétences
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div className="flex flex-wrap gap-2">
                                        {user.skills.map((skill: string, index: number) => (
                                            <Badge key={index} variant="secondary">
                                                {skill}
                                            </Badge>
                                        ))}
                                    </div>
                                </CardContent>
                            </Card>
                        )}

                        {/* Certifications */}
                        {user.certifications && user.certifications.length > 0 && (
                            <Card>
                                <CardHeader>
                                    <CardTitle className="flex items-center gap-2">
                                        <GraduationCap className="h-5 w-5" />
                                        Certifications
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div className="space-y-3">
                                        {user.certifications.map((cert: any, index: number) => (
                                            <div key={index} className="border-l-2 border-primary pl-4">
                                                <div className="font-medium">{cert.name || cert}</div>
                                                {cert.date && (
                                                    <div className="text-sm text-muted-foreground">
                                                        {new Date(cert.date).toLocaleDateString('fr-FR')}
                                                    </div>
                                                )}
                                            </div>
                                        ))}
                                    </div>
                                </CardContent>
                            </Card>
                        )}

                        {/* Formation */}
                        {user.education && user.education.length > 0 && (
                            <Card>
                                <CardHeader>
                                    <CardTitle className="flex items-center gap-2">
                                        <GraduationCap className="h-5 w-5" />
                                        Formation
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div className="space-y-3">
                                        {user.education.map((edu: any, index: number) => (
                                            <div key={index} className="border-l-2 border-primary pl-4">
                                                <div className="font-medium">{edu.degree || edu.school || edu}</div>
                                                {edu.school && <div className="text-sm text-muted-foreground">{edu.school}</div>}
                                                {edu.period && <div className="text-xs text-muted-foreground">{edu.period}</div>}
                                            </div>
                                        ))}
                                    </div>
                                </CardContent>
                            </Card>
                        )}
                    </div>

                    {/* Sidebar */}
                    <div className="space-y-6">
                        <Card>
                            <CardHeader>
                                <CardTitle>Statistiques</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="flex items-center justify-between">
                                    <span className="text-sm text-muted-foreground">Réservations</span>
                                    <span className="font-semibold">{user.total_reservations || 0}</span>
                                </div>
                                <Separator />
                                <div className="flex items-center justify-between">
                                    <span className="text-sm text-muted-foreground">Labs complétés</span>
                                    <span className="font-semibold">{user.total_labs_completed || 0}</span>
                                </div>
                                <Separator />
                                <div className="flex items-center justify-between">
                                    <span className="text-sm text-muted-foreground">Membre depuis</span>
                                    <span className="font-semibold text-sm">
                                        {new Date(user.created_at).toLocaleDateString('fr-FR')}
                                    </span>
                                </div>
                                {user.last_activity_at && (
                                    <>
                                        <Separator />
                                        <div className="flex items-center justify-between">
                                            <span className="text-sm text-muted-foreground">Dernière activité</span>
                                            <span className="font-semibold text-sm">
                                                {new Date(user.last_activity_at).toLocaleDateString('fr-FR')}
                                            </span>
                                        </div>
                                    </>
                                )}
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}

