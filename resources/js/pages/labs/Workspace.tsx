import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, usePage } from '@inertiajs/react';
import { Card, CardTitle, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import AnnotationLab from '@/components/app-annotation';
import {
    ArrowLeft,
    Play,
    Square,
    Network,
    Clock,
    Info,
    AlertTriangle,
    CheckCircle,
    Edit,
    Eye,
    Share2,
    ExternalLink
} from 'lucide-react';
import { useState } from 'react';
import { router } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Labs',
        href: '/labs',
    },
    {
        title: 'Workspace',
        href: '#',
    }
];

type Lab = {
    id: string;
    cml_id: string;
    state: string;
    lab_title: string;
    node_count: string|number;
    lab_description: string;
    created: string;
    modified: string;
    owner: string;
    link_count: number;
    effective_permissions: string[];
};

type Props = {
    lab: Lab;
};

export default function Workspace() {
    const { lab } = usePage<Props>().props;
    const [editMode, setEditMode] = useState(false);

    const handleStartLab = () => {
        if (confirm('Are you sure you want to start this lab?')) {
            router.post(`/labs/${lab.id}/start`, {}, {
                preserveScroll: true,
                onSuccess: () => {
                    // Reload the page to get updated lab state
                    window.location.reload();
                }
            });
        }
    };

    const handleStopLab = () => {
        if (confirm('Are you sure you want to stop this lab? This will disconnect all active sessions.')) {
            router.post(`/labs/${lab.id}/stop`, {}, {
                preserveScroll: true,
                onSuccess: () => {
                    // Reload the page to get updated lab state
                    window.location.reload();
                }
            });
        }
    };

    const openInCML = () => {
        window.open(`https://54.38.146.213/lab/${lab.cml_id}`, '_blank');
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`${lab.lab_title} - Workspace`} />

            <div className="flex h-full flex-col gap-4 overflow-hidden p-4">
                {/* Header Section */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() => router.visit('/labs')}
                            className="flex items-center gap-2"
                        >
                            <ArrowLeft className="h-4 w-4" />
                            Back to Labs
                        </Button>

                        <div className="flex items-center gap-3">
                            <div className="flex-shrink-0">
                                <CardTitle className="text-2xl font-bold">
                                    {lab.lab_title}
                                </CardTitle>
                            </div>

                            {/* Status Badge */}
                            <Badge
                                variant={
                                    lab.state === 'RUNNING' ? 'default' :
                                    lab.state === 'STOPPED' ? 'destructive' :
                                    lab.state === 'STARTING' ? 'secondary' :
                                    lab.state === 'STOPPING' ? 'secondary' :
                                    'outline'
                                }
                                className={`flex items-center gap-1.5 px-3 py-1 text-sm ${
                                    lab.state === 'RUNNING'
                                        ? 'bg-emerald-100 text-emerald-800 border-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-300 dark:border-emerald-800'
                                        : lab.state === 'STOPPED'
                                        ? 'bg-red-100 text-red-800 border-red-200 dark:bg-red-900/30 dark:text-red-300 dark:border-red-800'
                                        : lab.state === 'STARTING' || lab.state === 'STOPPING'
                                        ? 'bg-blue-100 text-blue-800 border-blue-200 dark:bg-blue-900/30 dark:text-blue-300 dark:border-blue-800'
                                        : 'bg-gray-100 text-gray-800 border-gray-200 dark:bg-gray-900/30 dark:text-gray-300 dark:border-gray-800'
                                }`}
                            >
                                {lab.state === 'RUNNING' ? (
                                    <CheckCircle className="h-4 w-4" />
                                ) : lab.state === 'STOPPED' ? (
                                    <AlertTriangle className="h-4 w-4" />
                                ) : lab.state === 'STARTING' || lab.state === 'STOPPING' ? (
                                    <Clock className="h-4 w-4" />
                                ) : (
                                    <Info className="h-4 w-4" />
                                )}
                                {lab.state.charAt(0).toUpperCase() + lab.state.slice(1).toLowerCase()}
                            </Badge>
                        </div>
                    </div>

                    {/* Action Buttons */}
                    <div className="flex items-center gap-2">
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() => setEditMode(!editMode)}
                            className={`flex items-center gap-2 ${
                                editMode ? 'border-blue-500 bg-blue-50 text-blue-700 dark:bg-blue-900/20 dark:text-blue-300' : ''
                            }`}
                        >
                            {editMode ? <Eye className="h-4 w-4" /> : <Edit className="h-4 w-4" />}
                            {editMode ? 'View Mode' : 'Edit Annotations'}
                        </Button>

                        {lab.state === 'STOPPED' && (
                            <Button
                                onClick={handleStartLab}
                                size="sm"
                                className="flex items-center gap-2 bg-green-600 hover:bg-green-700"
                            >
                                <Play className="h-4 w-4" />
                                Start Lab
                            </Button>
                        )}

                        {lab.state === 'RUNNING' && (
                            <Button
                                onClick={handleStopLab}
                                variant="destructive"
                                size="sm"
                                className="flex items-center gap-2"
                            >
                                <Square className="h-4 w-4" />
                                Stop Lab
                            </Button>
                        )}

                        <Button
                            onClick={openInCML}
                            size="sm"
                            className="flex items-center gap-2"
                        >
                            <ExternalLink className="h-4 w-4" />
                            Open in CML
                        </Button>
                    </div>
                </div>

                {/* Lab Info Card */}
                <Card className="border-0 shadow-sm">
                    <CardContent className="p-4">
                        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div className="flex items-center gap-3">
                                <div className="w-10 h-10 rounded-lg bg-blue-100 dark:bg-blue-900/20 flex items-center justify-center">
                                    <Network className="w-5 h-5 text-blue-600 dark:text-blue-400" />
                                </div>
                                <div>
                                    <p className="text-sm font-medium text-gray-900 dark:text-white">{lab.node_count}</p>
                                    <p className="text-xs text-gray-600 dark:text-gray-400">devices</p>
                                </div>
                            </div>

                            <div className="flex items-center gap-3">
                                <div className="w-10 h-10 rounded-lg bg-purple-100 dark:bg-purple-900/20 flex items-center justify-center">
                                    <Clock className="w-5 h-5 text-purple-600 dark:text-purple-400" />
                                </div>
                                <div>
                                    <p className="text-sm font-medium text-gray-900 dark:text-white">
                                        {new Date(lab.modified).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}
                                    </p>
                                    <p className="text-xs text-gray-600 dark:text-gray-400">last modified</p>
                                </div>
                            </div>

                            <div className="flex items-center gap-3">
                                <div className="w-10 h-10 rounded-lg bg-green-100 dark:bg-green-900/20 flex items-center justify-center">
                                    <Share2 className="w-5 h-5 text-green-600 dark:text-green-400" />
                                </div>
                                <div>
                                    <p className="text-sm font-medium text-gray-900 dark:text-white">{lab.owner}</p>
                                    <p className="text-xs text-gray-600 dark:text-gray-400">owner</p>
                                </div>
                            </div>
                        </div>

                        {lab.lab_description && (
                            <>
                                <Separator className="my-4" />
                                <p className="text-sm text-gray-700 dark:text-gray-300 leading-relaxed">
                                    {lab.lab_description}
                                </p>
                            </>
                        )}
                    </CardContent>
                </Card>

                {/* Workspace Area */}
                <div className="flex-1 relative bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
                    {/* Annotations Canvas */}
                    <div className="absolute inset-0">
                        <AnnotationLab
                            labId={lab.id}
                            editMode={editMode}
                            onEditModeChange={setEditMode}
                            className="w-full h-full"
                        />
                    </div>

                    {/* Edit Mode Overlay */}
                    {editMode && (
                        <div className="absolute top-4 right-4 bg-white dark:bg-gray-800 rounded-lg shadow-lg p-3 border border-gray-200 dark:border-gray-700 z-10">
                            <div className="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                <Edit className="h-4 w-4 text-blue-600" />
                                <span>Edit Mode Active</span>
                            </div>
                            <p className="text-xs text-gray-600 dark:text-gray-400 mt-1">
                                Drag annotations to reposition • Changes are auto-saved
                            </p>
                        </div>
                    )}

                    {/* Lab State Overlay */}
                    {!editMode && lab.state !== 'RUNNING' && (
                        <div className="absolute inset-0 bg-black bg-opacity-50 flex items-center justify-center z-10">
                            <div className="bg-white dark:bg-gray-800 rounded-lg shadow-xl p-6 max-w-md mx-4 text-center">
                                <div className="w-16 h-16 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center mx-auto mb-4">
                                    {lab.state === 'STOPPED' ? (
                                        <AlertTriangle className="h-8 w-8 text-gray-600 dark:text-gray-400" />
                                    ) : lab.state === 'STARTING' || lab.state === 'STOPPING' ? (
                                        <div className="w-8 h-8 border-4 border-blue-600 border-t-transparent rounded-full animate-spin"></div>
                                    ) : (
                                        <Info className="h-8 w-8 text-gray-600 dark:text-gray-400" />
                                    )}
                                </div>
                                <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                                    Lab {lab.state.toLowerCase()}
                                </h3>
                                <p className="text-sm text-gray-600 dark:text-gray-400 mb-4">
                                    {lab.state === 'STOPPED' && 'The lab is currently stopped. Start it to view and interact with annotations.'}
                                    {lab.state === 'STARTING' && 'The lab is starting up. This may take a few minutes...'}
                                    {lab.state === 'STOPPING' && 'The lab is stopping. Please wait...'}
                                </p>
                                {lab.state === 'STOPPED' && (
                                    <Button onClick={handleStartLab} className="bg-green-600 hover:bg-green-700">
                                        <Play className="h-4 w-4 mr-2" />
                                        Start Lab
                                    </Button>
                                )}
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
