import { useEffect, useMemo, useRef, useState } from 'react';
import { Network } from 'lucide-react';
import { Card, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';

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
    const allNodes = useMemo(() => topology?.nodes || nodes || [], [topology?.nodes, nodes]);
    const allLinks = useMemo(() => topology?.links || links || [], [topology?.links, links]);

    useEffect(() => {
        const canvas = canvasRef.current;
        if (!canvas) return;

        const ctx = canvas.getContext('2d');
        if (!ctx) return;

        // Set canvas size
        const rect = canvas.getBoundingClientRect();
        canvas.width = rect.width;
        canvas.height = rect.height;

        // Clear canvas
        ctx.clearRect(0, 0, canvas.width, canvas.height);

        // Calculate node positions if not provided (in world coordinates, before transformation)
        const nodePositions = new Map<string, { x: number; y: number }>();
        
        // Calculate center in world coordinates (inverse transform of canvas center)
        // After translate(pan.x, pan.y) and scale(zoom, zoom), canvas center (w/2, h/2) maps to:
        const centerX = (canvas.width / 2 - pan.x) / zoom;
        const centerY = (canvas.height / 2 - pan.y) / zoom;
        const radius = Math.min(canvas.width, canvas.height) / 3 / zoom;
        const angleStep = (2 * Math.PI) / Math.max(allNodes.length, 1);

        for (const [index, node] of allNodes.entries()) {
            if (node.x !== undefined && node.y !== undefined) {
                nodePositions.set(node.id, { x: node.x, y: node.y });
            } else {
                const angle = index * angleStep;
                const x = centerX + radius * Math.cos(angle);
                const y = centerY + radius * Math.sin(angle);
                nodePositions.set(node.id, { x, y });
            }
        }
        
        // Store positions for click detection
        nodePositionsRef.current = nodePositions;

        // Apply zoom and pan transformations
        ctx.save();
        ctx.translate(pan.x, pan.y);
        ctx.scale(zoom, zoom);

        // Draw links
        ctx.strokeStyle = '#64748b';
        ctx.lineWidth = 2 / zoom;
        for (const link of allLinks) {
            const node1 = nodePositions.get(link.n1 || '');
            const node2 = nodePositions.get(link.n2 || '');
            if (node1 && node2) {
                ctx.beginPath();
                ctx.moveTo(node1.x, node1.y);
                ctx.lineTo(node2.x, node2.y);
                ctx.stroke();
            }
        }

        // Draw nodes
        for (const node of allNodes) {
            const pos = nodePositions.get(node.id);
            if (!pos) continue;

            const isSelected = selectedNode?.id === node.id;
            const isRunning = node.state === 'BOOTED' || node.state === 'STARTED';

            // Determine fill color
            let fillColor = '#64748b';
            if (isSelected) {
                fillColor = '#3b82f6';
            } else if (isRunning) {
                fillColor = '#10b981';
            }

            // Node circle
            ctx.beginPath();
            ctx.arc(pos.x, pos.y, 30 / zoom, 0, 2 * Math.PI);
            ctx.fillStyle = fillColor;
            ctx.fill();
            ctx.strokeStyle = isSelected ? '#1e40af' : '#475569';
            ctx.lineWidth = 2 / zoom;
            ctx.stroke();

            // Node label
            ctx.fillStyle = '#ffffff';
            ctx.font = `${12 / zoom}px sans-serif`;
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            const label = node.label || node.id.slice(0, 8);
            ctx.fillText(label, pos.x, pos.y);
        }

        ctx.restore();
    }, [allNodes, allLinks, selectedNode, zoom, pan]);

    const handleMouseDown = (e: React.MouseEvent<HTMLCanvasElement>) => {
        const canvas = canvasRef.current;
        if (!canvas) return;
        
        const rect = canvas.getBoundingClientRect();
        const mouseX = e.clientX - rect.left;
        const mouseY = e.clientY - rect.top;
        
        // Convert mouse position to world coordinates
        const worldX = (mouseX - pan.x) / zoom;
        const worldY = (mouseY - pan.y) / zoom;
        
        // Check if clicking on a node
        const nodeRadius = 30 / zoom;
        let clickedNode: Node | null = null;
        
        for (const node of allNodes) {
            const pos = nodePositionsRef.current.get(node.id);
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
            setDragStart({ x: mouseX - pan.x, y: mouseY - pan.y });
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
            x: mouseX - dragStart.x,
            y: mouseY - dragStart.y,
        });
    };

    const handleMouseUp = () => {
        setIsDragging(false);
    };

    const handleWheel = (e: React.WheelEvent<HTMLCanvasElement>) => {
        e.preventDefault();
        const delta = e.deltaY > 0 ? 0.9 : 1.1;
        setZoom((prev) => Math.max(0.5, Math.min(2, prev * delta)));
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
        <div className={`relative h-full w-full ${className}`}>
            <canvas
                ref={canvasRef}
                className="h-full w-full cursor-move"
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


