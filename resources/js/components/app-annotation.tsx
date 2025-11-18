import React, { useState, useEffect, useRef, useCallback, Component, ErrorInfo, ReactNode } from 'react';
import { LabAnnotation } from '../types';
import axios from 'axios';
import {
    Edit3,
    Save,
    X,
    Loader2,
    Type,
    Square,
    Circle,
    Minus,
    Palette,
    Settings,
    AlertTriangle,
    Trash2,

} from 'lucide-react';
import { Card, CardContent } from '@/components/ui/card';

import { Button } from '@/components/ui/button';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { Separator } from '@/components/ui/separator';



// Error Boundary Component
interface ErrorBoundaryProps {
    children: ReactNode;
    fallback?: ReactNode;
}

interface ErrorBoundaryState {
    hasError: boolean;
    error?: Error;
}

class AnnotationErrorBoundary extends Component<{ children: ReactNode; fallback?: ReactNode }, { hasError: boolean; error?: Error }> {
    constructor(props: ErrorBoundaryProps) {
        super(props);
        this.state = { hasError: false };
    }

    static getDerivedStateFromError(error: Error): ErrorBoundaryState {
        return { hasError: true, error };
    }

    componentDidCatch(error: Error, errorInfo: ErrorInfo) {
        console.error('AnnotationLab Error:', error, errorInfo);
    }

    render() {
        if (this.state.hasError) {
            return this.props.fallback || (
                <div className="absolute inset-0 flex flex-col items-center justify-center bg-gray-50 dark:bg-gray-900 rounded-lg p-8">
                    <div className="w-16 h-16 rounded-full bg-red-100 dark:bg-red-900/20 flex items-center justify-center mb-4">
                        <AlertTriangle className="w-8 h-8 text-red-600 dark:text-red-400" />
                    </div>
                    <div className="text-lg font-medium text-red-700 dark:text-red-300 mb-2">
                        Component Error
                    </div>
                    <div className="text-sm text-gray-600 dark:text-gray-400 text-center max-w-md mb-4">
                        An error occurred while rendering the annotation component. This might be due to invalid data or a rendering issue.
                    </div>
                    <Button
                        onClick={() => this.setState({ hasError: false })}
                        variant="outline"
                        size="sm"
                    >
                        Try Again
                    </Button>
                </div>
            );
        }

        return this.props.children;
    }
}

interface AnnotationLabProps {
    labId: string;
    className?: string;
    editMode?: boolean;
    onEditModeChange?: (editMode: boolean) => void;
}

// Main AnnotationLab component
const AnnotationLab: React.FC<AnnotationLabProps> = ({
    labId,
    className = '',
    editMode = false,
    onEditModeChange
}) => {
    const [annotations, setAnnotations] = useState<LabAnnotation[]>([]);
    const [editingAnnotations, setEditingAnnotations] = useState<LabAnnotation[]>([]);
    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [selectedAnnotation, setSelectedAnnotation] = useState<LabAnnotation | null>(null);
    const [showPropertiesPanel, setShowPropertiesPanel] = useState(false);
    const dragRefs = useRef<{ [key: string]: HTMLElement | null }>({});
    const [draggingAnnotation, setDraggingAnnotation] = useState<string | null>(null);
    const [dragStart, setDragStart] = useState({ x: 0, y: 0, annotationX: 0, annotationY: 0 });

    // Fetch annotations and lab schema
    useEffect(() => {
        const fetchLabData = async () => {
            try {
                setLoading(true);
                setError(null);

                // Fetch annotations
                const annotationsResponse = await axios.get(`labs/${labId}/annotations`, {
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                    withCredentials: true,
                });

                console.log('Annotations response:', annotationsResponse);
                console.log('Annotations data:', annotationsResponse.data);

                if (annotationsResponse) {
                    setAnnotations(annotationsResponse.data);
                    setEditingAnnotations(JSON.parse(JSON.stringify(annotationsResponse.data)));
                    // Debug: Log first annotation to see its structure
                    if (annotationsResponse.data.length > 0) {
                        console.log('Sample annotation structure:', annotationsResponse.data[0]);
                    }
                }

            } catch (err) {
                console.error('Error fetching lab data:', err);
                if (axios.isAxiosError(err) && err.response) {
                    if (err.response.status === 401) {
                        setError('Authentication required. Please log in again.');
                    } else if (err.response.status === 403) {
                        setError('Access denied. Invalid lab ID format.');
                    } else if (err.response.status === 404) {
                        const errorData = err.response.data;
                        if (errorData && errorData.body) {
                            try {
                                const cmlError = JSON.parse(errorData.body);
                                if (cmlError.message && cmlError.message.includes('annotations')) {
                                    setError('Annotations are not supported by this CML instance or lab.');
                                } else {
                                    setError('Annotations not available for this lab or CML instance.');
                                }
                            } catch {
                                setError('Annotations not available for this lab or CML instance.');
                            }
                        } else {
                            setError('Annotations not available for this lab or CML instance.');
                        }
                    } else {
                        setError(`Server error: ${err.response.status} - ${err.response.statusText}`);
                    }
                } else if (axios.isAxiosError(err) && err.request) {
                    setError('Network error. Check your connection.');
                } else {
                    setError('Failed to load lab annotations');
                }
            } finally {
                setLoading(false);
            }
        };

        if (labId) {
            fetchLabData();
        }
    }, [labId]);

    const handleAnnotationDrag = useCallback((id: string, deltaX: number, deltaY: number) => {
        setEditingAnnotations(prev => prev.map(ann => {
            if (ann.id === id) {
                const cmlDeltaX = deltaX / 0.1;
                const cmlDeltaY = deltaY / 0.1;
                return {
                    ...ann,
                    x1: ann.x1 + cmlDeltaX,
                    y1: ann.y1 + cmlDeltaY,
                    x2: ann.x2 ? ann.x2 + cmlDeltaX : undefined,
                    y2: ann.y2 ? ann.y2 + cmlDeltaY : undefined
                };
            }
            return ann;
        }));
    }, []);

    const saveChanges = useCallback(async () => {
        setSaving(true);
        try {
            const originalMap = new Map(annotations.map(a => [a.id, a]));
            const changes = editingAnnotations.filter(ann => {
                const original = originalMap.get(ann.id);
                return original && (
                    original.x1 !== ann.x1 || original.y1 !== ann.y1 ||
                    original.x2 !== ann.x2 || original.y2 !== ann.y2
                );
            });

            for (const annotation of changes) {
                await axios.patch(`/labs/${labId}/annotations/${annotation.id}`, {
                    x1: annotation.x1,
                    y1: annotation.y1,
                    x2: annotation.x2,
                    y2: annotation.y2
                }, {
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                    withCredentials: true,
                });
            }

            setAnnotations(JSON.parse(JSON.stringify(editingAnnotations)));
        } catch (err) {
            console.error('Error saving annotations:', err);
            setError('Failed to save annotation changes');
        } finally {
            setSaving(false);
        }
    }, [annotations, editingAnnotations, labId]);

    const cancelChanges = useCallback(() => {
        setEditingAnnotations(JSON.parse(JSON.stringify(annotations)));
    }, [annotations]);

    // Gestion du drag global
    useEffect(() => {
        if (!draggingAnnotation) return;

        const handleMouseMove = (e: MouseEvent) => {
            if (!draggingAnnotation || !editMode) return;
            e.preventDefault();
            const deltaX = (e.clientX - dragStart.x);
            const deltaY = (e.clientY - dragStart.y);
            handleAnnotationDrag(draggingAnnotation, deltaX, deltaY);
        };

        const handleMouseUp = () => {
            setDraggingAnnotation(null);
        };

        document.addEventListener('mousemove', handleMouseMove);
        document.addEventListener('mouseup', handleMouseUp);
        
        return () => {
            document.removeEventListener('mousemove', handleMouseMove);
            document.removeEventListener('mouseup', handleMouseUp);
        };
    }, [draggingAnnotation, dragStart, editMode, handleAnnotationDrag]);

    // Create new annotation
    // const createAnnotation = useCallback(async (type: 'text' | 'rectangle' | 'ellipse' | 'line', x: number, y: number) => {
    //     try {
    //         const scale = 0.1;
    //         const offsetX = 15000;
    //         const offsetY = 15000;

    //         // Convert screen coordinates to CML coordinates
    //         const cmlX = (x / scale) - offsetX;
    //         const cmlY = (y / scale) - offsetY;

    //         const newAnnotation: Omit<LabAnnotation, 'id'> = {
    //             type,
    //             x1: cmlX,
    //             y1: cmlY,
    //             x2: type !== 'text' ? cmlX + 1000 : undefined,
    //             y2: type !== 'text' ? cmlY + 500 : undefined,
    //             rotation: 0,
    //             z_index: 0,
    //             thickness: 2,
    //             color: '#FF6B35',
    //             border_color: '#000000',
    //             border_style: 'solid',
    //             text_content: type === 'text' ? 'New Text' : undefined,
    //             text_size: type === 'text' ? 12 : undefined,
    //             text_font: type === 'text' ? 'Arial' : undefined,
    //             text_bold: false,
    //             text_italic: false,
    //             border_radius: type === 'rectangle' ? 0 : undefined,
    //             line_start: type === 'line' ? null : undefined,
    //             line_end: type === 'line' ? null : undefined,
    //         };

    //         const response = await axios.post(`labs/${labId}/annotations`, newAnnotation, {
    //             headers: {
    //                 'Accept': 'application/json',
    //                 'Content-Type': 'application/json',
    //             },
    //             withCredentials: true,
    //         });

    //         if (response.data) {
    //             const updatedAnnotations = [...annotations, response.data];
    //             setAnnotations(updatedAnnotations);
    //             setEditingAnnotations(JSON.parse(JSON.stringify(updatedAnnotations)));
    //         }
    //     } catch (err) {
    //         console.error('Error creating annotation:', err);
    //         setError('Failed to create annotation');
    //     }
    // }, [annotations, labId]);

    const deleteAnnotation = useCallback(async (annotationId: string) => {
        try {
            await axios.delete(`labs/${labId}/annotations/${annotationId}`, {
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
                withCredentials: true,
            });

            const updatedAnnotations = annotations.filter(ann => ann.id !== annotationId);
            setAnnotations(updatedAnnotations);
            setEditingAnnotations(JSON.parse(JSON.stringify(updatedAnnotations)));
        } catch (err) {
            console.error('Error deleting annotation:', err);
            setError('Failed to delete annotation');
        }
    }, [annotations, labId]);

    // Update annotation properties
    // const updateAnnotation = useCallback(async (annotationId: string, updates: Partial<LabAnnotation>) => {
    //     try {
    //         const response = await axios.patch(`labs/${labId}/annotations/${annotationId}`, updates, {
    //             headers: {
    //                 'Accept': 'application/json',
    //                 'Content-Type': 'application/json',
    //             },
    //             withCredentials: true,
    //         });

    //         if (response.data) {
    //             const updatedAnnotations = annotations.map(ann =>
    //                 ann.id === annotationId ? response.data : ann
    //             );
    //             setAnnotations(updatedAnnotations);
    //             setEditingAnnotations(JSON.parse(JSON.stringify(updatedAnnotations)));
    //         }
    //     } catch (err) {
    //         console.error('Error updating annotation:', err);
    //         setError('Failed to update annotation');
    //     }
    // }, [annotations, labId]);

    // Handle annotation click for selection
    const handleAnnotationClick = useCallback((annotation: LabAnnotation, event: React.MouseEvent) => {
        event.stopPropagation();

        if (editMode) {
            setSelectedAnnotation(annotation);
            setShowPropertiesPanel(true);
        }
    }, [editMode]);

    // Handle property updates from the properties panel
    const handlePropertyUpdate = useCallback((updates: Partial<LabAnnotation>) => {
        if (selectedAnnotation) {
            // Update the editing annotations state directly for immediate UI feedback
            setEditingAnnotations(prev => prev.map(ann =>
                ann.id === selectedAnnotation.id ? { ...ann, ...updates } : ann
            ));

            // Update the selected annotation for the properties panel
            setSelectedAnnotation(prev => prev ? { ...prev, ...updates } : null);
        }
    }, [selectedAnnotation]);

    // Handle toolbar button clicks
    const handleToolbarAction = useCallback((action: string) => {
        console.log('Toolbar action:', action);
        // For now, just log the action - we can implement creation later
        // TODO: Implement annotation creation functionality
    }, []);







    if (loading) {
        return (
            <div className={`absolute inset-0 flex flex-col items-center justify-center bg-gray-50 dark:bg-gray-900 rounded-lg ${className}`}>
                <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mb-4"></div>
                <div className="text-sm text-gray-600 dark:text-gray-300 font-medium">Loading lab annotations...</div>
                <div className="text-xs text-gray-500 dark:text-gray-400 mt-2">Analyzing lab schema and topology</div>
            </div>
        );
    }

    if (error) {
        return (
            <div className={`absolute inset-0 flex flex-col items-center justify-center bg-gray-50 dark:bg-gray-900 rounded-lg ${className}`}>
                <div className="w-12 h-12 rounded-full bg-red-100 dark:bg-red-900/20 flex items-center justify-center mb-4">
                    <svg className="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
                    </svg>
                </div>
                <div className="text-sm font-medium text-red-700 dark:text-red-300 mb-2">Unable to Load Annotations</div>
                <div className="text-xs text-gray-600 dark:text-gray-400 text-center max-w-xs">{error}</div>
                <div className="text-xs text-gray-500 mt-3">Note: Annotations may not be available on all CML instances or labs</div>
            </div>
        );
    }

    if (annotations.length === 0) {
        return (
            <div className={`absolute inset-0 flex flex-col items-center justify-center bg-gray-50 dark:bg-gray-900 rounded-lg ${className}`}>
                <div className="w-12 h-12 rounded-full bg-blue-100 dark:bg-blue-900/20 flex items-center justify-center mb-4">
                    <svg className="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
                <div className="text-sm font-medium text-gray-700 dark:text-gray-200">No Annotations Found</div>
                <div className="text-xs text-gray-600 dark:text-gray-400 mt-2 max-w-xs text-center">
                    This lab doesn't have any annotations available.
                </div>
                {editMode && (
                    <button
                        onClick={() => onEditModeChange?.(false)}
                        className="mt-4 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
                    >
                        Exit Edit Mode
                    </button>
                )}
            </div>
        );
    }

    // Utility function to convert hex color with alpha to rgba
    const hexToRgba = (hex: string | undefined, fallback: string = 'transparent') => {
        if (!hex) return fallback;

        // Handle hex colors with alpha (like #808080FF)
        if (hex.length === 9 && hex.startsWith('#')) {
            const r = parseInt(hex.slice(1, 3), 16);
            const g = parseInt(hex.slice(3, 5), 16);
            const b = parseInt(hex.slice(5, 7), 16);
            const a = parseInt(hex.slice(7, 9), 16) / 255;
            return `rgba(${r}, ${g}, ${b}, ${a})`;
        }

        // Handle regular hex colors
        if (hex.length === 7 && hex.startsWith('#')) {
            return hex;
        }

        return fallback;
    };

    const renderAnnotation = (annotation: LabAnnotation, isEditing: boolean = false) => {
        const scale = 0.1;
        const offsetX = 15000;
        const offsetY = 15000;

        const transformedX1 = (annotation.x1 + offsetX) * scale;
        const transformedY1 = (annotation.y1 + offsetY) * scale;

        const commonStyle: React.CSSProperties = {
            zIndex: annotation.z_index || 0,
            position: 'absolute',
            left: `${transformedX1}px`,
            top: `${transformedY1}px`,
        };

        // Handle rotation
        if (annotation.rotation) {
            commonStyle.transform = `rotate(${annotation.rotation}deg)`;
        }

        // Handle border properties
        if (annotation.border_color) {
            commonStyle.borderColor = hexToRgba(annotation.border_color, 'transparent');
        }
        if (annotation.thickness) {
            commonStyle.borderWidth = `${annotation.thickness}px`;
        }
        if (annotation.border_style) {
            commonStyle.borderStyle = annotation.border_style === '' ? 'solid' : annotation.border_style;
        }

        // Handle background/fill color
        if (annotation.color) {
            commonStyle.backgroundColor = hexToRgba(annotation.color, 'transparent');
        }

        if (isEditing) {
            commonStyle.boxShadow = '0 0 0 2px rgba(59, 130, 246, 0.5)';
            commonStyle.cursor = 'move';
        }

        const annotationContent = (
            <div key={annotation.id} style={commonStyle}>
                {annotation.type === 'text' && (
                    <div
                        style={{
                            whiteSpace: 'pre-wrap',
                            fontSize: annotation.text_size || 12,
                            fontFamily: annotation.text_font || 'Arial',
                            fontWeight: annotation.text_bold ? 'bold' : 'normal',
                            fontStyle: annotation.text_italic ? 'italic' : 'normal',
                            color: annotation.color || 'black',
                        }}
                    >
                        {annotation.text_content}
                    </div>
                )}

                {annotation.type === 'rectangle' && (
                    <div
                        style={{
                            width: `${Math.abs((annotation.x2 || annotation.x1) - annotation.x1) * scale}px`,
                            height: `${Math.abs((annotation.y2 || annotation.y1) - annotation.y1) * scale}px`,
                            borderRadius: annotation.border_radius || 0,
                        }}
                    />
                )}

                {annotation.type === 'ellipse' && (
                    <div
                        style={{
                            width: `${Math.abs((annotation.x2 || annotation.x1) - annotation.x1) * scale}px`,
                            height: `${Math.abs((annotation.y2 || annotation.y1) - annotation.y1) * scale}px`,
                            borderRadius: '50%',
                        }}
                    />
                )}

                {annotation.type === 'line' && (() => {
                    // Handle case where x2 or y2 might be undefined
                    const x2 = annotation.x2 ?? annotation.x1;
                    const y2 = annotation.y2 ?? annotation.y1;

                    const deltaX = x2 - annotation.x1;
                    const deltaY = y2 - annotation.y1;

                    // Calculate line properties
                    const length = Math.sqrt(deltaX * deltaX + deltaY * deltaY) * scale;
                    const angle = Math.atan2(deltaY, deltaX) * (180 / Math.PI);

                    // Handle zero-length lines (point annotations)
                    if (length === 0) {
                        return (
                            <div
                                style={{
                                    width: `${annotation.thickness || 1}px`,
                                    height: `${annotation.thickness || 1}px`,
                                    backgroundColor: hexToRgba(annotation.color, 'black'),
                                    borderRadius: '50%',
                                }}
                            />
                        );
                    }

                    return (
                        <div
                            style={{
                                width: `${length}px`,
                                height: `${annotation.thickness || 1}px`,
                                backgroundColor: hexToRgba(annotation.color, 'black'),
                                transform: `rotate(${angle}deg)`,
                                transformOrigin: '0 0',
                            }}
                        >
                            {/* Line start marker */}
                            {annotation.line_start && (
                                <div
                                    style={{
                                        position: 'absolute',
                                        left: `-${(annotation.thickness || 5) + 2}px`,
                                        top: '50%',
                                        transform: 'translateY(-50%)',
                                        width: `${(annotation.thickness || 5) * 2}px`,
                                        height: `${(annotation.thickness || 5) * 2}px`,
                                        backgroundColor: hexToRgba(annotation.color, 'black'),
                                        clipPath: annotation.line_start === 'arrow'
                                            ? `polygon(100% 50%, 0 0, 0 100%)`
                                            : 'circle()',
                                    }}
                                />
                            )}

                            {/* Line end marker */}
                            {annotation.line_end && (
                                <div
                                    style={{
                                        position: 'absolute',
                                        right: `-${(annotation.thickness || 5) + 2}px`,
                                        top: '50%',
                                        transform: 'translateY(-50%)',
                                        width: `${(annotation.thickness || 5) * 2}px`,
                                        height: `${(annotation.thickness || 5) * 2}px`,
                                        backgroundColor: hexToRgba(annotation.color, 'black'),
                                        clipPath: annotation.line_end === 'arrow'
                                            ? `polygon(0 50%, 100% 0, 100% 100%)`
                                            : 'circle()',
                                    }}
                                />
                            )}
                        </div>
                    );
                })()}
            </div>
        );

        const handleMouseDown = (e: React.MouseEvent) => {
            if (!isEditing) return;
            e.preventDefault();
            e.stopPropagation();
            setDraggingAnnotation(annotation.id);
            setDragStart({
                x: e.clientX,
                y: e.clientY,
                annotationX: transformedX1,
                annotationY: transformedY1,
            });
        };

        if (isEditing) {
            return (
                <div
                    key={`draggable-${annotation.id}`}
                    ref={(el) => { dragRefs.current[annotation.id] = el; }}
                    className="absolute group cursor-pointer"
                    style={{
                        left: `${transformedX1}px`,
                        top: `${transformedY1}px`,
                        cursor: draggingAnnotation === annotation.id ? 'grabbing' : 'move',
                    }}
                    onMouseDown={handleMouseDown}
                    onClick={(e) => handleAnnotationClick(annotation, e)}
                >
                    {/* Delete button for annotations in edit mode */}
                    <Button
                        variant="destructive"
                        size="sm"
                        className="absolute -top-2 -right-2 w-6 h-6 p-0 opacity-0 group-hover:opacity-100 transition-opacity z-10"
                        onClick={(e) => {
                            e.stopPropagation();
                            deleteAnnotation(annotation.id);
                        }}
                    >
                        <Trash2 className="w-3 h-3" />
                    </Button>
                    {annotationContent}
                </div>
            );
        }

        return (
            <div
                key={annotation.id}
                style={{
                    position: 'absolute',
                    left: `${transformedX1}px`,
                    top: `${transformedY1}px`,
                    ...commonStyle
                }}
            >
                {annotationContent}
            </div>
        );
    };

    const currentAnnotations = editMode ? editingAnnotations : annotations;

    return (
        <TooltipProvider>
            <div className={`absolute inset-0 ${editMode ? 'pointer-events-auto' : 'pointer-events-none'} ${className}`}>

                {/* Annotation Creation Toolbar */}
                {editMode && (
                    <div className="absolute top-4 left-1/2 transform -translate-x-1/2 z-50">
                        <Card className="bg-background/90 backdrop-blur-sm border shadow-lg">
                            <CardContent className="p-3">
                                <div className="flex items-center gap-1">
                                    <span className="text-xs font-medium text-muted-foreground mr-2">Create:</span>

                                    <TooltipProvider>
                                        <Tooltip>
                                            <TooltipTrigger asChild>
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    className="w-8 h-8 p-0"
                                                    onClick={() => handleToolbarAction('text')}
                                                >
                                                    <Type className="w-4 h-4" />
                                                </Button>
                                            </TooltipTrigger>
                                            <TooltipContent>Add Text Annotation</TooltipContent>
                                        </Tooltip>
                                    </TooltipProvider>

                                    <TooltipProvider>
                                        <Tooltip>
                                            <TooltipTrigger asChild>
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    className="w-8 h-8 p-0"
                                                    onClick={() => handleToolbarAction('rectangle')}
                                                >
                                                    <Square className="w-4 h-4" />
                                                </Button>
                                            </TooltipTrigger>
                                            <TooltipContent>Add Rectangle</TooltipContent>
                                        </Tooltip>
                                    </TooltipProvider>

                                    <TooltipProvider>
                                        <Tooltip>
                                            <TooltipTrigger asChild>
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    className="w-8 h-8 p-0"
                                                    onClick={() => handleToolbarAction('ellipse')}
                                                >
                                                    <Circle className="w-4 h-4" />
                                                </Button>
                                            </TooltipTrigger>
                                            <TooltipContent>Add Circle/Ellipse</TooltipContent>
                                        </Tooltip>
                                    </TooltipProvider>

                                    <TooltipProvider>
                                        <Tooltip>
                                            <TooltipTrigger asChild>
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    className="w-8 h-8 p-0"
                                                    onClick={() => handleToolbarAction('line')}
                                                >
                                                    <Minus className="w-4 h-4" />
                                                </Button>
                                            </TooltipTrigger>
                                            <TooltipContent>Add Line</TooltipContent>
                                        </Tooltip>
                                    </TooltipProvider>

                                    <Separator orientation="vertical" className="h-6 mx-2" />

                                    <TooltipProvider>
                                        <Tooltip>
                                            <TooltipTrigger asChild>
                                                <Button variant="ghost" size="sm" className="w-8 h-8 p-0">
                                                    <Palette className="w-4 h-4" />
                                                </Button>
                                            </TooltipTrigger>
                                            <TooltipContent>Color Picker</TooltipContent>
                                        </Tooltip>
                                    </TooltipProvider>

                                    <TooltipProvider>
                                        <Tooltip>
                                            <TooltipTrigger asChild>
                                                <Button variant="ghost" size="sm" className="w-8 h-8 p-0">
                                                    <Settings className="w-4 h-4" />
                                                </Button>
                                            </TooltipTrigger>
                                            <TooltipContent>Properties</TooltipContent>
                                        </Tooltip>
                                    </TooltipProvider>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                )}

                {/* Properties Panel */}
                {editMode && selectedAnnotation && showPropertiesPanel && (
                    <div className="absolute top-4 right-4 z-50 w-80">
                        <Card className="bg-background/95 backdrop-blur-sm border shadow-xl">
                            <CardContent className="p-4">
                                <div className="flex items-center justify-between mb-4">
                                    <h3 className="font-semibold text-sm">Annotation Properties</h3>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => {
                                            setSelectedAnnotation(null);
                                            setShowPropertiesPanel(false);
                                        }}
                                        className="w-6 h-6 p-0"
                                    >
                                        <X className="w-4 h-4" />
                                    </Button>
                                </div>

                                <div className="space-y-4">
                                    {/* Type */}
                                    <div>
                                        <label className="text-xs font-medium text-muted-foreground">Type</label>
                                        <div className="text-sm font-medium capitalize">{selectedAnnotation.type}</div>
                                    </div>

                                    {/* Text Content (for text annotations) */}
                                    {selectedAnnotation.type === 'text' && (
                                        <div>
                                            <label htmlFor="text-content" className="text-xs font-medium text-muted-foreground">Text Content</label>
                                            <textarea
                                                id="text-content"
                                                className="w-full mt-1 px-2 py-1 text-sm border rounded"
                                                value={selectedAnnotation.text_content || ''}
                                                onChange={(e) => handlePropertyUpdate({ text_content: e.target.value })}
                                                rows={3}
                                                placeholder="Enter annotation text..."
                                            />
                                        </div>
                                    )}

                                    {/* Position */}
                                    <div className="grid grid-cols-2 gap-2">
                                        <div>
                                            <label htmlFor="x-position" className="text-xs font-medium text-muted-foreground">X Position</label>
                                            <input
                                                id="x-position"
                                                type="number"
                                                className="w-full mt-1 px-2 py-1 text-sm border rounded"
                                                value={Math.round(selectedAnnotation.x1)}
                                                onChange={(e) => handlePropertyUpdate({ x1: parseInt(e.target.value) || 0 })}
                                            />
                                        </div>
                                        <div>
                                            <label htmlFor="y-position" className="text-xs font-medium text-muted-foreground">Y Position</label>
                                            <input
                                                id="y-position"
                                                type="number"
                                                className="w-full mt-1 px-2 py-1 text-sm border rounded"
                                                value={Math.round(selectedAnnotation.y1)}
                                                onChange={(e) => handlePropertyUpdate({ y1: parseInt(e.target.value) || 0 })}
                                            />
                                        </div>
                                    </div>

                                    {/* Colors */}
                                    <div className="grid grid-cols-2 gap-2">
                                        <div>
                                            <label htmlFor="fill-color" className="text-xs font-medium text-muted-foreground">Fill Color</label>
                                            <input
                                                id="fill-color"
                                                type="color"
                                                className="w-full mt-1 h-8 border rounded"
                                                value={selectedAnnotation.color?.startsWith('#') ? selectedAnnotation.color : '#FF6B35'}
                                                onChange={(e) => handlePropertyUpdate({ color: e.target.value })}
                                            />
                                        </div>
                                        <div>
                                            <label htmlFor="border-color" className="text-xs font-medium text-muted-foreground">Border Color</label>
                                            <input
                                                id="border-color"
                                                type="color"
                                                className="w-full mt-1 h-8 border rounded"
                                                value={selectedAnnotation.border_color?.startsWith('#') ? selectedAnnotation.border_color : '#000000'}
                                                onChange={(e) => handlePropertyUpdate({ border_color: e.target.value })}
                                            />
                                        </div>
                                    </div>

                                    {/* Thickness */}
                                    <div>
                                        <label htmlFor="border-thickness" className="text-xs font-medium text-muted-foreground">Border Thickness</label>
                                        <input
                                            id="border-thickness"
                                            type="range"
                                            min="1"
                                            max="10"
                                            className="w-full mt-1"
                                            value={selectedAnnotation.thickness || 2}
                                            onChange={(e) => handlePropertyUpdate({ thickness: parseInt(e.target.value) || 2 })}
                                        />
                                        <div className="text-xs text-muted-foreground mt-1">{selectedAnnotation.thickness || 2}px</div>
                                    </div>

                                    {/* Text properties (for text annotations) */}
                                    {selectedAnnotation.type === 'text' && (
                                        <div className="space-y-2">
                                            <div>
                                                <label htmlFor="font-size" className="text-xs font-medium text-muted-foreground">Font Size</label>
                                                <input
                                                    id="font-size"
                                                    type="range"
                                                    min="8"
                                                    max="48"
                                                    className="w-full mt-1"
                                                    value={selectedAnnotation.text_size || 12}
                                                    onChange={(e) => handlePropertyUpdate({ text_size: parseInt(e.target.value) || 12 })}
                                                />
                                                <div className="text-xs text-muted-foreground">{selectedAnnotation.text_size || 12}px</div>
                                            </div>

                                            <div className="flex gap-2">
                                                <label htmlFor="text-bold" className="flex items-center gap-1 text-xs">
                                                    <input
                                                        id="text-bold"
                                                        type="checkbox"
                                                        checked={selectedAnnotation.text_bold || false}
                                                        onChange={(e) => handlePropertyUpdate({ text_bold: e.target.checked })}
                                                    />
                                                    Bold
                                                </label>
                                                <label htmlFor="text-italic" className="flex items-center gap-1 text-xs">
                                                    <input
                                                        id="text-italic"
                                                        type="checkbox"
                                                        checked={selectedAnnotation.text_italic || false}
                                                        onChange={(e) => handlePropertyUpdate({ text_italic: e.target.checked })}
                                                    />
                                                    Italic
                                                </label>
                                            </div>
                                        </div>
                                    )}

                                    {/* Rotation */}
                                    <div>
                                        <label htmlFor="rotation" className="text-xs font-medium text-muted-foreground">Rotation</label>
                                        <input
                                            id="rotation"
                                            type="range"
                                            min="0"
                                            max="360"
                                            className="w-full mt-1"
                                            value={selectedAnnotation.rotation || 0}
                                            onChange={(e) => handlePropertyUpdate({ rotation: parseInt(e.target.value) || 0 })}
                                        />
                                        <div className="text-xs text-muted-foreground">{selectedAnnotation.rotation || 0}°</div>
                                    </div>

                                    {/* Z-Index */}
                                    <div>
                                        <label htmlFor="z-index" className="text-xs font-medium text-muted-foreground">Z-Index</label>
                                        <input
                                            id="z-index"
                                            type="number"
                                            className="w-full mt-1 px-2 py-1 text-sm border rounded"
                                            value={selectedAnnotation.z_index || 0}
                                            onChange={(e) => handlePropertyUpdate({ z_index: parseInt(e.target.value) || 0 })}
                                        />
                                    </div>

                                    {/* Action buttons */}
                                    <div className="flex gap-2 pt-2 border-t">
                                        <Button
                                            variant="destructive"
                                            size="sm"
                                            onClick={() => {
                                                if (selectedAnnotation) {
                                                    deleteAnnotation(selectedAnnotation.id);
                                                    setSelectedAnnotation(null);
                                                    setShowPropertiesPanel(false);
                                                }
                                            }}
                                            className="flex-1"
                                        >
                                            <Trash2 className="w-4 h-4 mr-1" />
                                            Delete
                                        </Button>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                )}

                {/* Edit mode controls */}
                {editMode && (
                    <div className="absolute top-4 right-4 z-50 flex gap-2">
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={cancelChanges}
                            disabled={saving}
                            className="bg-background/80 backdrop-blur-sm"
                        >
                            <X className="w-4 h-4 mr-1" />
                            Cancel
                        </Button>
                        <Button
                            variant="default"
                            size="sm"
                            onClick={saveChanges}
                            disabled={saving}
                            className="bg-primary hover:bg-primary/90"
                        >
                            {saving ? (
                                <>
                                    <Loader2 className="w-4 h-4 mr-1 animate-spin" />
                                    Saving...
                                </>
                            ) : (
                                <>
                                    <Save className="w-4 h-4 mr-1" />
                                    Save Changes
                                </>
                            )}
                        </Button>
                    </div>
                )}

                {/* Render all annotations */}
                {currentAnnotations.map(annotation =>
                    renderAnnotation(annotation, editMode)
                )}

                {/* Edit mode indicator */}
                {editMode && (
                    <div className="absolute bottom-4 left-4 bg-primary text-primary-foreground px-3 py-2 rounded-md text-sm shadow-lg flex items-center gap-2">
                        <Edit3 className="w-4 h-4" />
                        Edit Mode: Drag annotations to reposition
                    </div>
                )}


            </div>
        </TooltipProvider>
    );
};

// Wrapped component with Error Boundary
const AnnotationLabWithErrorBoundary: React.FC<AnnotationLabProps> = (props) => {
    return (
        <AnnotationErrorBoundary>
            <AnnotationLab {...props} />
        </AnnotationErrorBoundary>
    );
};

export default AnnotationLabWithErrorBoundary;
