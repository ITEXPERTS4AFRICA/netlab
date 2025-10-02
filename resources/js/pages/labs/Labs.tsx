import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, usePage } from '@inertiajs/react';
import { Card, CardTitle, CardContent, CardHeader, CardDescription } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Skeleton } from '@/components/ui/skeleton';
import { PaginationApp } from '@/components/app-pagination';
import LabReservationDialog from '@/components/lab-reservation-dialog';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogTrigger } from '@/components/ui/dialog';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import AnnotationLab from '@/components/app-annotation';
import {
        AlertCircle,
        CheckCircle,
        Calendar,
        Eye,
        Network,
        Clock,
        Info,
        AlertTriangle,
        Search
    } from 'lucide-react';
import { motion, Variants } from 'framer-motion';
import { useState, useMemo, useEffect } from 'react';
import { Input } from '@/components/ui/input';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Labs',
        href: '/labs',
    }
];

type Lab = {
    id: string;
    state: string;
    lab_title: string;
    node_count: string|number;
    lab_description: string;
    created: string;
    modified: string;
};

type Pagination = {
    page: number;
    per_page: number;
    total: number;
    total_pages: number;
};

type Props = {
    labs: Lab[];
    pagination: Pagination;
};

export default function Labs() {
    const { labs, pagination } = usePage<Props>().props;
    const [searchQuery, setSearchQuery] = useState('');
    const [isLoading, setIsLoading] = useState(true);

    useEffect(() => {
        const timer = setTimeout(() => setIsLoading(false), 800);
        return () => clearTimeout(timer);
    }, []);

    const filteredLabs = useMemo(() => {
        if (!searchQuery.trim()) return labs;
        return labs.filter(lab =>
            lab.lab_title.toLowerCase().includes(searchQuery.toLowerCase()) ||
            lab.lab_description.toLowerCase().includes(searchQuery.toLowerCase())
        );
    }, [labs, searchQuery]);

    const getStatusBadge = (state: string) => {
        switch (state) {
            case 'RUNNING':
                return (
                    <Badge className="bg-[hsl(var(--chart-3))] hover:bg-[hsl(var(--chart-3))/80] text-white border-0">
                        <CheckCircle className="h-3 w-3 mr-1" />
                        Running
                    </Badge>
                );
            case 'STOPPED':
                return (
                    <Badge variant="destructive" className="border-0">
                        <AlertTriangle className="h-3 w-3 mr-1" />
                        Stopped
                    </Badge>
                );
            case 'STARTING':
            case 'STOPPING':
                return (
                    <Badge variant="secondary" className="bg-[hsl(var(--chart-2))] hover:bg-[hsl(var(--chart-2))/80] text-white border-0">
                        <Clock className="h-3 w-3 mr-1" />
                        {state.charAt(0).toUpperCase() + state.slice(1).toLowerCase()}
                    </Badge>
                );
            default:
                return (
                    <Badge variant="outline">
                        <Info className="h-3 w-3 mr-1" />
                        {state}
                    </Badge>
                );
        }
    };

    const containerVariants = {
        hidden: { opacity: 0 },
        visible: {
            opacity: 1,
            transition: {
                staggerChildren: 0.08
            }
        }
    };

    const cardVariants: Variants = {
        hidden: { opacity: 0, y: 20 },
        visible: {
            opacity: 1,
            y: 0,
            transition: {
                type: "spring",
                stiffness: 100,
                damping: 12
            }
        },
        hover: {
            y: -4,
            transition: {
                duration: 0.2,
                type: "tween"
            }
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Labs" />
            <motion.div
                className="flex h-full flex-1 flex-col gap-8 overflow-y-auto p-6"
                initial="hidden"
                animate="visible"
                variants={containerVariants}
            >
                {/* Header Section */}
                <motion.div
                    variants={cardVariants}
                    className="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6"
                >
                    <div className="space-y-2">
                        <motion.h1
                            className="text-4xl font-bold tracking-tight bg-gradient-to-r from-foreground to-foreground/80 bg-clip-text text-transparent"
                            initial={{ opacity: 0, x: -20 }}
                            animate={{ opacity: 1, x: 0 }}
                            transition={{ duration: 0.6, delay: 0.2 }}
                        >
                            Labs
                        </motion.h1>
                        <motion.p
                            className="text-muted-foreground text-lg max-w-md"
                            initial={{ opacity: 0 }}
                            animate={{ opacity: 1 }}
                            transition={{ duration: 0.6, delay: 0.4 }}
                        >
                            Discover and reserve Cisco Modeling Labs for your network experiments
                        </motion.p>
                    </div>

                    {/* Search Bar */}
                    <motion.div
                        className="relative max-w-sm w-full"
                        initial={{ opacity: 0, scale: 0.95 }}
                        animate={{ opacity: 1, scale: 1 }}
                        transition={{ duration: 0.5, delay: 0.3 }}
                    >
                        <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-muted-foreground h-4 w-4" />
                        <Input
                            type="search"
                            placeholder="Search labs..."
                            value={searchQuery}
                            onChange={(e) => setSearchQuery(e.target.value)}
                            className="pl-10 h-11 border-input bg-background/50 backdrop-blur-sm focus:bg-background transition-colors"
                        />
                    </motion.div>
                </motion.div>

                {/* Stats Overview */}
                <motion.div variants={cardVariants} className="flex gap-6 text-center">
                    <motion.div
                        className="flex-1 p-6 rounded-xl bg-gradient-to-br from-[hsl(var(--chart-1)/5)] to-[hsl(var(--chart-1)/10)] border border-[hsl(var(--chart-1)/20)]"
                        whileHover={{ scale: 1.02 }}
                        transition={{ duration: 0.2 }}
                    >
                        <div className="text-3xl font-bold text-[hsl(var(--chart-1))]">{labs.length}</div>
                        <div className="text-sm text-muted-foreground mt-1">Total Labs</div>
                    </motion.div>
                    <motion.div
                        className="flex-1 p-6 rounded-xl bg-gradient-to-br from-[hsl(var(--chart-3)/5)] to-[hsl(var(--chart-3)/10)] border border-[hsl(var(--chart-3)/20)]"
                        whileHover={{ scale: 1.02 }}
                        transition={{ duration: 0.2 }}
                    >
                        <div className="text-3xl font-bold text-[hsl(var(--chart-3))]">{labs.filter(l => l.state === 'RUNNING').length}</div>
                        <div className="text-sm text-muted-foreground mt-1">Running</div>
                    </motion.div>
                    <motion.div
                        className="flex-1 p-6 rounded-xl bg-gradient-to-br from-[hsl(var(--chart-2)/5)] to-[hsl(var(--chart-2)/10)] border border-[hsl(var(--chart-2)/20)]"
                        whileHover={{ scale: 1.02 }}
                        transition={{ duration: 0.2 }}
                    >
                        <div className="text-3xl font-bold text-[hsl(var(--chart-2))]">{pagination.total}</div>
                        <div className="text-sm text-muted-foreground mt-1">Available</div>
                    </motion.div>
                </motion.div>

                {/* Labs Grid */}
                {isLoading ? (
                    <div className="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
                        {[...Array(6)].map((_, i) => (
                            <Card key={i} className="overflow-hidden">
                                <CardHeader className="pb-4">
                                    <Skeleton className="h-5 w-32 mb-2" />
                                    <Skeleton className="h-4 w-20" />
                                </CardHeader>
                                <CardContent>
                                    <div className="grid grid-cols-2 gap-4 mb-6">
                                        <Skeleton className="h-16 w-full" />
                                        <Skeleton className="h-16 w-full" />
                                    </div>
                                    <div className="flex gap-2">
                                        <Skeleton className="h-9 w-24" />
                                        <Skeleton className="h-9 w-16" />
                                    </div>
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                ) : filteredLabs.length === 0 ? (
                    <motion.div
                        variants={cardVariants}
                        className="flex flex-col items-center justify-center py-16 text-center"
                    >
                        <motion.div
                            initial={{ scale: 0 }}
                            animate={{ scale: 1 }}
                            transition={{ duration: 0.5, delay: 0.2 }}
                            className="w-24 h-24 rounded-full bg-muted flex items-center justify-center mb-6"
                        >
                            <Search className="h-12 w-12 text-muted-foreground" />
                        </motion.div>
                        <h3 className="text-xl font-semibold mb-2">No labs found</h3>
                        <p className="text-muted-foreground max-w-md">
                            {searchQuery ? `No labs match "${searchQuery}"` : 'No labs are currently available.'}
                        </p>
                        {searchQuery && (
                            <Button
                                variant="outline"
                                className="mt-4"
                                onClick={() => setSearchQuery('')}
                            >
                                Clear search
                            </Button>
                        )}
                    </motion.div>
                ) : (
                    <motion.div
                        className="grid gap-6 md:grid-cols-2 xl:grid-cols-3"
                        variants={containerVariants}
                    >
                        {filteredLabs.map((lab, index) => (
                            <motion.div key={lab.id} variants={cardVariants} whileHover="hover">
                                <Card className="group relative overflow-hidden border-0 bg-gradient-to-br from-card via-card/95 to-card/80 hover:shadow-2xl hover:shadow-primary/5 transition-all duration-500">
                                    {/* Status gradient stripe */}
                                    <motion.div
                                        className={`absolute top-0 left-0 right-0 h-1 ${
                                            lab.state === 'RUNNING'
                                                ? 'bg-gradient-to-r from-[hsl(var(--chart-3))] to-[hsl(var(--chart-3))/70]'
                                                : lab.state === 'STOPPED'
                                                ? 'bg-gradient-to-r from-destructive to-destructive/70'
                                                : 'bg-gradient-to-r from-[hsl(var(--chart-2))] to-[hsl(var(--chart-2))/70]'
                                        }`}
                                        initial={false}
                                        animate={{
                                            scaleX: [0, 1],
                                            transformOrigin: "left"
                                        }}
                                        transition={{ duration: 0.6, delay: index * 0.05 }}
                                    />

                                    <CardHeader className="pb-4">
                                        <div className="flex items-start justify-between">
                                            <div className="flex-1 min-w-0">
                                                <CardTitle className="text-xl font-bold line-clamp-2 mb-3 group-hover:text-primary transition-colors duration-300">
                                                    {lab.lab_title}
                                                </CardTitle>
                                                {getStatusBadge(lab.state)}
                                            </div>

                                            {/* Status indicator */}
                                            <motion.div
                                                className={`p-3 rounded-xl ${
                                                    lab.state === 'RUNNING'
                                                        ? 'bg-[hsl(var(--chart-3)/10)] border border-[hsl(var(--chart-3)/20)]'
                                                        : lab.state === 'STOPPED'
                                                        ? 'bg-destructive/10 border border-destructive/20'
                                                        : 'bg-[hsl(var(--chart-2)/10)] border border-[hsl(var(--chart-2)/20)]'
                                                }`}
                                                whileHover={{ scale: 1.05 }}
                                                transition={{ duration: 0.2 }}
                                            >
                                                {lab.state === 'RUNNING' ? (
                                                    <CheckCircle className="h-6 w-6 text-[hsl(var(--chart-3))]" />
                                                ) : lab.state === 'STOPPED' ? (
                                                    <AlertCircle className="h-6 w-6 text-destructive" />
                                                ) : (
                                                    <Clock className="h-6 w-6 text-[hsl(var(--chart-2))]" />
                                                )}
                                            </motion.div>
                                        </div>
                                    </CardHeader>

                                    <CardContent className="space-y-6">
                                        {/* Description */}
                                        {lab.lab_description && (
                                            <p className="text-muted-foreground line-clamp-2 text-sm leading-relaxed">
                                                {lab.lab_description}
                                            </p>
                                        )}

                                        {/* Stats Grid */}
                                        <div className="grid grid-cols-2 gap-4">
                                            <motion.div
                                                className="flex items-center gap-3 p-4 rounded-xl bg-muted/30 border border-border/50"
                                                whileHover={{ scale: 1.02 }}
                                            >
                                                <Network className="h-5 w-5 text-[hsl(var(--chart-4))]" />
                                                <div>
                                                    <div className="text-lg font-semibold">{lab.node_count}</div>
                                                    <div className="text-xs text-muted-foreground">devices</div>
                                                </div>
                                            </motion.div>

                                            <motion.div
                                                className="flex items-center gap-3 p-4 rounded-xl bg-muted/30 border border-border/50"
                                                whileHover={{ scale: 1.02 }}
                                            >
                                                <Clock className="h-5 w-5 text-[hsl(var(--chart-5))]" />
                                                <div>
                                                    <div className="text-sm font-medium">
                                                        {new Date(lab.modified).toLocaleDateString('en-US', {
                                                            month: 'short',
                                                            day: 'numeric'
                                                        })}
                                                    </div>
                                                    <div className="text-xs text-muted-foreground">modified</div>
                                                </div>
                                            </motion.div>
                                        </div>

                                        {/* Actions */}
                                        <div className="flex flex-wrap items-center gap-2 pt-2 border-t border-border/50">
                                            <LabReservationDialog
                                                lab={{
                                                    id: lab.id,
                                                    title: lab.lab_title,
                                                    description: lab.lab_description,
                                                    state: lab.state
                                                }}
                                            >
                                                <motion.div whileHover={{ scale: 1.02 }} whileTap={{ scale: 0.98 }}>
                                                    <Button
                                                        size="sm"
                                                        className={`h-10 px-6 shadow-lg ${
                                                            lab.state === 'RUNNING'
                                                                ? 'bg-gradient-to-r from-[hsl(var(--chart-3))] to-[hsl(var(--chart-3))/90] hover:from-[hsl(var(--chart-3))/90] hover:to-[hsl(var(--chart-3))] shadow-[hsl(var(--chart-3))/25]'
                                                                : 'bg-muted hover:bg-muted/80'
                                                        } text-white transition-all duration-300`}
                                                    >
                                                        <Calendar className="h-4 w-4 mr-2" />
                                                        {lab.state === 'RUNNING' ? 'Book Now' : 'Book Lab'}
                                                    </Button>
                                                </motion.div>
                                            </LabReservationDialog>

                                            <TooltipProvider>
                                                <Tooltip>
                                                    <TooltipTrigger asChild>
                                                        <Dialog>
                                                            <DialogTrigger asChild>
                                                                <motion.div whileHover={{ scale: 1.02 }} whileTap={{ scale: 0.98 }}>
                                                                    <Button variant="outline" size="sm" className="h-10 px-4 hover:bg-muted/50 transition-colors">
                                                                        <Eye className="h-4 w-4 mr-2" />
                                                                        <span className="hidden sm:inline">Schema</span>
                                                                    </Button>
                                                                </motion.div>
                                                            </DialogTrigger>
                                                            <DialogContent className="max-w-6xl max-h-[90vh] overflow-hidden">
                                                                <CardHeader className="px-0">
                                                                    <div className="flex items-center gap-3">
                                                                        <div className="w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center">
                                                                            <Eye className="h-6 w-6 text-primary" />
                                                                        </div>
                                                                        <div>
                                                                            <CardTitle className="text-xl">{lab.lab_title}</CardTitle>
                                                                            <CardDescription>Lab topology and annotations preview</CardDescription>
                                                                        </div>
                                                                    </div>
                                                                </CardHeader>

                                                                <div className="relative bg-card rounded-xl border shadow-lg min-h-[500px] overflow-hidden">
                                                                    <div className="absolute top-4 left-4 z-10 bg-background/95 backdrop-blur-sm rounded-lg shadow-sm px-4 py-3 border">
                                                                        <div className="flex items-center gap-4 text-sm">
                                                                            <div className="flex items-center gap-2">
                                                                                <div className="w-2 h-2 rounded-full bg-green-500"></div>
                                                                                <span>Live Preview</span>
                                                                            </div>
                                                                            <div className="flex items-center gap-2 text-muted-foreground">
                                                                                <Network className="h-4 w-4" />
                                                                                <span>{lab.node_count} nodes</span>
                                                                            </div>
                                                                        </div>
                                                                    </div>

                                                                    <div className="w-full h-[500px] rounded-xl overflow-hidden">
                                                                        <AnnotationLab labId={lab.id} />
                                                                    </div>
                                                                </div>

                                                                <div className="flex items-center justify-between text-sm text-muted-foreground pt-4">
                                                                    <div className="space-y-1">
                                                                        <p>• Interactive annotations with drag-and-drop repositioning</p>
                                                                        <p>• Real-time topology visualization from CML</p>
                                                                    </div>
                                                                    <Badge variant="secondary" className="text-xs">
                                                                        Live Data
                                                                    </Badge>
                                                                </div>
                                                            </DialogContent>
                                                        </Dialog>
                                                    </TooltipTrigger>
                                                    <TooltipContent side="top" className="max-w-xs">
                                                        <div className="space-y-2">
                                                            <p className="font-medium">Lab Architecture Preview</p>
                                                            <p className="text-xs leading-relaxed">
                                                                View the complete network topology and annotations for this lab before booking.
                                                            </p>
                                                        </div>
                                                    </TooltipContent>
                                                </Tooltip>
                                            </TooltipProvider>
                                        </div>
                                    </CardContent>
                                </Card>
                            </motion.div>
                        ))}
                    </motion.div>
                )}

                {/* Pagination */}
                {pagination.total_pages > 1 && (
                    <motion.div
                        variants={cardVariants}
                        className="flex justify-center pt-8"
                    >
                        <PaginationApp
                            page={pagination.page}
                            per_page={pagination.per_page}
                            total={pagination.total}
                            total_pages={pagination.total_pages}
                        />
                    </motion.div>
                )}
            </motion.div>
        </AppLayout>
    );
}
