import { useEffect, useState } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useLabEvents } from '@/hooks/useLabEvents';
import { Loader2, RefreshCw, Filter, X } from 'lucide-react';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

type Props = {
    labId: string;
    nodeId?: string;
    interfaceId?: string;
    className?: string;
};

export default function LabEventsPanel({ labId, nodeId, interfaceId, className = '' }: Props) {
    const { loading, events, getLabEvents, getNodeEvents, getInterfaceEvents } = useLabEvents();
    const [filteredEvents, setFilteredEvents] = useState(events);
    const [filterType, setFilterType] = useState<string>('all');
    const [searchTerm, setSearchTerm] = useState('');
    const [limit, setLimit] = useState(50);

    useEffect(() => {
        if (labId) {
            void loadEvents();
        }
    }, [labId, nodeId, interfaceId, limit]);

    useEffect(() => {
        applyFilters();
    }, [events, filterType, searchTerm]);

    const loadEvents = async () => {
        if (interfaceId) {
            await getInterfaceEvents(labId, interfaceId, limit);
        } else if (nodeId) {
            await getNodeEvents(labId, nodeId, limit);
        } else {
            await getLabEvents(labId, { limit });
        }
    };

    const applyFilters = () => {
        let filtered = [...events];

        // Filtrer par type
        if (filterType !== 'all') {
            filtered = filtered.filter((event) => event.type === filterType);
        }

        // Filtrer par recherche
        if (searchTerm) {
            const term = searchTerm.toLowerCase();
            filtered = filtered.filter(
                (event) =>
                    event.message?.toLowerCase().includes(term) ||
                    event.source?.toLowerCase().includes(term) ||
                    event.type?.toLowerCase().includes(term)
            );
        }

        setFilteredEvents(filtered);
    };

    const getEventTypeColor = (type?: string) => {
        if (!type) return 'secondary';
        if (type.includes('error') || type.includes('fail')) return 'destructive';
        if (type.includes('warning')) return 'default';
        if (type.includes('success') || type.includes('start')) return 'default';
        return 'secondary';
    };

    const getEventIcon = (type?: string) => {
        if (!type) return '📋';
        if (type.includes('error') || type.includes('fail')) return '❌';
        if (type.includes('warning')) return '⚠️';
        if (type.includes('success') || type.includes('start')) return '✅';
        return 'ℹ️';
    };

    // Extraire les types uniques pour le filtre
    const eventTypes = Array.from(new Set(events.map((e) => e.type).filter(Boolean)));

    return (
        <Card className={className}>
            <CardHeader>
                <div className="flex items-center justify-between">
                    <CardTitle>
                        {interfaceId ? 'Événements Interface' : nodeId ? 'Événements Node' : 'Événements Lab'}
                    </CardTitle>
                    <Button
                        variant="outline"
                        size="sm"
                        onClick={() => void loadEvents()}
                        disabled={loading}
                    >
                        <RefreshCw className={`h-4 w-4 mr-2 ${loading ? 'animate-spin' : ''}`} />
                        Actualiser
                    </Button>
                </div>
            </CardHeader>
            <CardContent>
                {/* Filtres */}
                <div className="flex gap-2 mb-4">
                    <div className="flex-1">
                        <Input
                            placeholder="Rechercher..."
                            value={searchTerm}
                            onChange={(e) => setSearchTerm(e.target.value)}
                            className="h-9"
                        />
                    </div>
                    <Select value={filterType} onValueChange={setFilterType}>
                        <SelectTrigger className="w-40">
                            <SelectValue placeholder="Type" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Tous</SelectItem>
                            {eventTypes.map((type) => (
                                <SelectItem key={type} value={type}>
                                    {type}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    {searchTerm && (
                        <Button
                            variant="ghost"
                            size="sm"
                            onClick={() => setSearchTerm('')}
                        >
                            <X className="h-4 w-4" />
                        </Button>
                    )}
                </div>

                {/* Liste des événements */}
                {loading && events.length === 0 ? (
                    <div className="flex items-center justify-center p-8">
                        <Loader2 className="h-8 w-8 animate-spin text-muted-foreground" />
                    </div>
                ) : filteredEvents.length === 0 ? (
                    <div className="text-center text-muted-foreground py-8">
                        {events.length === 0 ? 'Aucun événement' : 'Aucun événement ne correspond aux filtres'}
                    </div>
                ) : (
                    <div className="space-y-2 max-h-96 overflow-y-auto">
                        {filteredEvents.map((event) => (
                            <Card key={event.id} className="p-3">
                                <div className="flex items-start gap-3">
                                    <div className="text-2xl">{getEventIcon(event.type)}</div>
                                    <div className="flex-1 min-w-0">
                                        <div className="flex items-center gap-2 mb-1">
                                            <Badge variant={getEventTypeColor(event.type)}>
                                                {event.type || 'unknown'}
                                            </Badge>
                                            {event.source && (
                                                <Badge variant="outline" className="text-xs">
                                                    {event.source}
                                                </Badge>
                                            )}
                                            <span className="text-xs text-muted-foreground ml-auto">
                                                {event.timestamp
                                                    ? new Date(event.timestamp).toLocaleString()
                                                    : 'N/A'}
                                            </span>
                                        </div>
                                        <div className="text-sm">{event.message || 'Événement sans message'}</div>
                                        {event.details && (
                                            <details className="mt-2">
                                                <summary className="text-xs text-muted-foreground cursor-pointer">
                                                    Détails
                                                </summary>
                                                <pre className="mt-2 text-xs bg-muted p-2 rounded overflow-auto">
                                                    {JSON.stringify(event.details, null, 2)}
                                                </pre>
                                            </details>
                                        )}
                                    </div>
                                </div>
                            </Card>
                        ))}
                    </div>
                )}

                {/* Statistiques */}
                <div className="mt-4 pt-4 border-t flex items-center justify-between text-sm text-muted-foreground">
                    <span>
                        {filteredEvents.length} / {events.length} événement(s)
                    </span>
                    <div className="flex items-center gap-2">
                        <span>Limite:</span>
                        <Select value={limit.toString()} onValueChange={(v) => setLimit(Number(v))}>
                            <SelectTrigger className="w-20 h-8">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="25">25</SelectItem>
                                <SelectItem value="50">50</SelectItem>
                                <SelectItem value="100">100</SelectItem>
                                <SelectItem value="200">200</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </div>
            </CardContent>
        </Card>
    );
}


