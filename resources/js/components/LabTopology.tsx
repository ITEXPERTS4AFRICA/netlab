import { useEffect, useMemo, useRef, useState } from 'react';
import { Network } from 'lucide-react';
import { Card, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';

type Node = {
    id: string;
    label?: string;
    node_definition?: string;
    state?: string;
    x?: number;
    y?: number;
    [key: string]: unknown;
};

type Link = {
    id: string;
    n1?: string;
    n2?: string;
    i1?: string;
    i2?: string;
    state?: string;
    [key: string]: unknown;
};

type Topology = {
    nodes?: Node[];
    links?: Link[];
    [key: string]: unknown;
};

type Props = {
    readonly nodes: Node[];
    readonly links: Link[];
    readonly topology?: Topology | null;
    readonly className?: string;
};

export default function LabTopology({ nodes, links, topology, className = '' }: Props) {
    const canvasRef = useRef<HTMLCanvasElement>(null);
    const [selectedNode, setSelectedNode] = useState<Node | null>(null);
    const [zoom, setZoom] = useState(1);
    const [pan, setPan] = useState({ x: 0, y: 0 });
    const [isDragging, setIsDragging] = useState(false);
    const [dragStart, setDragStart] = useState({ x: 0, y: 0 });
    const nodePositionsRef = useRef<Map<string, { x: number; y: number }>>(new Map());

    // Combine nodes from props and topology
    const allNodes = useMemo(() => {
        const topologyNodes = topology?.nodes || [];
        const propNodes = nodes || [];
        const combined = topologyNodes.length > 0 ? topologyNodes : propNodes;

        // Debug log
        if (combined.length === 0) {
            console.warn('LabTopology: Aucun node disponible', {
                topologyNodes: topologyNodes.length,
                propNodes: propNodes.length,
                topology,
                nodes,
            });
        } else {
            console.log('LabTopology: Nodes disponibles', {
                total: combined.length,
                sample: combined[0],
            });
        }

        return combined;
    }, [topology, nodes]);

    const allLinks = useMemo(() => {
        const topologyLinks = topology?.links || [];
        const propLinks = links || [];
        const combined = topologyLinks.length > 0 ? topologyLinks : propLinks;

        // Debug log
        if (combined.length === 0) {
            console.warn('LabTopology: Aucun link disponible', {
                topologyLinks: topologyLinks.length,
                propLinks: propLinks.length,
                topology,
                links,
            });
        } else {
            console.log('LabTopology: Links disponibles', {
                total: combined.length,
                sample: combined[0],
            });
        }

        return combined;
    }, [topology, links]);

    useEffect(() => {
        console.log('LabTopology: useEffect déclenché', {
            allNodesCount: allNodes.length,
            allLinksCount: allLinks.length,
            zoom,
            pan,
        });

        const canvas = canvasRef.current;
        if (!canvas) {
            console.error('LabTopology: Canvas ref non disponible - canvasRef.current est null');
            return;
        }

        const ctx = canvas.getContext('2d');
        if (!ctx) {
            console.error('LabTopology: Context 2D non disponible');
            return;
        }

        // Set canvas size
        const rect = canvas.getBoundingClientRect();
        console.log('LabTopology: Canvas rect', {
            width: rect.width,
            height: rect.height,
            left: rect.left,
            top: rect.top,
        });

        // Use actual pixel dimensions, not CSS dimensions
        const dpr = window.devicePixelRatio || 1;
        canvas.width = rect.width * dpr;
        canvas.height = rect.height * dpr;

        // Scale context to account for device pixel ratio
        ctx.scale(dpr, dpr);

        // Now work in CSS pixels
        const cssWidth = rect.width;
        const cssHeight = rect.height;

        console.log('LabTopology: Canvas dimensions définies', {
            canvasWidth: canvas.width,
            canvasHeight: canvas.height,
            cssWidth,
            cssHeight,
            dpr,
            nodesCount: allNodes.length,
            linksCount: allLinks.length,
        });

        if (canvas.width === 0 || canvas.height === 0) {
            console.error('LabTopology: Canvas a des dimensions 0x0 !', {
                rect,
                dpr,
            });
            return;
        }

        // Draw background with grid FIRST - use CSS dimensions
        ctx.fillStyle = '#0f172a'; // Dark background
        ctx.fillRect(0, 0, cssWidth, cssHeight);
        console.log('LabTopology: Fond dessiné', { cssWidth, cssHeight });

        // Draw grid before transformations
        ctx.strokeStyle = '#334155'; // Lighter gray for grid visibility
        ctx.lineWidth = 1;
        const gridSize = 50;

        ctx.save();
        // Draw vertical lines
        for (let x = 0; x < cssWidth; x += gridSize) {
            ctx.beginPath();
            ctx.moveTo(x, 0);
            ctx.lineTo(x, cssHeight);
            ctx.stroke();
        }
        // Draw horizontal lines
        for (let y = 0; y < cssHeight; y += gridSize) {
            ctx.beginPath();
            ctx.moveTo(0, y);
            ctx.lineTo(cssWidth, y);
            ctx.stroke();
        }
        ctx.restore();
        console.log('LabTopology: Grille dessinée', {
            gridSize,
            verticalLines: Math.ceil(cssWidth / gridSize),
            horizontalLines: Math.ceil(cssHeight / gridSize),
        });

        // Test: Draw a simple test circle at center AFTER background to verify canvas is working
        ctx.fillStyle = '#ff0000'; // Red test circle - should be visible on dark background
        ctx.beginPath();
        ctx.arc(cssWidth / 2, cssHeight / 2, 15, 0, 2 * Math.PI);
        ctx.fill();
        console.log('LabTopology: Cercle de test rouge dessiné au centre', {
            centerX: cssWidth / 2,
            centerY: cssHeight / 2,
        });

        // Calculate node positions in world coordinates (0,0 at center)
        const nodePositions = new Map<string, { x: number; y: number }>();

        // Utiliser un rayon adapté - plus petit pour être sûr de voir (en CSS pixels)
        const radius = Math.min(cssWidth, cssHeight) / 5;
        const angleStep = allNodes.length > 0 ? (2 * Math.PI) / allNodes.length : 0;

        console.log('LabTopology: Calcul positions nodes', {
            nodesCount: allNodes.length,
            radius,
            cssWidth,
            cssHeight,
            angleStep,
        });

        for (const [index, node] of allNodes.entries()) {
            if (node.x !== undefined && node.y !== undefined && node.x !== null && node.y !== null) {
                // Si les positions sont fournies, les utiliser directement
                nodePositions.set(node.id, { x: node.x, y: node.y });
            } else {
                // Sinon, disposer en cercle autour du centre (0,0 en world space)
                const angle = index * angleStep;
                const x = radius * Math.cos(angle);
                const y = radius * Math.sin(angle);
                nodePositions.set(node.id, { x, y });
            }
        }

        // Store positions for click detection (en screen coordinates pour le clic)
        nodePositionsRef.current = new Map(
            Array.from(nodePositions.entries()).map(([id, pos]) => [
                id,
                {
                    x: pos.x * zoom + pan.x + cssWidth / 2,
                    y: pos.y * zoom + pan.y + cssHeight / 2,
                }
            ])
        );

        // Apply transformations: center first, then zoom/pan (en CSS pixels)
        ctx.save();
        // Déplacer l'origine au centre du canvas
        ctx.translate(cssWidth / 2, cssHeight / 2);
        // Appliquer le zoom
        ctx.scale(zoom, zoom);
        // Appliquer le pan
        ctx.translate(pan.x / zoom, pan.y / zoom);

        console.log('LabTopology: Transformations appliquées', {
            centerX: cssWidth / 2,
            centerY: cssHeight / 2,
            zoom,
            panX: pan.x,
            panY: pan.y,
            nodePositionsCount: nodePositions.size,
            nodePositions: Array.from(nodePositions.entries()).map(([id, pos]) => ({ id, ...pos })),
        });

        // Draw links with brighter color to ensure visibility
        ctx.strokeStyle = '#94a3b8'; // Lighter gray
        ctx.lineWidth = Math.max(2 / zoom, 1); // Ensure minimum line width
        let linksDrawn = 0;
        for (const link of allLinks) {
            const node1Id = String(link.n1 || link.node1 || link.src || '');
            const node2Id = String(link.n2 || link.node2 || link.dst || '');
            const node1 = nodePositions.get(node1Id);
            const node2 = nodePositions.get(node2Id);
            if (node1 && node2) {
                ctx.beginPath();
                ctx.moveTo(node1.x, node1.y);
                ctx.lineTo(node2.x, node2.y);
                ctx.stroke();
                linksDrawn++;
            }
        }

        if (allLinks.length > 0 && linksDrawn === 0) {
            console.warn('LabTopology: Aucun link dessiné', {
                totalLinks: allLinks.length,
                sampleLink: allLinks[0],
                nodePositions: Array.from(nodePositions.keys()),
            });
        }

        // Draw nodes
        let nodesDrawn = 0;
        for (const node of allNodes) {
            const pos = nodePositions.get(node.id);
            if (!pos) {
                console.warn('LabTopology: Position non trouvée pour node', {
                    nodeId: node.id,
                    availablePositions: Array.from(nodePositions.keys()),
                });
                continue;
            }
            nodesDrawn++;

            const isSelected = selectedNode?.id === node.id;
            const isRunning = node.state === 'BOOTED' || node.state === 'STARTED';

            // Determine fill color
            let fillColor = '#64748b';
            if (isSelected) {
                fillColor = '#3b82f6';
            } else if (isRunning) {
                fillColor = '#10b981';
            }

            // Node circle - ensure minimum size for visibility
            const nodeRadius = Math.max(30 / zoom, 10);
            ctx.beginPath();
            ctx.arc(pos.x, pos.y, nodeRadius, 0, 2 * Math.PI);
            ctx.fillStyle = fillColor;
            ctx.fill();
            ctx.strokeStyle = isSelected ? '#1e40af' : '#ffffff'; // White border for visibility
            ctx.lineWidth = Math.max(3 / zoom, 2); // Thicker border
            ctx.stroke();

            // Node label - ensure readable size
            ctx.fillStyle = '#ffffff';
            ctx.font = `bold ${Math.max(12 / zoom, 10)}px sans-serif`;
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            const label = node.label || node.id.slice(0, 8);

            // Add text shadow for better visibility
            ctx.shadowColor = 'rgba(0, 0, 0, 0.8)';
            ctx.shadowBlur = 4;
            ctx.fillText(label, pos.x, pos.y);
            ctx.shadowBlur = 0; // Reset shadow

            console.log('LabTopology: Node dessiné', {
                nodeId: node.id,
                label,
                position: pos,
                radius: nodeRadius,
                color: fillColor,
            });
        }

        if (allNodes.length > 0 && nodesDrawn === 0) {
            console.error('LabTopology: Aucun node dessiné malgré la présence de nodes', {
                totalNodes: allNodes.length,
                sampleNode: allNodes[0],
            });
        }

        ctx.restore();

        console.log('LabTopology: Dessin terminé', {
            nodesDrawn,
            linksDrawn,
            totalNodes: allNodes.length,
            totalLinks: allLinks.length,
        });
    }, [allNodes, allLinks, selectedNode, zoom, pan]);

    // Redessiner quand la taille du canvas change
    useEffect(() => {
        const canvas = canvasRef.current;
        if (!canvas) return;

        const resizeObserver = new ResizeObserver(() => {
            // Force le redessin en déclenchant un re-render
            const ctx = canvas.getContext('2d');
            if (ctx) {
                const rect = canvas.getBoundingClientRect();
                canvas.width = rect.width;
                canvas.height = rect.height;
                // Le useEffect précédent va redessiner automatiquement
            }
        });

        resizeObserver.observe(canvas);

        return () => {
            resizeObserver.disconnect();
        };
    }, [allNodes, allLinks, selectedNode, zoom, pan]);

    const handleMouseDown = (e: React.MouseEvent<HTMLCanvasElement>) => {
        const canvas = canvasRef.current;
        if (!canvas) return;

        const rect = canvas.getBoundingClientRect();
        const mouseX = e.clientX - rect.left;
        const mouseY = e.clientY - rect.top;

        const cssWidth = rect.width;
        const cssHeight = rect.height;

        // Convert mouse position to world coordinates
        // L'origine est au centre du canvas, avec zoom et pan appliqués
        const worldX = (mouseX - cssWidth / 2 - pan.x) / zoom;
        const worldY = (mouseY - cssHeight / 2 - pan.y) / zoom;

        // Check if clicking on a node (en world coordinates)
        const nodeRadius = 30 / zoom;
        let clickedNode: Node | null = null;

        // Recalculer les positions en world space pour la détection de clic
        const worldNodePositions = new Map<string, { x: number; y: number }>();
        const radius = Math.min(cssWidth, cssHeight) / 5;
        const angleStep = allNodes.length > 0 ? (2 * Math.PI) / allNodes.length : 0;

        for (const [index, node] of allNodes.entries()) {
            let pos: { x: number; y: number };
            if (node.x !== undefined && node.y !== undefined && node.x !== null && node.y !== null) {
                pos = { x: node.x, y: node.y };
            } else {
                const angle = index * angleStep;
                pos = {
                    x: radius * Math.cos(angle),
                    y: radius * Math.sin(angle),
                };
            }
            worldNodePositions.set(node.id, pos);
        }

        for (const node of allNodes) {
            const pos = worldNodePositions.get(node.id);
            if (!pos) continue;

            const dx = worldX - pos.x;
            const dy = worldY - pos.y;
            const distance = Math.hypot(dx, dy);

            if (distance <= nodeRadius) {
                clickedNode = node;
                break;
            }
        }

        if (clickedNode) {
            setSelectedNode(clickedNode);
            setIsDragging(false);
        } else {
            setIsDragging(true);
            setDragStart({ x: mouseX, y: mouseY });
        }
    };

    const handleMouseMove = (e: React.MouseEvent<HTMLCanvasElement>) => {
        if (!isDragging) return;

        const canvas = canvasRef.current;
        if (!canvas) return;

        const rect = canvas.getBoundingClientRect();
        const mouseX = e.clientX - rect.left;
        const mouseY = e.clientY - rect.top;

        setPan({
            x: pan.x + (mouseX - dragStart.x),
            y: pan.y + (mouseY - dragStart.y),
        });

        setDragStart({ x: mouseX, y: mouseY });
    };

    const handleMouseUp = () => {
        setIsDragging(false);
    };

    const handleWheel = (e: React.WheelEvent<HTMLCanvasElement>) => {
        e.preventDefault();
        const delta = e.deltaY > 0 ? 0.9 : 1.1;
        setZoom((prev) => Math.max(0.5, Math.min(2, prev * delta)));
    };

    const handleResetView = () => {
        setZoom(1);
        setPan({ x: 0, y: 0 });
        setSelectedNode(null);
    };

    if (allNodes.length === 0) {
        return (
            <div className={`flex h-full items-center justify-center ${className}`}>
                <Card className="border-0 bg-transparent shadow-none">
                    <CardContent className="flex flex-col items-center gap-4 p-8">
                        <div className="rounded-full bg-muted p-6">
                            <Network className="h-12 w-12 text-muted-foreground" />
                        </div>
                        <div className="text-center">
                            <h3 className="text-lg font-semibold text-foreground">
                                Aucune topologie disponible
                            </h3>
                            <p className="mt-2 text-sm text-muted-foreground">
                                Les nodes et links du lab apparaîtront ici une fois le lab démarré.
                            </p>
                        </div>
                    </CardContent>
                </Card>
            </div>
        );
    }

    return (
        <div
            className={`relative h-full w-full ${className}`}
            style={{ minHeight: '400px', minWidth: '400px', backgroundColor: '#0f172a' }}
        >
            <canvas
                ref={canvasRef}
                className="h-full w-full cursor-move"
                style={{
                    display: 'block',
                    backgroundColor: '#0f172a',
                    minHeight: '400px',
                    minWidth: '400px'
                }}
                onMouseDown={handleMouseDown}
                onMouseMove={handleMouseMove}
                onMouseUp={handleMouseUp}
                onMouseLeave={handleMouseUp}
                onWheel={handleWheel}
            />

            {/* Topology Info Panel */}
            <div className="absolute right-4 top-4 z-10">
                <Card className="border-0 bg-white/90 shadow-lg backdrop-blur-sm dark:bg-gray-900/90">
                    <CardContent className="p-4">
                        <div className="space-y-2 text-sm">
                            <div className="flex items-center justify-between gap-4">
                                <span className="text-muted-foreground">Nodes:</span>
                                <Badge variant="secondary">{allNodes.length}</Badge>
                            </div>
                            <div className="flex items-center justify-between gap-4">
                                <span className="text-muted-foreground">Links:</span>
                                <Badge variant="secondary">{allLinks.length}</Badge>
                            </div>
                            <div className="flex items-center justify-between gap-4">
                                <span className="text-muted-foreground">Zoom:</span>
                                <Badge variant="outline">{(zoom * 100).toFixed(0)}%</Badge>
                            </div>
                        </div>
                        <div className="mt-3 space-y-1 text-xs text-muted-foreground">
                            <p>• Molette pour zoomer</p>
                            <p>• Clic-déplacer pour déplacer</p>
                        </div>
                        <Button
                            onClick={handleResetView}
                            variant="outline"
                            size="sm"
                            className="mt-3 w-full"
                        >
                            Réinitialiser la vue
                        </Button>
                    </CardContent>
                </Card>
            </div>

            {/* Node Details Panel */}
            {selectedNode && (
                <div className="absolute bottom-4 left-4 z-10">
                    <Card className="border-0 bg-white/90 shadow-lg backdrop-blur-sm dark:bg-gray-900/90">
                        <CardContent className="p-4">
                            <div className="flex items-center justify-between gap-4 mb-2">
                                <h4 className="font-semibold">{selectedNode.label || selectedNode.id}</h4>
                                <button
                                    onClick={() => setSelectedNode(null)}
                                    className="text-muted-foreground hover:text-foreground"
                                >
                                    ×
                                </button>
                            </div>
                            <div className="space-y-1 text-xs">
                                {selectedNode.node_definition && (
                                    <div>
                                        <span className="text-muted-foreground">Type: </span>
                                        <span className="font-medium">{selectedNode.node_definition}</span>
                                    </div>
                                )}
                                {selectedNode.state && (
                                    <div className="flex items-center gap-2">
                                        <span className="text-muted-foreground">État: </span>
                                        <Badge
                                            variant={
                                                selectedNode.state === 'BOOTED' || selectedNode.state === 'STARTED'
                                                    ? 'default'
                                                    : 'secondary'
                                            }
                                        >
                                            {selectedNode.state}
                                        </Badge>
                                    </div>
                                )}
                            </div>
                        </CardContent>
                    </Card>
                </div>
            )}
        </div>
    );
}


