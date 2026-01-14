import { useEffect, useMemo, useRef, useState, useCallback } from 'react';
import { Network } from 'lucide-react';
import { Card, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useLabDetails } from '@/hooks/useLabDetails';

type Node = {
    id: string;
    label?: string;
    node_definition?: string;
    state?: string;
    x?: number;
    y?: number;
    [key: string]: unknown;
};

type Interface = {
    id: string;
    label?: string;
    type?: 'physical' | 'loopback';
    is_connected?: boolean;
    state?: string;
    mac_address?: string;
    node?: string;
};

type Link = {
    id: string;
    n1?: string;
    n2?: string;
    i1?: string;
    i2?: string;
    state?: string;
    interface1?: Interface;
    interface2?: Interface;
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
    readonly labId?: string;
    readonly realtimeUpdate?: boolean;
    readonly updateInterval?: number;
};

export default function LabTopology({
    nodes,
    links,
    topology,
    className = '',
    labId,
    realtimeUpdate = true,
    updateInterval = 3000,
}: Props) {
    const canvasRef = useRef<HTMLCanvasElement>(null);
    const [selectedNode, setSelectedNode] = useState<Node | null>(null);
    const [selectedLink, setSelectedLink] = useState<Link | null>(null);
    const [hoveredNode, setHoveredNode] = useState<Node | null>(null);
    const [hoveredLink, setHoveredLink] = useState<Link | null>(null);
    const [hoverPosition, setHoverPosition] = useState({ x: 0, y: 0 });
    const [zoom, setZoom] = useState(1);
    const [pan, setPan] = useState({ x: 0, y: 0 });
    const [isDragging, setIsDragging] = useState(false);
    const [dragStart, setDragStart] = useState({ x: 0, y: 0 });
    const [currentNodes, setCurrentNodes] = useState<Node[]>(nodes);
    const [currentLinks, setCurrentLinks] = useState<Link[]>(links);
    const nodePositionsRef = useRef<Map<string, { x: number; y: number }>>(new Map());
    const linkPositionsRef = useRef<Map<string, { x1: number; y1: number; x2: number; y2: number }>>(new Map());

    // Hook pour les mises à jour en temps réel
    const { getLabDetails, details } = useLabDetails();

    // Mise à jour en temps réel (avec gestion d'erreur silencieuse et rate limiting)
    useEffect(() => {
        if (!realtimeUpdate || !labId) return;

        let errorCount = 0;
        let rateLimitCount = 0;
        const maxErrors = 3; // Arrêter après 3 erreurs consécutives
        let isPollingActive = true;
        let currentInterval = updateInterval;

        const poll = async () => {
            if (!isPollingActive) return;

            try {
                await getLabDetails(labId);
                // Réinitialiser les compteurs d'erreurs en cas de succès
                errorCount = 0;
                rateLimitCount = 0;
                currentInterval = updateInterval; // Réinitialiser l'intervalle
            } catch (err) {
                errorCount++;
                const errorMessage = err instanceof Error ? err.message : String(err);

                // Gérer les erreurs 429 (rate limiting)
                if (errorMessage.includes('429') || errorMessage.includes('Too Many Requests')) {
                    rateLimitCount++;
                    // Augmenter l'intervalle progressivement
                    currentInterval = Math.min(currentInterval * 2, 30000); // Maximum 30 secondes
                    console.warn('LabTopology: Rate limit détecté, intervalle augmenté à', currentInterval, 'ms');

                    // Si trop d'erreurs 429, arrêter temporairement
                    if (rateLimitCount >= 3) {
                        console.warn('LabTopology: Trop d\'erreurs 429, arrêt temporaire du polling');
                        isPollingActive = false;
                        // Redémarrer après 60 secondes
                        setTimeout(() => {
                            isPollingActive = true;
                            rateLimitCount = 0;
                            currentInterval = updateInterval;
                            void poll();
                        }, 60000);
                        return;
                    }
                }

                // Si l'endpoint n'existe pas (404) ou trop d'erreurs, arrêter le polling silencieusement
                if (errorMessage.includes('404') || errorCount >= maxErrors) {
                    if (errorCount === maxErrors) {
                        console.warn('LabTopology: Arrêt du polling en temps réel', {
                            reason: errorMessage.includes('404') ? 'Endpoint non disponible' : 'Trop d\'erreurs',
                            errorCount,
                        });
                    }
                    isPollingActive = false;
                }
            }
        };

        // Récupération initiale avec délai aléatoire pour éviter les requêtes simultanées
        const initialDelay = Math.random() * 2000; // Délai aléatoire entre 0 et 2 secondes
        setTimeout(() => {
            void poll();
        }, initialDelay);

        const interval = setInterval(() => {
            if (isPollingActive) {
                void poll();
            } else {
                clearInterval(interval);
            }
        }, currentInterval);

        return () => {
            isPollingActive = false;
            clearInterval(interval);
        };
    }, [realtimeUpdate, labId, updateInterval, getLabDetails]);

    // Mettre à jour les nodes et links depuis les détails
    useEffect(() => {
        if (details) {
            if (details.nodes && Array.isArray(details.nodes)) {
                setCurrentNodes(details.nodes);
            }
            if (details.links && Array.isArray(details.links)) {
                setCurrentLinks(details.links);
            }
        }
    }, [details]);

    // Combine nodes from props and topology
    const allNodes = useMemo(() => {
        const topologyNodes = topology?.nodes || [];
        const propNodes = currentNodes.length > 0 ? currentNodes : nodes;
        const combined = topologyNodes.length > 0 ? topologyNodes : propNodes;

        // Debug log
        if (combined.length === 0) {
            console.warn('LabTopology: Aucun node disponible', {
                topologyNodes: topologyNodes.length,
                propNodes: propNodes.length,
                topology,
                nodes,
            });
        }
        // Logs de debug désactivés pour réduire le bruit dans la console

        return combined;
    }, [topology, nodes, currentNodes]);

    const allLinks = useMemo(() => {
        const topologyLinks = topology?.links || [];
        const propLinks = currentLinks.length > 0 ? currentLinks : links;
        const combined = topologyLinks.length > 0 ? topologyLinks : propLinks;

        // Debug log
        if (combined.length === 0) {
            console.warn('LabTopology: Aucun link disponible', {
                topologyLinks: topologyLinks.length,
                propLinks: propLinks.length,
                topology,
                links,
            });
        }
        // Logs de debug désactivés pour réduire le bruit dans la console

        return combined;
    }, [topology, links, currentLinks]);

    useEffect(() => {
        // Logs de debug désactivés pour réduire le bruit dans la console

        // Si aucun node, le composant retourne une UI vide (sans canvas), donc on ne fait rien
        if (allNodes.length === 0) {
            return;
        }

        const canvas = canvasRef.current;
        if (!canvas) {
            // Uniquement loguer une erreur si on devrait avoir un canvas (nodes > 0)
            console.error('LabTopology: Canvas ref non disponible - canvasRef.current est null');
            return;
        }

        const ctx = canvas.getContext('2d');
        if (!ctx) {
            console.error('LabTopology: Context 2D non disponible');
            return;
        }

        // Set canvas size
        let rect = canvas.getBoundingClientRect();
        // Logs de debug désactivés pour réduire le bruit dans la console

        // Si le canvas n'a pas de dimensions CSS, forcer des dimensions minimales
        if (rect.width === 0 || rect.height === 0) {
            console.warn('LabTopology: Canvas a des dimensions 0x0, utilisation de dimensions par défaut');
            const parent = canvas.parentElement;
            if (parent) {
                const parentRect = parent.getBoundingClientRect();
                if (parentRect.width > 0 && parentRect.height > 0) {
                    rect = parentRect;
                } else {
                    // Dimensions par défaut
                    rect = { width: 800, height: 600, left: 0, top: 0 } as DOMRect;
                }
            }
        }

        // Use actual pixel dimensions, not CSS dimensions
        const dpr = globalThis.devicePixelRatio || 1;
        canvas.width = rect.width * dpr;
        canvas.height = rect.height * dpr;

        // Scale context to account for device pixel ratio
        ctx.scale(dpr, dpr);

        // Now work in CSS pixels
        const cssWidth = rect.width;
        const cssHeight = rect.height;

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

        // Calculate node positions in world coordinates (0,0 at center)
        const nodePositions = new Map<string, { x: number; y: number }>();

        // Utiliser un rayon adapté - plus petit pour être sûr de voir (en CSS pixels)
        const radius = Math.min(cssWidth, cssHeight) / 5;
        const angleStep = allNodes.length > 0 ? (2 * Math.PI) / allNodes.length : 0;

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

        // Draw links with interface information
        const linkPositions = new Map<string, { x1: number; y1: number; x2: number; y2: number }>();
        let linksDrawn = 0;

        for (const link of allLinks) {
            const node1Id = typeof link.n1 === 'string' ? link.n1 :
                typeof link.node1 === 'string' ? link.node1 :
                    typeof link.src === 'string' ? link.src : '';
            const node2Id = typeof link.n2 === 'string' ? link.n2 :
                typeof link.node2 === 'string' ? link.node2 :
                    typeof link.dst === 'string' ? link.dst : '';
            const node1 = nodePositions.get(node1Id);
            const node2 = nodePositions.get(node2Id);

            if (!node1 || !node2) {
                console.warn('LabTopology: Node position manquante pour le lien', {
                    linkId: link.id,
                    node1Id,
                    node2Id,
                    hasNode1: !!node1,
                    hasNode2: !!node2,
                    availableNodes: Array.from(nodePositions.keys()),
                });
                continue;
            }

            if (node1 && node2) {
                const isSelected = selectedLink?.id === link.id;

                // Couleur du lien selon le statut
                let linkColor = '#94a3b8'; // Gris par défaut
                let linkWidth = Math.max(2 / zoom, 1);

                // Vérifier le statut des interfaces
                const interface1 = link.interface1;
                const interface2 = link.interface2;
                const isConnected1 = interface1?.is_connected ?? false;
                const isConnected2 = interface2?.is_connected ?? false;
                const linkState = link.state || interface1?.state || interface2?.state;

                if (isSelected) {
                    linkColor = '#3b82f6'; // Bleu pour le lien sélectionné
                    linkWidth = Math.max(4 / zoom, 2);
                } else if (linkState === 'STARTED' || linkState === 'BOOTED') {
                    linkColor = '#10b981'; // Vert pour actif
                    linkWidth = Math.max(3 / zoom, 1.5);
                } else if (linkState === 'STOPPED' || linkState === 'DEFINED_ON_CORE') {
                    linkColor = '#f59e0b'; // Orange pour arrêté
                } else if (!isConnected1 || !isConnected2) {
                    linkColor = '#ef4444'; // Rouge si une interface n'est pas connectée
                }

                ctx.strokeStyle = linkColor;
                ctx.lineWidth = linkWidth;

                // Dessiner le lien
                ctx.beginPath();
                ctx.moveTo(node1.x, node1.y);
                ctx.lineTo(node2.x, node2.y);
                ctx.stroke();

                // Stocker la position du lien pour la détection de clic
                linkPositions.set(link.id, {
                    x1: node1.x,
                    y1: node1.y,
                    x2: node2.x,
                    y2: node2.y,
                });

                // Dessiner les labels des interfaces au milieu du lien
                const midX = (node1.x + node2.x) / 2;
                const midY = (node1.y + node2.y) / 2;

                // Préparer les informations à afficher
                const label1 = interface1?.label || '?';
                const label2 = interface2?.label || '?';
                const linkLabel = `${label1} ↔ ${label2}`;

                // Type d'interface
                const type1 = interface1?.type === 'loopback' ? 'L' : interface1?.type ? 'P' : '?';
                const type2 = interface2?.type === 'loopback' ? 'L' : interface2?.type ? 'P' : '?';
                const typeText = `${type1}-${type2}`;

                // Statut de connexion (utiliser les variables déjà déclarées plus haut)
                const bothConnected = isConnected1 && isConnected2;
                const statusText = bothConnected ? '✓' : '✗';
                const statusColor = bothConnected ? '#10b981' : '#ef4444';

                // Mesurer les textes pour dimensionner le fond
                ctx.font = `bold ${Math.max(10 / zoom, 8)}px sans-serif`;
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                const textMetrics = ctx.measureText(linkLabel);
                const textWidth = textMetrics.width;

                ctx.font = `${Math.max(8 / zoom, 6)}px sans-serif`;
                const typeMetrics = ctx.measureText(typeText);
                const typeWidth = typeMetrics.width;

                const maxWidth = Math.max(textWidth, typeWidth);
                const padding = 12;
                const totalHeight = 28;

                // Fond pour le texte (pour la lisibilité)
                ctx.fillStyle = '#0f172a';
                ctx.fillRect(midX - maxWidth / 2 - padding, midY - totalHeight / 2, maxWidth + padding * 2, totalHeight);

                // Labels des interfaces
                ctx.fillStyle = '#ffffff';
                ctx.font = `bold ${Math.max(10 / zoom, 8)}px sans-serif`;
                ctx.fillText(linkLabel, midX, midY - 6);

                // Type d'interface
                ctx.fillStyle = '#94a3b8';
                ctx.font = `${Math.max(8 / zoom, 6)}px sans-serif`;
                ctx.fillText(`Type: ${typeText}`, midX, midY + 6);

                // Statut de connexion à droite
                ctx.fillStyle = statusColor;
                ctx.font = `bold ${Math.max(10 / zoom, 8)}px sans-serif`;
                ctx.textAlign = 'left';
                ctx.fillText(statusText, midX + maxWidth / 2 + 2, midY - 6);
                ctx.textAlign = 'center'; // Reset

                linksDrawn++;
            }
        }

        // Stocker les positions des liens pour la détection de clic
        linkPositionsRef.current = linkPositions;

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
        }

        if (allNodes.length > 0 && nodesDrawn === 0) {
            console.error('LabTopology: Aucun node dessiné malgré la présence de nodes', {
                totalNodes: allNodes.length,
                sampleNode: allNodes[0],
            });
        }

        ctx.restore();
    }, [allNodes, allLinks, selectedNode, selectedLink, zoom, pan]);

    // Redessiner quand la taille du canvas change
    useEffect(() => {
        const canvas = canvasRef.current;
        if (!canvas) return;

        const resizeObserver = new ResizeObserver(() => {
            // Le useEffect précédent va redessiner automatiquement
        });

        resizeObserver.observe(canvas);

        // Force un redessin initial après un court délai pour s'assurer que le canvas est monté
        const timeoutId = setTimeout(() => {
            // Le useEffect précédent va redessiner automatiquement
        }, 100);

        return () => {
            clearTimeout(timeoutId);
            resizeObserver.disconnect();
        };
    }, [allNodes, allLinks, selectedNode, zoom, pan]);

    // Attacher l'événement wheel manuellement avec passive: false pour permettre preventDefault
    useEffect(() => {
        const canvas = canvasRef.current;
        if (!canvas) return;

        const wheelHandler = (e: WheelEvent) => {
            e.preventDefault();
            const delta = e.deltaY > 0 ? 0.9 : 1.1;
            setZoom((prev) => Math.max(0.5, Math.min(2, prev * delta)));
        };

        // Attacher l'événement avec passive: false
        canvas.addEventListener('wheel', wheelHandler, { passive: false });

        return () => {
            canvas.removeEventListener('wheel', wheelHandler);
        };
    }, []);

    const handleMouseMove = useCallback((e: React.MouseEvent<HTMLCanvasElement>) => {
        const canvas = canvasRef.current;
        if (!canvas) return;

        const rect = canvas.getBoundingClientRect();
        const mouseX = e.clientX - rect.left;
        const mouseY = e.clientY - rect.top;

        // Mettre à jour la position du survol pour le tooltip
        setHoverPosition({ x: e.clientX, y: e.clientY });

        // Si on est en train de déplacer, gérer le pan
        if (isDragging) {
            setPan({
                x: pan.x + (mouseX - dragStart.x),
                y: pan.y + (mouseY - dragStart.y),
            });
            setDragStart({ x: mouseX, y: mouseY });
            return;
        }

        // Détecter le survol des nodes et links
        const cssWidth = rect.width;
        const cssHeight = rect.height;
        const worldX = (mouseX - cssWidth / 2 - pan.x) / zoom;
        const worldY = (mouseY - cssHeight / 2 - pan.y) / zoom;

        // Vérifier le survol d'un node
        const nodeRadius = 30 / zoom;
        let hovered: Node | null = null;

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
                hovered = node;
                break;
            }
        }

        if (hovered) {
            setHoveredNode(hovered);
            setHoveredLink(null);
            return;
        }

        // Vérifier le survol d'un link
        const clickThreshold = 10 / zoom;
        let hoveredLink: Link | null = null;

        for (const link of allLinks) {
            const linkPos = linkPositionsRef.current.get(link.id);
            if (!linkPos) continue;

            const { x1, y1, x2, y2 } = linkPos;
            const A = worldX - x1;
            const B = worldY - y1;
            const C = x2 - x1;
            const D = y2 - y1;

            const dot = A * C + B * D;
            const lenSq = C * C + D * D;
            let param = -1;

            if (lenSq !== 0) {
                param = dot / lenSq;
            }

            let xx, yy;
            if (param < 0) {
                xx = x1;
                yy = y1;
            } else if (param > 1) {
                xx = x2;
                yy = y2;
            } else {
                xx = x1 + param * C;
                yy = y1 + param * D;
            }

            const dx = worldX - xx;
            const dy = worldY - yy;
            const distance = Math.hypot(dx, dy);

            if (distance <= clickThreshold) {
                hoveredLink = link;
                break;
            }
        }

        setHoveredNode(null);
        setHoveredLink(hoveredLink);
    }, [allNodes, allLinks, isDragging, pan, zoom, dragStart]);

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

        // Check if clicking on a link
        let clickedLink: Link | null = null;
        if (!clickedNode) {
            // Convertir la position de la souris en coordonnées world
            const worldX = (mouseX - cssWidth / 2 - pan.x) / zoom;
            const worldY = (mouseY - cssHeight / 2 - pan.y) / zoom;

            // Vérifier si on clique sur un lien (distance à la ligne)
            const clickThreshold = 10 / zoom; // Tolérance de clic

            for (const link of allLinks) {
                const linkPos = linkPositionsRef.current.get(link.id);
                if (!linkPos) continue;

                // Calculer la distance du point à la ligne
                const { x1, y1, x2, y2 } = linkPos;
                const A = worldX - x1;
                const B = worldY - y1;
                const C = x2 - x1;
                const D = y2 - y1;

                const dot = A * C + B * D;
                const lenSq = C * C + D * D;
                let param = -1;

                if (lenSq !== 0) {
                    param = dot / lenSq;
                }

                let xx, yy;
                if (param < 0) {
                    xx = x1;
                    yy = y1;
                } else if (param > 1) {
                    xx = x2;
                    yy = y2;
                } else {
                    xx = x1 + param * C;
                    yy = y1 + param * D;
                }

                const dx = worldX - xx;
                const dy = worldY - yy;
                const distance = Math.hypot(dx, dy);

                if (distance <= clickThreshold) {
                    clickedLink = link;
                    break;
                }
            }
        }

        if (clickedNode) {
            setSelectedNode(clickedNode);
            setSelectedLink(null);
            setIsDragging(false);
        } else if (clickedLink) {
            setSelectedLink(clickedLink);
            setSelectedNode(null);
            setIsDragging(false);
        } else {
            setSelectedNode(null);
            setSelectedLink(null);
            setIsDragging(true);
            setDragStart({ x: mouseX, y: mouseY });
        }
    };


    const handleMouseUp = () => {
        setIsDragging(false);
    };

    const handleMouseLeave = () => {
        setHoveredNode(null);
        setHoveredLink(null);
        setIsDragging(false);
    };

    const handleResetView = () => {
        setZoom(1);
        setPan({ x: 0, y: 0 });
        setSelectedNode(null);
        setSelectedLink(null);
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
                onMouseLeave={handleMouseLeave}
            />

            {/* Tooltip pour les nodes et links au survol */}
            {(hoveredNode || hoveredLink) && (
                <div
                    className="fixed z-50 pointer-events-none"
                    style={{
                        left: hoverPosition.x + 10,
                        top: hoverPosition.y + 10,
                    }}
                >
                    <Card className="border bg-white/95 shadow-lg backdrop-blur-sm dark:bg-gray-900/95">
                        <CardContent className="p-3">
                            {hoveredNode && (
                                <div className="space-y-1 text-xs">
                                    <div className="font-semibold">{hoveredNode.label || hoveredNode.id}</div>
                                    {hoveredNode.node_definition && (
                                        <div className="text-muted-foreground">Type: {hoveredNode.node_definition}</div>
                                    )}
                                    {hoveredNode.state && (
                                        <div>
                                            <Badge
                                                variant={
                                                    hoveredNode.state === 'BOOTED' || hoveredNode.state === 'STARTED'
                                                        ? 'default'
                                                        : 'secondary'
                                                }
                                            >
                                                {hoveredNode.state}
                                            </Badge>
                                        </div>
                                    )}
                                </div>
                            )}
                            {hoveredLink && (
                                <div className="space-y-1 text-xs">
                                    <div className="font-semibold">Lien</div>
                                    {hoveredLink.interface1 && hoveredLink.interface2 && (
                                        <div className="text-muted-foreground">
                                            {hoveredLink.interface1.label || '?'} ↔ {hoveredLink.interface2.label || '?'}
                                        </div>
                                    )}
                                    {hoveredLink.state && (
                                        <div>
                                            <Badge
                                                variant={
                                                    hoveredLink.state === 'STARTED' || hoveredLink.state === 'BOOTED'
                                                        ? 'default'
                                                        : 'secondary'
                                                }
                                            >
                                                {hoveredLink.state}
                                            </Badge>
                                        </div>
                                    )}
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>
            )}

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

            {/* Link Details Panel */}
            {selectedLink && (
                <div className="absolute bottom-4 left-4 z-10">
                    <Card className="border-0 bg-white/90 shadow-lg backdrop-blur-sm dark:bg-gray-900/90">
                        <CardContent className="p-4">
                            <div className="flex items-center justify-between gap-4 mb-3">
                                <h4 className="font-semibold">Détails du lien</h4>
                                <button
                                    onClick={() => setSelectedLink(null)}
                                    className="text-muted-foreground hover:text-foreground"
                                >
                                    ×
                                </button>
                            </div>

                            <div className="space-y-3 text-xs">
                                {/* Interface 1 */}
                                {selectedLink.interface1 && (
                                    <div className="border-b border-gray-200 dark:border-gray-700 pb-2">
                                        <div className="font-semibold text-sm mb-1">Interface 1</div>
                                        <div className="space-y-1">
                                            <div>
                                                <span className="text-muted-foreground">Label: </span>
                                                <span className="font-medium">{selectedLink.interface1.label || 'N/A'}</span>
                                            </div>
                                            {selectedLink.interface1.type && (
                                                <div>
                                                    <span className="text-muted-foreground">Type: </span>
                                                    <Badge variant="outline" className="ml-1">
                                                        {selectedLink.interface1.type === 'loopback' ? 'Loopback' : 'Physical'}
                                                    </Badge>
                                                </div>
                                            )}
                                            <div>
                                                <span className="text-muted-foreground">Connectée: </span>
                                                <Badge
                                                    variant={selectedLink.interface1.is_connected ? 'default' : 'destructive'}
                                                    className="ml-1"
                                                >
                                                    {selectedLink.interface1.is_connected ? 'Oui' : 'Non'}
                                                </Badge>
                                            </div>
                                            {selectedLink.interface1.state && (
                                                <div>
                                                    <span className="text-muted-foreground">État: </span>
                                                    <Badge variant="secondary" className="ml-1">
                                                        {selectedLink.interface1.state}
                                                    </Badge>
                                                </div>
                                            )}
                                            {selectedLink.interface1.mac_address && (
                                                <div>
                                                    <span className="text-muted-foreground">MAC: </span>
                                                    <span className="font-mono text-xs">{selectedLink.interface1.mac_address}</span>
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                )}

                                {/* Interface 2 */}
                                {selectedLink.interface2 && (
                                    <div>
                                        <div className="font-semibold text-sm mb-1">Interface 2</div>
                                        <div className="space-y-1">
                                            <div>
                                                <span className="text-muted-foreground">Label: </span>
                                                <span className="font-medium">{selectedLink.interface2.label || 'N/A'}</span>
                                            </div>
                                            {selectedLink.interface2.type && (
                                                <div>
                                                    <span className="text-muted-foreground">Type: </span>
                                                    <Badge variant="outline" className="ml-1">
                                                        {selectedLink.interface2.type === 'loopback' ? 'Loopback' : 'Physical'}
                                                    </Badge>
                                                </div>
                                            )}
                                            <div>
                                                <span className="text-muted-foreground">Connectée: </span>
                                                <Badge
                                                    variant={selectedLink.interface2.is_connected ? 'default' : 'destructive'}
                                                    className="ml-1"
                                                >
                                                    {selectedLink.interface2.is_connected ? 'Oui' : 'Non'}
                                                </Badge>
                                            </div>
                                            {selectedLink.interface2.state && (
                                                <div>
                                                    <span className="text-muted-foreground">État: </span>
                                                    <Badge variant="secondary" className="ml-1">
                                                        {selectedLink.interface2.state}
                                                    </Badge>
                                                </div>
                                            )}
                                            {selectedLink.interface2.mac_address && (
                                                <div>
                                                    <span className="text-muted-foreground">MAC: </span>
                                                    <span className="font-mono text-xs">{selectedLink.interface2.mac_address}</span>
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                )}

                                {/* Statut du lien */}
                                {selectedLink.state && (
                                    <div className="pt-2 border-t border-gray-200 dark:border-gray-700">
                                        <span className="text-muted-foreground">Statut du lien: </span>
                                        <Badge
                                            variant={
                                                selectedLink.state === 'STARTED' || selectedLink.state === 'BOOTED'
                                                    ? 'default'
                                                    : 'secondary'
                                            }
                                            className="ml-1"
                                        >
                                            {selectedLink.state}
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


