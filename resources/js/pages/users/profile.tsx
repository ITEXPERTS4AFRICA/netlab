import { Head, usePage } from '@inertiajs/react';
import { type SharedData } from '@/types';
import AppLayout from '@/layouts/app-layout';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { 
    User, Mail, Phone, Building2, Briefcase, GraduationCap, 
    Award, Calendar, Clock, Edit, Settings 
} from 'lucide-react';
import { Link } from '@inertiajs/react';

export default function UserProfile() {
    const { auth } = usePage<SharedData>().props;
    const user = auth.user;

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
            <Head title="Mon Profil" />
            
            <div className="container mx-auto py-8 space-y-6">
                {/* En-tête du profil */}
                <Card>
                    <CardContent className="pt-6">
                        <div className="flex items-start gap-6">
                            <Avatar className="h-24 w-24">
                                <AvatarImage src={user.avatar || undefined} alt={user.name} />
                                <AvatarFallback className="text-2xl">
                                    {user.name.charAt(0).toUpperCase()}
                                </AvatarFallback>
                            </Avatar>
                            
                            <div className="flex-1">
                                <div className="flex items-center gap-3 mb-2">
                                    <h1 className="text-3xl font-bold">{user.name}</h1>
                                    <Badge className={getRoleBadgeColor(user.role || 'user')}>
                                        {user.role || 'user'}
                                    </Badge>
                                </div>
                                
                                {user.bio && (
                                    <p className="text-muted-foreground mb-4">{user.bio}</p>
                                )}
                                
                                <div className="flex flex-wrap gap-4 text-sm text-muted-foreground">
                                    {user.email && (
                                        <div className="flex items-center gap-2">
                                            <Mail className="h-4 w-4" />
                                            <span>{user.email}</span>
                                        </div>
                                    )}
                                    {user.phone && (
                                        <div className="flex items-center gap-2">
                                            <Phone className="h-4 w-4" />
                                            <span>{user.phone}</span>
                                        </div>
                                    )}
                                    {user.organization && (
                                        <div className="flex items-center gap-2">
                                            <Building2 className="h-4 w-4" />
                                            <span>{user.organization}</span>
                                        </div>
                                    )}
                                    {user.department && (
                                        <div className="flex items-center gap-2">
                                            <Briefcase className="h-4 w-4" />
                                            <span>{user.department}</span>
                                        </div>
                                    )}
                                </div>
                                
                                <div className="mt-4">
                                    <Link href="/settings/profile">
                                        <Button variant="outline" size="sm">
                                            <Edit className="h-4 w-4 mr-2" />
                                            Modifier le profil
                                        </Button>
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                    {/* Statistiques */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-lg">Statistiques</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="flex items-center justify-between">
                                <span className="text-sm text-muted-foreground">Réservations totales</span>
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

                    {/* Compétences */}
                    {user.skills && Array.isArray(user.skills) && user.skills.length > 0 && (
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-lg flex items-center gap-2">
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
                    {user.certifications && Array.isArray(user.certifications) && user.certifications.length > 0 && (
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-lg flex items-center gap-2">
                                    <GraduationCap className="h-5 w-5" />
                                    Certifications
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="space-y-2">
                                    {user.certifications.map((cert: any, index: number) => (
                                        <div key={index} className="text-sm">
                                            <div className="font-medium">{cert.name || cert}</div>
                                            {cert.date && (
                                                <div className="text-muted-foreground text-xs">
                                                    {new Date(cert.date).toLocaleDateString('fr-FR')}
                                                </div>
                                            )}
                                        </div>
                                    ))}
                                </div>
                            </CardContent>
                        </Card>
                    )}
                </div>

                {/* Formation */}
                {user.education && Array.isArray(user.education) && user.education.length > 0 && (
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-lg flex items-center gap-2">
                                <GraduationCap className="h-5 w-5" />
                                Formation
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-4">
                                {user.education.map((edu: any, index: number) => (
                                    <div key={index} className="border-l-2 border-primary pl-4">
                                        <div className="font-semibold">{edu.degree || edu.school || edu}</div>
                                        {edu.school && <div className="text-sm text-muted-foreground">{edu.school}</div>}
                                        {edu.period && <div className="text-xs text-muted-foreground">{edu.period}</div>}
                                    </div>
                                ))}
                            </div>
                        </CardContent>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}

