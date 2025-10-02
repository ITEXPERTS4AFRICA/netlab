import React, { useState, useEffect, useRef, useCallback } from 'react';
import { LabAnnotation } from '../types';
import axios from 'axios';
import Draggable from 'react-draggable';

interface AnnotationLabProps {
    labId: string;
    className?: string;
    editMode?: boolean;
    onEditModeChange?: (editMode: boolean) => void;
}

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
    const dragRefs = useRef<{ [key: string]: HTMLElement | null }>({});

    // All hooks must be before any early returns
    useEffect(() => {
        const fetchAnnotations = async () => {
            try {
                setLoading(true);
                setError(null);
                const response = await axios.get(`/api/labs/${labId}/annotations`, {
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                    withCredentials: true,
                });
                if (response.data && Array.isArray(response.data)) {
                    setAnnotations(response.data);
                    setEditingAnnotations(JSON.parse(JSON.stringify(response.data))); // Deep copy
                }
            } catch (err) {
                console.error('Error fetching annotations:', err);
                if (axios.isAxiosError(err) && err.response) {
                    // Server responded with error
                    if (err.response.status === 401) {
                        setError('Authentication required. Please log in again.');
                    } else if (err.response.status === 403) {
                        setError('Access denied. Invalid lab ID format.');
                    } else if (err.response.status === 404) {
                        setError('Annotations not available for this lab or CMA instance.');
                    } else {
                        setError(`Server error: ${err.response.status} - ${err.response.statusText}`);
                    }
                } else if (axios.isAxiosError(err) && err.request) {
                    // Network error
                    setError('Network error. Check your connection.');
                } else {
                    // Other error
                    setError('Failed to load lab annotations');
                }
            } finally {
                setLoading(false);
            }
        };

        if (labId) {
            fetchAnnotations();
        }
    }, [labId]);

    const handleAnnotationDrag = useCallback((id: string, deltaX: number, deltaY: number) => {
        setEditingAnnotations(prev => prev.map(ann => {
            if (ann.id === id) {
                // Transform drag deltas back to CML coordinates
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
            // Find annotations that have changed position
            const originalMap = new Map(annotations.map(a => [a.id, a]));
            const changes = editingAnnotations.filter(ann => {
                const original = originalMap.get(ann.id);
                return original && (
                    original.x1 !== ann.x1 || original.y1 !== ann.y1 ||
                    original.x2 !== ann.x2 || original.y2 !== ann.y2
                );
            });

            // Save each changed annotation
            for (const annotation of changes) {
                await axios.patch(`/api/labs/${labId}/annotations/${annotation.id}`, {
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

            // Update original annotations
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
                    This lab doesn't have any annotations or schema information available.
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

    const renderAnnotation = (annotation: LabAnnotation, isEditing: boolean = false) => {
        const scale = 0.1;
        const offsetX = 15000;
        const offsetY = 15000;

        const transformedX1 = (annotation.x1 + offsetX) * scale;
        const transformedY1 = (annotation.y1 + offsetY) * scale;

        const commonStyle: React.CSSProperties = {
            zIndex: annotation.z_index || 0,
            transform: `rotate(${annotation.rotation}deg)`,
            borderColor: annotation.border_color || 'transparent',
            borderWidth: annotation.thickness || 1,
            borderStyle: annotation.border_style || 'solid',
            backgroundColor: annotation.color || 'transparent',
        };

        // Add visual feedback for editing mode
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
                    const transformedX2 = (annotation.x2 || 0 + offsetX) * scale;
                    const transformedY2 = (annotation.y2 || 0 + offsetY) * scale;
                    const length = Math.sqrt(
                        Math.pow(transformedX2 - transformedX1, 2) +
                        Math.pow(transformedY2 - transformedY1, 2)
                    );
                    const angle = Math.atan2(
                        transformedY2 - transformedY1,
                        transformedX2 - transformedX1
                    ) * (180 / Math.PI);

                    return (
                        <div
                            style={{
                                width: `${length}px`,
                                height: `${annotation.thickness || 1}px`,
                                backgroundColor: annotation.color || 'black',
                                transform: `rotate(${angle}deg)`,
                                transformOrigin: '0 0',
                            }}
                        >
                            {annotation.line_start && (
                                <div
                                    style={{
                                        position: 'absolute',
                                        left: '-5px',
                                        top: '50%',
                                        transform: 'translateY(-50%)',
                                        width: '10px',
                                        height: '10px',
                                        backgroundColor: annotation.color || 'black',
                                        clipPath: annotation.line_start === 'arrow'
                                            ? 'polygon(100% 50%, 0 0, 0 100%)'
                                            : 'circle()',
                                    }}
                                />
                            )}
                            {annotation.line_end && (
                                <div
                                    style={{
                                        position: 'absolute',
                                        right: '-5px',
                                        top: '50%',
                                        transform: 'translateY(-50%)',
                                        width: '10px',
                                        height: '10px',
                                        backgroundColor: annotation.color || 'black',
                                        clipPath: annotation.line_end === 'arrow'
                                            ? 'polygon(0 50%, 100% 0, 100% 100%)'
                                            : 'circle()',
                                    }}
                                />
                            )}
                        </div>
                    );
                })()}
            </div>
        );

        if (isEditing) {
            return (
                <Draggable
                    key={`draggable-${annotation.id}`}
                    position={{ x: transformedX1, y: transformedY1 }}
                    onDrag={(e, data) => handleAnnotationDrag(annotation.id, data.deltaX, data.deltaY)}
                >
                    <div ref={(el) => { dragRefs.current[annotation.id] = el; }} className="absolute">
                        {annotationContent}
                    </div>
                </Draggable>
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
        <div className={`absolute inset-0 ${editMode ? 'pointer-events-auto' : 'pointer-events-none'} ${className}`}>
            {/* Edit mode controls */}
            {editMode && (
                <div className="absolute top-4 right-4 z-50 flex gap-2">
                    <button
                        onClick={cancelChanges}
                        disabled={saving}
                        className="px-3 py-1 bg-gray-600 text-white rounded text-sm hover:bg-gray-700 disabled:opacity-50"
                    >
                        Cancel
                    </button>
                    <button
                        onClick={saveChanges}
                        disabled={saving}
                        className="px-3 py-1 bg-green-600 text-white rounded text-sm hover:bg-green-700 disabled:opacity-50 flex items-center gap-1"
                    >
                        {saving ? (
                            <>
                                <div className="animate-spin rounded-full h-3 w-3 border border-white"></div>
                                Saving...
                            </>
                        ) : (
                            'Save Changes'
                        )}
                    </button>
                </div>
            )}

            {/* Render all annotations */}
            {currentAnnotations.map(annotation =>
                renderAnnotation(annotation, editMode)
            )}

            {/* Edit mode indicator */}
            {editMode && (
                <div className="absolute bottom-4 left-4 bg-blue-600 text-white px-3 py-1 rounded text-sm">
                    Edit Mode: Drag annotations to reposition
                </div>
            )}
        </div>
    );
};

export default AnnotationLab;
