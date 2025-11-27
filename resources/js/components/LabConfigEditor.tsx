import { useEffect, useState, useCallback } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import { Badge } from '@/components/ui/badge';
import { useLabConfig } from '@/hooks/useLabConfig';
import { useRealtimeLogs } from '@/hooks/useRealtimeLogs';
import { Loader2, Save, RefreshCw, Download, Upload, AlertCircle } from 'lucide-react';
import { toast } from 'sonner';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { configUpdateNotifier } from '@/utils/configUpdateNotifier';

type Props = {
    labId: string;
    className?: string;
};

export default function LabConfigEditor({ labId, className = '' }: Props) {
    const { loading, config, getLabConfig, updateLabConfig } = useLabConfig();
    const { logs, addLog, clearLogs, refresh: refreshLogs } = useRealtimeLogs(labId, true, 2000);
    const [yamlContent, setYamlContent] = useState('');
    const [topologyJson, setTopologyJson] = useState('');
    const [isSaving, setIsSaving] = useState(false);
    const [activeTab, setActiveTab] = useState<'yaml' | 'json' | 'logs'>('yaml');

    // Charger la configuration au montage
    useEffect(() => {
        if (labId) {
            void loadConfig();
        }
    }, [labId]);

    const loadConfig = useCallback(async () => {
        const result = await getLabConfig(labId);
        if (result) {
            setYamlContent(result.yaml || '');
            setTopologyJson(JSON.stringify(result.topology || {}, null, 2));
            addLog({
                level: 'success',
                message: 'Configuration chargée avec succès',
                source: 'config-editor',
            });
        }
    }, [labId, getLabConfig, addLog]);

    const handleSave = useCallback(async () => {
        setIsSaving(true);
        addLog({
            level: 'info',
            message: 'Sauvegarde de la configuration en cours...',
            source: 'config-editor',
        });

        try {
            let topology: any = null;
            let yaml: string | undefined = undefined;

            if (activeTab === 'yaml' && yamlContent.trim()) {
                yaml = yamlContent;
            } else if (activeTab === 'json' && topologyJson.trim()) {
                try {
                    topology = JSON.parse(topologyJson);
                } catch (e) {
                    addLog({
                        level: 'error',
                        message: 'JSON invalide: ' + (e instanceof Error ? e.message : 'Erreur inconnue'),
                        source: 'config-editor',
                    });
                    toast.error('JSON invalide');
                    setIsSaving(false);
                    return;
                }
            }

            const success = await updateLabConfig(labId, topology, yaml);

            if (success) {
                addLog({
                    level: 'success',
                    message: 'Configuration sauvegardée avec succès',
                    source: 'config-editor',
                });
                toast.success('Configuration sauvegardée');
                
                // Recharger après un court délai pour voir les résultats
                setTimeout(() => {
                    void loadConfig();
                    void refreshLogs();
                }, 1000);
            }
        } catch (err) {
            addLog({
                level: 'error',
                message: 'Erreur lors de la sauvegarde: ' + (err instanceof Error ? err.message : 'Erreur inconnue'),
                source: 'config-editor',
            });
        } finally {
            setIsSaving(false);
        }
    }, [labId, yamlContent, topologyJson, activeTab, updateLabConfig, addLog, loadConfig, refreshLogs]);

    const handleDownload = useCallback(() => {
        const content = activeTab === 'yaml' ? yamlContent : topologyJson;
        const filename = `lab_${labId}_config.${activeTab === 'yaml' ? 'yaml' : 'json'}`;
        const blob = new Blob([content], { type: 'text/plain' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
        toast.success('Configuration téléchargée');
    }, [yamlContent, topologyJson, activeTab, labId]);

    const handleFileUpload = useCallback((e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (!file) return;

        const reader = new FileReader();
        reader.onload = (event) => {
            const content = event.target?.result as string;
            if (file.name.endsWith('.yaml') || file.name.endsWith('.yml')) {
                setYamlContent(content);
                setActiveTab('yaml');
            } else if (file.name.endsWith('.json')) {
                setTopologyJson(content);
                setActiveTab('json');
            }
            toast.success('Fichier chargé');
        };
        reader.readAsText(file);
    }, []);

    const getLogColor = (level: string) => {
        switch (level) {
            case 'error':
                return 'text-red-500';
            case 'warning':
                return 'text-yellow-500';
            case 'success':
                return 'text-green-500';
            default:
                return 'text-blue-500';
        }
    };

    return (
        <div className={`flex flex-col h-full min-h-[700px] ${className}`}>
            <Card className="flex-1 flex flex-col min-h-[700px]">
                <CardHeader>
                    <div className="flex items-center justify-between">
                        <CardTitle>Éditeur de Configuration du Lab</CardTitle>
                        <div className="flex gap-2">
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={() => void loadConfig()}
                                disabled={loading}
                            >
                                <RefreshCw className={`h-4 w-4 mr-2 ${loading ? 'animate-spin' : ''}`} />
                                Recharger
                            </Button>
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={handleDownload}
                            >
                                <Download className="h-4 w-4 mr-2" />
                                Télécharger
                            </Button>
                            <label>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    asChild
                                >
                                    <span>
                                        <Upload className="h-4 w-4 mr-2" />
                                        Charger fichier
                                    </span>
                                </Button>
                                <input
                                    type="file"
                                    accept=".yaml,.yml,.json"
                                    onChange={handleFileUpload}
                                    className="hidden"
                                />
                            </label>
                            <Button
                                onClick={handleSave}
                                disabled={isSaving || loading}
                            >
                                {isSaving ? (
                                    <>
                                        <Loader2 className="h-4 w-4 mr-2 animate-spin" />
                                        Sauvegarde...
                                    </>
                                ) : (
                                    <>
                                        <Save className="h-4 w-4 mr-2" />
                                        Sauvegarder
                                    </>
                                )}
                            </Button>
                        </div>
                    </div>
                </CardHeader>
                <CardContent className="flex-1 flex flex-col overflow-hidden">
                    <Tabs value={activeTab} onValueChange={(v) => setActiveTab(v as 'yaml' | 'json' | 'logs')} className="flex-1 flex flex-col">
                        <TabsList>
                            <TabsTrigger value="yaml">YAML</TabsTrigger>
                            <TabsTrigger value="json">JSON</TabsTrigger>
                            <TabsTrigger value="logs">
                                Logs en temps réel
                                {logs.length > 0 && (
                                    <Badge variant="secondary" className="ml-2">
                                        {logs.length}
                                    </Badge>
                                )}
                            </TabsTrigger>
                        </TabsList>
                        <TabsContent value="yaml" className="flex-1 flex flex-col mt-4">
                            <Textarea
                                value={yamlContent}
                                onChange={(e) => setYamlContent(e.target.value)}
                                className="flex-1 font-mono text-sm min-h-[600px]"
                                placeholder="Configuration YAML du lab..."
                            />
                        </TabsContent>
                        <TabsContent value="json" className="flex-1 flex flex-col mt-4">
                            <Textarea
                                value={topologyJson}
                                onChange={(e) => setTopologyJson(e.target.value)}
                                className="flex-1 font-mono text-sm min-h-[600px]"
                                placeholder="Configuration JSON de la topologie..."
                            />
                        </TabsContent>
                        <TabsContent value="logs" className="flex-1 flex flex-col mt-4">
                            <div className="flex items-center justify-between mb-2">
                                <div className="text-sm text-muted-foreground">
                                    {logs.length} entrée(s) de log
                                </div>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={clearLogs}
                                >
                                    Effacer
                                </Button>
                            </div>
                            <div className="flex-1 overflow-y-auto border rounded-lg p-4 bg-muted/50">
                                {logs.length === 0 ? (
                                    <div className="text-center text-muted-foreground py-8">
                                        Aucun log pour le moment
                                    </div>
                                ) : (
                                    <div className="space-y-2">
                                        {logs.map((log, index) => (
                                            <div
                                                key={`${log.id}-${index}-${log.timestamp}`}
                                                className="flex items-start gap-2 text-sm font-mono"
                                            >
                                                <span className="text-muted-foreground text-xs">
                                                    {new Date(log.timestamp).toLocaleTimeString()}
                                                </span>
                                                <Badge
                                                    variant={
                                                        log.level === 'error' ? 'destructive' :
                                                        log.level === 'warning' ? 'default' :
                                                        log.level === 'success' ? 'default' : 'secondary'
                                                    }
                                                    className="text-xs"
                                                >
                                                    {log.level.toUpperCase()}
                                                </Badge>
                                                <span className={getLogColor(log.level)}>
                                                    {log.message}
                                                </span>
                                                {log.source && (
                                                    <span className="text-muted-foreground text-xs">
                                                        [{log.source}]
                                                    </span>
                                                )}
                                            </div>
                                        ))}
                                    </div>
                                )}
                            </div>
                        </TabsContent>
                    </Tabs>
                </CardContent>
            </Card>
        </div>
    );
}

