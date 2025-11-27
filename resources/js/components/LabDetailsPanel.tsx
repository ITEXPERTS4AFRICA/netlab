import { useEffect, useState } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useLabDetails } from '@/hooks/useLabDetails';
import { Loader2, RefreshCw, Server, Network, Link2, Activity } from 'lucide-react';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';

type Props = {
    labId: string;
    className?: string;
};

export default function LabDetailsPanel({ labId, className = '' }: Props) {
    const { loading, details, getLabDetails, getSimulationStats, getLayer3Addresses } = useLabDetails();
    const [simulationStats, setSimulationStats] = useState<unknown>(null);
    const [layer3Addresses, setLayer3Addresses] = useState<unknown>(null);

    useEffect(() => {
        if (labId) {
            void loadAllDetails();
        }
    }, [labId]);

    const loadAllDetails = async () => {
        await getLabDetails(labId);
        const stats = await getSimulationStats(labId);
        const addresses = await getLayer3Addresses(labId);
        setSimulationStats(stats);
        setLayer3Addresses(addresses);
    };

    if (loading && !details) {
        return (
            <Card className={className}>
                <CardContent className="flex items-center justify-center p-8">
                    <Loader2 className="h-8 w-8 animate-spin text-muted-foreground" />
                </CardContent>
            </Card>
        );
    }

    if (!details) {
        return (
            <Card className={className}>
                <CardContent className="p-8 text-center">
                    <p className="text-muted-foreground">Aucun détail disponible</p>
                    <Button onClick={() => void loadAllDetails()} className="mt-4">
                        <RefreshCw className="h-4 w-4 mr-2" />
                        Recharger
                    </Button>
                </CardContent>
            </Card>
        );
    }

    return (
        <Card className={className}>
            <CardHeader>
                <div className="flex items-center justify-between">
                    <CardTitle>Détails du Lab</CardTitle>
                    <Button
                        variant="outline"
                        size="sm"
                        onClick={() => void loadAllDetails()}
                        disabled={loading}
                    >
                        <RefreshCw className={`h-4 w-4 mr-2 ${loading ? 'animate-spin' : ''}`} />
                        Actualiser
                    </Button>
                </div>
            </CardHeader>
            <CardContent>
                <Tabs defaultValue="overview" className="w-full">
                    <TabsList className="grid w-full grid-cols-4">
                        <TabsTrigger value="overview">Vue d'ensemble</TabsTrigger>
                        <TabsTrigger value="nodes">Nodes</TabsTrigger>
                        <TabsTrigger value="links">Links</TabsTrigger>
                        <TabsTrigger value="stats">Statistiques</TabsTrigger>
                    </TabsList>

                    <TabsContent value="overview" className="space-y-4">
                        <div className="grid grid-cols-2 gap-4">
                            <div className="space-y-2">
                                <div className="flex items-center gap-2">
                                    <Server className="h-4 w-4 text-muted-foreground" />
                                    <span className="text-sm font-medium">État</span>
                                </div>
                                <Badge variant={details.state?.state === 'STARTED' ? 'default' : 'secondary'}>
                                    {details.state?.state || 'N/A'}
                                </Badge>
                            </div>
                            <div className="space-y-2">
                                <div className="flex items-center gap-2">
                                    <Network className="h-4 w-4 text-muted-foreground" />
                                    <span className="text-sm font-medium">Nodes</span>
                                </div>
                                <Badge variant="secondary">{details.nodes?.length || 0}</Badge>
                            </div>
                            <div className="space-y-2">
                                <div className="flex items-center gap-2">
                                    <Link2 className="h-4 w-4 text-muted-foreground" />
                                    <span className="text-sm font-medium">Links</span>
                                </div>
                                <Badge variant="secondary">{details.links?.length || 0}</Badge>
                            </div>
                            <div className="space-y-2">
                                <div className="flex items-center gap-2">
                                    <Activity className="h-4 w-4 text-muted-foreground" />
                                    <span className="text-sm font-medium">Événements</span>
                                </div>
                                <Badge variant="secondary">{details.events?.length || 0}</Badge>
                            </div>
                        </div>

                        {details.lab && (
                            <div className="space-y-2 pt-4 border-t">
                                <h4 className="font-semibold">Informations du Lab</h4>
                                <div className="grid grid-cols-2 gap-2 text-sm">
                                    <div>
                                        <span className="text-muted-foreground">Titre:</span>
                                        <span className="ml-2">{details.lab.title || 'N/A'}</span>
                                    </div>
                                    <div>
                                        <span className="text-muted-foreground">Description:</span>
                                        <span className="ml-2">{details.lab.description || 'N/A'}</span>
                                    </div>
                                    <div>
                                        <span className="text-muted-foreground">Propriétaire:</span>
                                        <span className="ml-2">{details.lab.owner_username || details.lab.owner || 'N/A'}</span>
                                    </div>
                                    <div>
                                        <span className="text-muted-foreground">Créé:</span>
                                        <span className="ml-2">
                                            {details.lab.created ? new Date(details.lab.created).toLocaleString() : 'N/A'}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        )}
                    </TabsContent>

                    <TabsContent value="nodes" className="space-y-2">
                        <div className="max-h-96 overflow-y-auto">
                            {details.nodes && details.nodes.length > 0 ? (
                                <div className="space-y-2">
                                    {details.nodes.map((node: any, index: number) => (
                                        <Card key={node.id || `node-${index}`} className="p-3">
                                            <div className="flex items-center justify-between">
                                                <div>
                                                    <div className="font-semibold">{node.label || node.id}</div>
                                                    <div className="text-sm text-muted-foreground">
                                                        {node.node_definition || 'N/A'}
                                                    </div>
                                                </div>
                                                <Badge
                                                    variant={
                                                        node.state === 'BOOTED' || node.state === 'STARTED'
                                                            ? 'default'
                                                            : 'secondary'
                                                    }
                                                >
                                                    {node.state || 'N/A'}
                                                </Badge>
                                            </div>
                                        </Card>
                                    ))}
                                </div>
                            ) : (
                                <p className="text-center text-muted-foreground py-8">Aucun node</p>
                            )}
                        </div>
                    </TabsContent>

                    <TabsContent value="links" className="space-y-2">
                        <div className="max-h-96 overflow-y-auto">
                            {details.links && details.links.length > 0 ? (
                                <div className="space-y-2">
                                    {details.links.map((link: any, index: number) => (
                                        <Card key={link.id || `link-${index}`} className="p-3">
                                            <div className="flex items-center justify-between">
                                                <div>
                                                    <div className="font-semibold">
                                                        {link.interface1?.label || link.i1 || '?'} ↔ {link.interface2?.label || link.i2 || '?'}
                                                    </div>
                                                    <div className="text-sm text-muted-foreground">
                                                        {link.n1 || link.node_a} → {link.n2 || link.node_b}
                                                    </div>
                                                </div>
                                                <Badge
                                                    variant={
                                                        link.state === 'STARTED' || link.state === 'BOOTED'
                                                            ? 'default'
                                                            : 'secondary'
                                                    }
                                                >
                                                    {link.state || 'N/A'}
                                                </Badge>
                                            </div>
                                        </Card>
                                    ))}
                                </div>
                            ) : (
                                <p className="text-center text-muted-foreground py-8">Aucun link</p>
                            )}
                        </div>
                    </TabsContent>

                    <TabsContent value="stats" className="space-y-4">
                        {simulationStats && (
                            <div className="space-y-2">
                                <h4 className="font-semibold">Statistiques de Simulation</h4>
                                <pre className="bg-muted p-4 rounded-lg text-xs overflow-auto">
                                    {JSON.stringify(simulationStats, null, 2)}
                                </pre>
                            </div>
                        )}
                        {layer3Addresses && (
                            <div className="space-y-2">
                                <h4 className="font-semibold">Adresses Layer 3</h4>
                                <pre className="bg-muted p-4 rounded-lg text-xs overflow-auto">
                                    {JSON.stringify(layer3Addresses, null, 2)}
                                </pre>
                            </div>
                        )}
                        {!simulationStats && !layer3Addresses && (
                            <p className="text-center text-muted-foreground py-8">Aucune statistique disponible</p>
                        )}
                    </TabsContent>
                </Tabs>
            </CardContent>
        </Card>
    );
}

