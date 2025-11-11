import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, usePage } from '@inertiajs/react';
import { Card, CardTitle, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import AnnotationLab from '@/components/AnnotationLab';
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
    ExternalLink,
    Timer
} from 'lucide-react';
import LabConsolePanel, { type ConsoleSession, type ConsoleSessionsResponse } from '@/components/lab-console-panel';
import { useEffect, useMemo, useState } from 'react';
import { router } from '@inertiajs/react';
import { toast } from 'sonner';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Labs',
        href: '/labs',
    },
    {
        title: 'My Reservations',
        href: '/labs/my-reserved',
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

type Reservation = {
    id: number;
    start_at: string;
    end_at: string;
    status: string;
};

type LabNode = {
    id: string;
    label?: string;
    name?: string;
    state?: string;
    node_definition?: string;
};

type Props = {
    lab: Lab;
    reservation: Reservation | null;
    nodes: LabNode[] | Record<string, LabNode>;
    consoleSessions: ConsoleSession[] | ConsoleSessionsResponse | null;
};

export default function Workspace() {
    const { lab, reservation, nodes, consoleSessions } = usePage<Props>().props;
    const [editMode, setEditMode] = useState(false);
    const [timeLeft, setTimeLeft] = useState<number>(0);

    const nodeList = useMemo<LabNode[]>(() => {
        if (Array.isArray(nodes)) {
            return nodes;
        }
        if (nodes && typeof nodes === 'object') {
            return Object.values(nodes);
        }
        return [];
    }, [nodes]);

    useEffect(() => {
        if (!reservation) return;

        const interval = setInterval(() => {
            const now = new Date().getTime();
            const end = new Date(reservation.end_at).getTime();
            const remaining = end - now;

            if (remaining <= 0) {
                clearInterval(interval);
                toast.error('Session ended. Redirecting to dashboard...', {
                    duration: 3000,
                });
                setTimeout(() => {
                    router.visit('/dashboard');
                }, 3000);
            } else {
                setTimeLeft(Math.floor(remaining / 1000));
            }
        }, 1000);

        return () => clearInterval(interval);
    }, [reservation]);

    const formatTime = (seconds: number) => {
        const hrs = Math.floor(seconds / 3600);
        const mins = Math.floor((seconds % 3600) / 60);
        const secs = seconds % 60;
        return `${hrs.toString().padStart(2, '0')}h${mins.toString().padStart(2, '0')}m${secs.toString().padStart(2, '0')}s`;
    };

    const handleStartLab = () => {
        if (confirm('Are you sure you want to start this lab?')) {
            router.post(`/api/labs/${lab.id}/start`, {}, {
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
            router.post(`/api/labs/${lab.id}/stop`, {}, {
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
                        <div className={`grid grid-cols-1 ${reservation ? 'md:grid-cols-4' : 'md:grid-cols-3'} gap-4`}>
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

                            {reservation && (
                                <div className="flex items-center gap-3">
                                    <div className={`w-10 h-10 rounded-lg bg-orange-100 dark:bg-orange-900/20 flex items-center justify-center ${timeLeft < 300 ? 'animate-pulse bg-red-100 dark:bg-red-900/20' : ''}`}>
                                        <Timer className={`w-5 h-5 ${timeLeft < 300 ? 'text-red-600 dark:text-red-400' : 'text-orange-600 dark:text-orange-400'}`} />
                                    </div>
                                    <div>
                                        <p className="text-sm font-medium text-gray-900 dark:text-white">
                                            {formatTime(timeLeft)}
                                        </p>
                                        <p className="text-xs text-gray-600 dark:text-gray-400">
                                            {timeLeft < 3600 ? 'remaining (min:sec)' : 'remaining (hr:min:sec)'}
                                        </p>
                                    </div>
                                </div>
                            )}
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
                <div className="flex flex-1 flex-col gap-4 overflow-hidden lg:flex-row">
                    <div className="relative flex-1 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
                        {/* Annotations Canvas */}
                        <div className="absolute inset-0">
                            <AnnotationLab
                                labId={lab.id}
                                editMode={editMode}
                                onEditModeChange={setEditMode}
                                className="h-full w-full"
                            />
                        </div>

                        {/* Edit Mode Overlay */}
                        {editMode && (
                            <div className="absolute right-4 top-4 z-10 rounded-lg border border-gray-200 bg-white p-3 shadow-lg dark:border-gray-700 dark:bg-gray-800">
                                <div className="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                    <Edit className="h-4 w-4 text-blue-600" />
                                    <span>Edit Mode Active</span>
                                </div>
                                <p className="mt-1 text-xs text-gray-600 dark:text-gray-400">
                                    Drag annotations to reposition • Changes are auto-saved
                                </p>
                            </div>
                        )}

                        {/* Lab State Overlay */}
                        {!editMode && lab.state !== 'RUNNING' && (
                            <div className="absolute inset-0 z-10 flex items-center justify-center bg-black bg-opacity-50">
                                <div className="mx-4 max-w-md rounded-lg bg-white p-6 text-center shadow-xl dark:bg-gray-800">
                                    <div className="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-700">
                                        {lab.state === 'STOPPED' ? (
                                            <AlertTriangle className="h-8 w-8 text-gray-600 dark:text-gray-400" />
                                        ) : lab.state === 'STARTING' || lab.state === 'STOPPING' ? (
                                            <div className="h-8 w-8 animate-spin rounded-full border-4 border-blue-600 border-t-transparent"></div>
                                        ) : (
                                            <Info className="h-8 w-8 text-gray-600 dark:text-gray-400" />
                                        )}
                                    </div>
                                    <h3 className="mb-2 text-lg font-semibold text-gray-900 dark:text-white">
                                        Lab {lab.state.toLowerCase()}
                                    </h3>
                                    <p className="mb-4 text-sm text-gray-600 dark:text-gray-400">
                                        {lab.state === 'STOPPED' && 'The lab is currently stopped. Start it to view and interact with annotations.'}
                                        {lab.state === 'STARTING' && 'The lab is starting up. This may take a few minutes...'}
                                        {lab.state === 'STOPPING' && 'The lab is stopping. Please wait...'}
                                    </p>
                                    {lab.state === 'STOPPED' && (
                                        <Button onClick={handleStartLab} className="bg-green-600 hover:bg-green-700">
                                            <Play className="mr-2 h-4 w-4" />
                                            Start Lab
                                        </Button>
                                    )}
                                </div>
                            </div>
                        )}
                    </div>

                    <div className="min-h-[28rem] w-full flex-shrink-0 overflow-hidden lg:w-[420px]">
                        <LabConsolePanel
                            cmlLabId={lab.cml_id}
                            nodes={nodeList}
                            initialSessions={consoleSessions}
                        />
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
