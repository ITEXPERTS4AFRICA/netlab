import AppLayout from '@/layouts/app-layout'
import { type BreadcrumbItem } from '@/types'
import { Head, usePage } from '@inertiajs/react'
import { Card, CardTitle, CardContent, CardHeader, CardDescription } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Skeleton } from '@/components/ui/skeleton'
import { PaginationApp } from '@/components/app-pagination'
import LabReservationDialog from '@/components/lab-reservation-dialog'
import { Button } from '@/components/ui/button'
import { Dialog, DialogContent, DialogDescription, DialogTrigger } from '@/components/ui/dialog'
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip'
import AnnotationLab from '@/components/AnnotationLab'
import { Input } from '@/components/ui/input'

import {
    AlertCircle,
    CheckCircle,
    Calendar,
    Eye,
    Network,
    Clock,
    Info,
    AlertTriangle,
    Search,
    Activity,
    X,
    Star,
    DollarSign,
    TrendingUp
} from 'lucide-react'
import { motion, type Variants } from 'framer-motion'
import { useState, useMemo, useEffect } from 'react'

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Labs',
        href: '/labs'
    }
]

type Lab = {
    id: string
    db_id?: number
    state: string
    lab_title: string
    node_count: string | number
    link_count?: number
    interface_count?: number
    lab_description: string
    short_description?: string
    created: string
    modified: string
    // Métadonnées enrichies
    price_cents?: number
    currency?: string
    difficulty_level?: string
    estimated_duration_minutes?: number
    is_featured?: boolean
    rating?: number
    rating_count?: number
    view_count?: number
    reservation_count?: number
    active_reservations_count?: number
}

type Pagination = {
    page: number
    per_page: number
    total: number
    total_pages: number
}

type Props = {
    labs: Lab[]
    pagination: Pagination
    error?: string
}

export default function Labs() {
    const { labs, pagination, error } = usePage<Props>().props
    const [searchQuery, setSearchQuery] = useState('')
    const [isLoading, setIsLoading] = useState(true)
    const [debugInfo, setDebugInfo] = useState<{
        labsType: string
        labsIsArray: boolean
        labsLength: number | string
        pagination: Pagination | null
        error: string | undefined
        timestamp: string
    }>({
        labsType: 'unknown',
        labsIsArray: false,
        labsLength: 0,
        pagination: null,
        error: undefined,
        timestamp: new Date().toISOString()
    })

    // Normalise labs toujours en Array
    const safeLabs = useMemo<Lab[]>(() => {
        if (Array.isArray(labs)) return labs
        if (labs && typeof labs === 'object') {
            // Permet objets indexés
            return Object.values(labs).filter(
                lab => lab && typeof lab === 'object' && 'id' in lab && 'lab_title' in lab
            ) as Lab[]
        }
        return []
    }, [labs])

    useEffect(() => {
        setDebugInfo({
            labsType: typeof labs,
            labsIsArray: Array.isArray(labs),
            labsLength: Array.isArray(labs) ? labs.length : 'N/A',
            pagination,
            error,
            timestamp: new Date().toISOString()
        })
        const timer = setTimeout(() => setIsLoading(false), 300)
        return () => clearTimeout(timer)
    }, [labs, pagination, error])

    const filteredLabs = useMemo(() => {
        let filtered = safeLabs

        if (searchQuery.trim()) {
            const query = searchQuery.toLowerCase()
            filtered = safeLabs.filter(lab =>
                lab.lab_title?.toLowerCase().includes(query) ||
                lab.lab_description?.toLowerCase().includes(query)
            )
        }
        if (searchQuery === 'running' || searchQuery === 'RUNNING') {
            filtered = filtered.filter(lab => lab.state === 'DEFINED_ON_CORE')
        } else if (searchQuery === 'stopped') {
            filtered = filtered.filter(lab => lab.state === 'STOPPED')
        }

        return filtered
    }, [safeLabs, searchQuery])

    const getStatusBadge = (state: string) => {
        switch (state) {
            case 'DEFINED_ON_CORE':
                return (
                    <Badge className="bg-[hsl(var(--chart-3))] hover:bg-[hsl(var(--chart-3))/80] text-white border-0">
                        <CheckCircle className="h-3 w-3 mr-1" />
                        RUNNING
                    </Badge>
                )
            case 'STOPPED':
                return (
                    <Badge variant="destructive" className="border-0">
                        <AlertTriangle className="h-3 w-3 mr-1" />
                        Stopped
                    </Badge>
                )
            case 'STOPPING':
                return (
                    <Badge variant="secondary" className="bg-[hsl(var(--chart-2))] hover:bg-[hsl(var(--chart-2))/80] text-white border-0">
                        <Clock className="h-3 w-3 mr-1" />
                        {state.charAt(0).toUpperCase() + state.slice(1).toLowerCase()}
                    </Badge>
                )
            default:
                return (
                    <Badge variant="outline">
                        <Info className="h-3 w-3 mr-1" />
                        {state}
                    </Badge>
                )
        }
    }

    const containerVariants: Variants = {
        hidden: { opacity: 0 },
        visible: {
            opacity: 1,
            transition: {
                staggerChildren: 0.08
            }
        }
    }

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
    }

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
                    className="relative"
                >
                    <div className="absolute inset-0 bg-gradient-to-r from-primary/5 via-transparent to-accent/5 rounded-2xl blur-3xl" />

                    <div className="relative flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6 p-8 rounded-2xl bg-card/80 backdrop-blur-sm border border-border/50 shadow-lg">
                        <div className="space-y-4">
                            <div className="flex items-center gap-3">
                                <div className="w-12 h-12 rounded-xl bg-gradient-to-br from-primary/20 to-primary/5 flex items-center justify-center border border-primary/20">
                                    <Network className="h-6 w-6 text-primary" />
                                </div>
                                <div>
                                    <motion.h1
                                        className="text-4xl font-bold tracking-tight bg-gradient-to-r from-foreground via-foreground to-foreground/70 bg-clip-text text-transparent"
                                        initial={{ opacity: 0, x: -20 }}
                                        animate={{ opacity: 1, x: 0 }}
                                        transition={{ duration: 0.6, delay: 0.2 }}
                                    >
                                        Laboratoires Réseau
                                    </motion.h1>
                                    <div className="flex items-center gap-2 mt-1">
                                        <div className="w-2 h-2 rounded-full bg-green-500 animate-pulse" />
                                        <span className="text-sm text-muted-foreground">Intégration CML Live</span>
                                    </div>
                                </div>
                            </div>
                            <motion.p
                                className="text-muted-foreground text-lg max-w-2xl leading-relaxed"
                                initial={{ opacity: 0 }}
                                animate={{ opacity: 1 }}
                                transition={{ duration: 0.6, delay: 0.4 }}
                            >
                                Découvrez et réservez des Cisco Modeling Labs pour vos expériences réseau.
                                Accédez aux topologies en temps réel, aux annotations interactives et aux environnements d'apprentissage pratiques.
                            </motion.p>
                            {/* Quick stats in header */}
                            <motion.div
                                className="flex items-center gap-6 pt-2"
                                initial={{ opacity: 0, y: 10 }}
                                animate={{ opacity: 1, y: 0 }}
                                transition={{ duration: 0.5, delay: 0.6 }}
                            >
                                <div className="flex items-center gap-2 px-3 py-1.5 rounded-full bg-primary/10 border border-primary/20">
                                    <Activity className="h-4 w-4 text-primary" />
                                    <span className="text-sm font-medium text-primary">{safeLabs.length} Labs Actifs</span>
                                </div>
                                <div className="flex items-center gap-2 px-3 py-1.5 rounded-full bg-green-500/10 border border-green-500/20">
                                    <CheckCircle className="h-4 w-4 text-green-600" />
                                    <span className="text-sm font-medium text-green-600">
                                        {safeLabs.filter(l => l.state === 'DEFINED_ON_CORE').length} Running
                                    </span>
                                </div>
                            </motion.div>
                        </div>

                        {/* Enhanced Search Bar */}
                        <motion.div
                            className="flex flex-col gap-4"
                            initial={{ opacity: 0, scale: 0.95 }}
                            animate={{ opacity: 1, scale: 1 }}
                            transition={{ duration: 0.5, delay: 0.3 }}
                        >
                            <div className="relative">
                                <Search className="absolute left-4 top-1/2 transform -translate-y-1/2 text-muted-foreground h-5 w-5" />
                                <Input
                                    type="search"
                                    placeholder="Rechercher des labs par titre, description..."
                                    value={searchQuery}
                                    onChange={e => setSearchQuery(e.target.value)}
                                    className="pl-12 pr-4 h-12 w-80 text-base border-2 border-input bg-background/80 backdrop-blur-sm focus:bg-background focus:border-primary/50 transition-all duration-200 rounded-xl shadow-sm"
                                />
                                {searchQuery && (
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        className="absolute right-2 top-1/2 transform -translate-y-1/2 h-8 w-8 p-0 hover:bg-muted/80"
                                        onClick={() => setSearchQuery('')}
                                    >
                                        <X className="h-4 w-4" />
                                    </Button>
                                )}
                            </div>
                            {/* Quick filters */}
                            <div className="flex items-center gap-2 flex-wrap">
                                <span className="text-sm text-muted-foreground">Filtrer :</span>
                                <div className="flex gap-2">
                                    <Button
                                        variant={searchQuery === '' ? 'default' : 'outline'}
                                        size="sm"
                                        className="h-8 px-3 text-xs"
                                        onClick={() => setSearchQuery('')}
                                    >
                                        Tous les Labs
                                    </Button>
                                    <Button
                                        variant={['running', 'RUNNING'].includes(searchQuery) ? 'default' : 'outline'}
                                        size="sm"
                                        className="h-8 px-3 text-xs"
                                        onClick={() => setSearchQuery('RUNNING')}
                                    >
                                        En cours
                                    </Button>
                                    <Button
                                        variant={searchQuery === 'stopped' ? 'default' : 'outline'}
                                        size="sm"
                                        className="h-8 px-3 text-xs"
                                        onClick={() => setSearchQuery('stopped')}
                                    >
                                        Arrêté
                                    </Button>
                                </div>
                            </div>
                        </motion.div>
                    </div>
                </motion.div>

                {/* Error Display */}
                {error && (
                    <motion.div
                        variants={cardVariants}
                        className="p-6 rounded-xl bg-destructive/10 border border-destructive/20"
                    >
                        <div className="flex items-center gap-3">
                            <AlertCircle className="h-6 w-6 text-destructive" />
                            <div>
                                <h3 className="font-semibold text-destructive">Erreur de Connexion</h3>
                                <p className="text-sm text-muted-foreground mt-1">{error}</p>
                            </div>
                        </div>
                    </motion.div>
                )}

                {/* Debug Information Panel */}
                <motion.div
                    variants={cardVariants}
                    className="p-4 rounded-xl bg-muted/30 border border-border/50"
                >
                    <details className="group">
                        <summary className="flex items-center gap-2 cursor-pointer text-sm font-medium text-muted-foreground hover:text-foreground transition-colors">
                            <Info className="h-4 w-4" />
                            Informations de Débogage (Cliquer pour développer)
                        </summary>
                        <div className="mt-4 space-y-2 text-xs font-mono">
                            <div>
                                <strong>Labs Type:</strong> {debugInfo.labsType}
                            </div>
                            <div>
                                <strong>Is Array:</strong> {debugInfo.labsIsArray ? 'Yes' : 'No'}
                            </div>
                            <div>
                                <strong>Labs Length:</strong> {String(debugInfo.labsLength)}
                            </div>
                            <div>
                                <strong>Pagination:</strong>{' '}
                                {debugInfo.pagination
                                    ? JSON.stringify(debugInfo.pagination, null, 2)
                                    : 'null'}
                            </div>
                            <div>
                                <strong>Error:</strong> {debugInfo.error || 'None'}
                            </div>
                            <div>
                                <strong>Timestamp:</strong> {debugInfo.timestamp}
                            </div>
                            <div>
                                <strong>Sample Lab Data:</strong>
                            </div>
                            <pre className="bg-muted p-2 rounded text-xs overflow-auto max-h-32">
                                {safeLabs.length > 0
                                    ? JSON.stringify(safeLabs[0], null, 2)
                                    : 'No lab data available'}
                            </pre>
                        </div>
                    </details>
                </motion.div>

                {/* Stats Overview */}
                <motion.div variants={cardVariants} className="flex gap-6 text-center">
                    <motion.div
                        className="flex-1 p-6 rounded-xl bg-gradient-to-br from-[hsl(var(--chart-1)/5)] to-[hsl(var(--chart-1)/10)] border border-[hsl(var(--chart-1)/20)]"
                        whileHover={{ scale: 1.02 }}
                        transition={{ duration: 0.2 }}
                    >
                        <div className="text-3xl font-bold text-[hsl(var(--chart-1))]">
                            {safeLabs.length}
                        </div>
                        <div className="text-sm text-muted-foreground mt-1">
                            Total des Labs
                        </div>
                    </motion.div>
                    <motion.div
                        className="flex-1 p-6 rounded-xl bg-gradient-to-br from-[hsl(var(--chart-3)/5)] to-[hsl(var(--chart-3)/10)] border border-[hsl(var(--chart-3)/20)]"
                        whileHover={{ scale: 1.02 }}
                        transition={{ duration: 0.2 }}
                    >
                        <div className="text-3xl font-bold text-[hsl(var(--chart-3))]">
                            {safeLabs.filter(l => l.state === 'DEFINED_ON_CORE').length}
                        </div>
                        <div className="text-sm text-muted-foreground mt-1">
                            DEFINED_ON_CORE
                        </div>
                    </motion.div>
                    <motion.div
                        className="flex-1 p-6 rounded-xl bg-gradient-to-br from-[hsl(var(--chart-2)/5)] to-[hsl(var(--chart-2)/10)] border border-[hsl(var(--chart-2)/20)]"
                        whileHover={{ scale: 1.02 }}
                        transition={{ duration: 0.2 }}
                    >
                        <div className="text-3xl font-bold text-[hsl(var(--chart-2))]">
                            {pagination.total}
                        </div>
                        <div className="text-sm text-muted-foreground mt-1">
                            Disponible
                        </div>
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
                        <h3 className="text-xl font-semibold mb-2">Aucun lab trouvé</h3>
                        <p className="text-muted-foreground max-w-md">
                            {searchQuery ? `No labs match "${searchQuery}"` : 'No labs are currently available.'}
                        </p>
                        {searchQuery && (
                            <Button
                                variant="outline"
                                className="mt-4"
                                onClick={() => setSearchQuery('')}
                            >
                                Effacer la recherche
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
                                <Card className={`group relative overflow-hidden border-0 bg-gradient-to-br from-card via-card/95 to-card/80 hover:shadow-2xl hover:shadow-primary/5 transition-all duration-500 ${lab.is_featured ? 'ring-2 ring-yellow-400/50 shadow-lg shadow-yellow-400/10' : ''}`}>
                                    {/* Badge "En avant" */}
                                    {lab.is_featured && (
                                        <div className="absolute top-3 right-3 z-10">
                                            <Badge className="bg-yellow-500 text-white border-0 shadow-lg">
                                                <Star className="h-3 w-3 mr-1 fill-white" />
                                                En avant
                                            </Badge>
                                        </div>
                                    )}

                                    {/* Status gradient stripe */}
                                    <motion.div
                                        className={`absolute top-0 left-0 right-0 h-1 ${
                                            lab.state === 'DEFINED_ON_CORE'
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
                                            <div className="flex-1 min-w-0 pr-2">
                                                <div className="flex items-center gap-2 mb-2">
                                                    <CardTitle className="text-xl font-bold line-clamp-2 group-hover:text-primary transition-colors duration-300">
                                                    {lab.lab_title}
                                                </CardTitle>
                                                    {lab.is_featured && (
                                                        <Star className="h-5 w-5 text-yellow-400 fill-yellow-400 flex-shrink-0" />
                                                    )}
                                                </div>
                                                <div className="flex items-center gap-2 flex-wrap">
                                                {getStatusBadge(lab.state)}
                                                    {lab.difficulty_level && (
                                                        <Badge variant="outline" className="text-xs">
                                                            {lab.difficulty_level}
                                                        </Badge>
                                                    )}
                                                    {lab.rating && lab.rating_count > 0 && (
                                                        <Badge variant="outline" className="text-xs">
                                                            <Star className="h-3 w-3 mr-1 fill-yellow-400 text-yellow-400" />
                                                            {lab.rating.toFixed(1)} ({lab.rating_count})
                                                        </Badge>
                                                    )}
                                                </div>
                                            </div>

                                            {/* Status indicator */}
                                            <motion.div
                                                className={`p-3 rounded-xl ${
                                                    lab.state === 'DEFINED_ON_CORE'
                                                        ? 'bg-[hsl(var(--chart-3)/10)] border border-[hsl(var(--chart-3)/20)]'
                                                        : lab.state === 'STOPPED'
                                                        ? 'bg-destructive/10 border border-destructive/20'
                                                        : 'bg-[hsl(var(--chart-2)/10)] border border-[hsl(var(--chart-2)/20)]'
                                                }`}
                                                whileHover={{ scale: 1.05 }}
                                                transition={{ duration: 0.2 }}
                                            >
                                                {lab.state === 'DEFINED_ON_CORE' ? (
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
                                        {(lab.short_description || lab.lab_description) && (
                                            <p className="text-muted-foreground line-clamp-2 text-sm leading-relaxed">
                                                {lab.short_description || lab.lab_description}
                                            </p>
                                        )}

                                        {/* Prix, Temps et Difficulté */}
                                        <div className="flex flex-wrap items-center gap-3 text-sm">
                                            {/* Prix */}
                                            {lab.price_cents && lab.price_cents > 0 ? (
                                                <div className="flex items-center gap-2 px-3 py-1.5 rounded-full bg-primary/10 border border-primary/20">
                                                    <DollarSign className="h-4 w-4 text-primary" />
                                                    <span className="font-semibold text-primary">
                                                        {((lab.price_cents || 0) / 100).toLocaleString('fr-FR')} {lab.currency || 'XOF'}
                                                    </span>
                                                </div>
                                            ) : (
                                                <Badge variant="secondary" className="text-xs">
                                                    Gratuit
                                                </Badge>
                                            )}
                                            {/* Durée */}
                                            {lab.estimated_duration_minutes && (
                                                <div className="flex items-center gap-2 px-3 py-1.5 rounded-full bg-blue-500/10 border border-blue-500/20">
                                                    <Clock className="h-4 w-4 text-blue-600" />
                                                    <span className="font-medium text-blue-600">{lab.estimated_duration_minutes} min</span>
                                                </div>
                                            )}
                                            {/* Difficulté */}
                                            {lab.difficulty_level && (
                                                <Badge variant="outline" className="text-xs px-3 py-1.5">
                                                    {lab.difficulty_level}
                                                </Badge>
                                            )}
                                            {/* Vues */}
                                            {lab.view_count > 0 && (
                                                <div className="flex items-center gap-2 text-muted-foreground">
                                                    <TrendingUp className="h-4 w-4" />
                                                    <span>{lab.view_count} vues</span>
                                                </div>
                                            )}
                                        </div>

                                        {/* Stats Grid - Nodes, Links, Interfaces */}
                                        <div className="grid grid-cols-3 gap-3">
                                            <motion.div
                                                className="flex flex-col items-center gap-2 p-4 rounded-xl bg-gradient-to-br from-[hsl(var(--chart-4)/10)] to-[hsl(var(--chart-4)/5)] border border-[hsl(var(--chart-4)/20)]"
                                                whileHover={{ scale: 1.05 }}
                                            >
                                                <Network className="h-5 w-5 text-[hsl(var(--chart-4))]" />
                                                <div className="text-center">
                                                    <div className="text-xl font-bold text-[hsl(var(--chart-4))]">{lab.node_count || 0}</div>
                                                    <div className="text-xs text-muted-foreground font-medium">Nœuds</div>
                                                </div>
                                            </motion.div>

                                            <motion.div
                                                className="flex flex-col items-center gap-2 p-4 rounded-xl bg-gradient-to-br from-[hsl(var(--chart-5)/10)] to-[hsl(var(--chart-5)/5)] border border-[hsl(var(--chart-5)/20)]"
                                                whileHover={{ scale: 1.05 }}
                                            >
                                                <div className="h-5 w-5 flex items-center justify-center">
                                                    <svg className="h-5 w-5 text-[hsl(var(--chart-5))]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                                                    </svg>
                                                </div>
                                                <div className="text-center">
                                                    <div className="text-xl font-bold text-[hsl(var(--chart-5))]">{lab.link_count || 0}</div>
                                                    <div className="text-xs text-muted-foreground font-medium">Liens</div>
                                                </div>
                                            </motion.div>

                                            <motion.div
                                                className="flex flex-col items-center gap-2 p-4 rounded-xl bg-gradient-to-br from-[hsl(var(--chart-6)/10)] to-[hsl(var(--chart-6)/5)] border border-[hsl(var(--chart-6)/20)]"
                                                whileHover={{ scale: 1.05 }}
                                            >
                                                <div className="h-5 w-5 flex items-center justify-center">
                                                    <svg className="h-5 w-5 text-[hsl(var(--chart-6))]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                                                    </svg>
                                                </div>
                                                <div className="text-center">
                                                    <div className="text-xl font-bold text-[hsl(var(--chart-6))]">{lab.interface_count !== undefined && lab.interface_count !== null ? lab.interface_count : 'N/A'}</div>
                                                    <div className="text-xs text-muted-foreground font-medium">Interfaces</div>
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
                                                            lab.state === 'DEFINED_ON_CORE'
                                                                ? 'bg-gradient-to-r from-[hsl(var(--chart-3))] to-[hsl(var(--chart-3))/90] hover:from-[hsl(var(--chart-3))/90] hover:to-[hsl(var(--chart-3))] shadow-[hsl(var(--chart-3))/25]'
                                                                : 'bg-muted hover:bg-muted/80'
                                                        } text-white transition-all duration-300`}
                                                    >
                                                        <Calendar className="h-4 w-4 mr-2" />
                                                        {lab.state === 'DEFINED_ON_CORE' ? 'Réserver Maintenant' : 'Réserver le Lab'}
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
                                                                            <CardDescription>Aperçu de la topologie et des annotations du lab</CardDescription>
                                                                        </div>
                                                                    </div>
                                                                    <DialogDescription>
                                                                        Consultez la topologie réseau complète, les connexions d'appareils et les annotations interactives pour {lab.lab_title}. Cet aperçu montre l'architecture du lab en temps réel depuis CML.
                                                                    </DialogDescription>
                                                                </CardHeader>

                                                                <div className="relative bg-card rounded-xl border shadow-lg min-h-[500px] overflow-hidden">
                                                                    <div className="absolute top-4 left-4 z-10 bg-background/95 backdrop-blur-sm rounded-lg shadow-sm px-4 py-3 border">
                                                                        <div className="flex items-center gap-4 text-sm">
                                                                            <div className="flex items-center gap-2">
                                                                                <div className="w-2 h-2 rounded-full bg-green-500"></div>
                                                                                <span>Aperçu Live</span>
                                                                            </div>
                                                                            <div className="flex items-center gap-2 text-muted-foreground">
                                                                                <Network className="h-4 w-4" />
                                                                                <span>{lab.node_count} nœuds</span>
                                                                            </div>
                                                                        </div>
                                                                    </div>

                                                                    <div className="w-full h-[500px] rounded-xl overflow-hidden">
                                                                        <AnnotationLab labId={lab.id} />
                                                                    </div>
                                                                </div>

                                                                <div className="flex items-center justify-between text-sm text-muted-foreground pt-4">
                                                                    <div className="space-y-1">
                                                                        <p>• Annotations interactives avec repositionnement par glisser-déposer</p>
                                                                        <p>• Visualisation de topologie en temps réel depuis CML</p>
                                                                    </div>
                                                                    <Badge variant="secondary" className="text-xs">
                                                                        Données Live
                                                                    </Badge>
                                                                </div>
                                                            </DialogContent>
                                                        </Dialog>
                                                    </TooltipTrigger>
                                                    <TooltipContent side="top" className="max-w-xs">
                                                        <div className="space-y-2">
                                                            <p className="font-medium">Aperçu de l'Architecture du Lab</p>
                                                            <p className="text-xs leading-relaxed">
                                                                Consultez la topologie réseau complète et les annotations pour ce lab avant de réserver.
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
