import React, { useState, useCallback, useRef, useEffect } from 'react';
import { LabAnnotation } from '@/types/annotation';
import { useAnnotations } from '@/hooks/useAnnotations';
import { useCoordinateSystem } from '@/hooks/useCoordinateSystem';
import { Grid } from '@/components/Grid';
import { Landmarks } from '@/components/Landmarks';
import { RulerComponent } from '@/components/Ruler';
import { AnnotationToolbar } from '@/components/AnnotationToolbar';
import { AnnotationRenderer } from '@/components/AnnotationRenderer';
import { Edit3, AlertTriangle, Loader2 } from 'lucide-react';
import { Button } from '@/components/ui/button';

interface AnnotationLabProps {
  labId: string;
  className?: string;
  editMode?: boolean;

  onEditModeChange?: (editMode: boolean) => void;
}

export const AnnotationLab: React.FC<AnnotationLabProps> = ({
  labId,
  className = '',
  editMode = false,
  onEditModeChange,
}) => {
  const containerRef = useRef<HTMLDivElement>(null);

  // State management
  const [showGrid, setShowGrid] = useState(true);
  const [showRuler, setShowRuler] = useState(true);
  const [currentPosition, setCurrentPosition] = useState({ x: 0, y: 0 });

  const [editingAnnotations, setEditingAnnotations] = useState<LabAnnotation[]>([]);

  // Custom hooks
  const {
    annotations,
    loading,
    error,
    fetchAnnotations,
    createAnnotation,
    updateAnnotation,
    deleteAnnotation
  } = useAnnotations(labId);

  const {
    coordinateSystem,
    cmlToScreen,
    screenToCml,
    zoomIn,
    zoomOut,
    resetView,
  } = useCoordinateSystem();

  // Effects
  useEffect(() => {
    fetchAnnotations();
  }, [fetchAnnotations]);

  useEffect(() => {
    setEditingAnnotations([...annotations]);
  }, [annotations]);

  useEffect(() => {
    if (containerRef.current) {
      resetView(
        containerRef.current.clientWidth,
        containerRef.current.clientHeight
      );
    }
  }, [resetView]);

  // Event handlers
  const handleMouseMove = useCallback((e: React.MouseEvent) => {
    if (containerRef.current) {
      const rect = containerRef.current.getBoundingClientRect();
      const screenX = e.clientX - rect.left;
      const screenY = e.clientY - rect.top;
      const cmlPos = screenToCml(screenX, screenY);
      setCurrentPosition(cmlPos);
    }
  }, [screenToCml]);

  const handleAnnotationDrag = useCallback((id: string, deltaCmlX: number, deltaCmlY: number) => {
    setEditingAnnotations(prev => prev.map(ann => {
      if (ann.id === id) {
        const updatedAnnotation = {
          ...ann,
          x1: ann.x1 + deltaCmlX,
          y1: ann.y1 + deltaCmlY,
          x2: ann.x2 ? ann.x2 + deltaCmlX : undefined,
          y2: ann.y2 ? ann.y2 + deltaCmlY : undefined
        };

        // Update the annotation in the backend
        updateAnnotation(id, updatedAnnotation);

        return updatedAnnotation;
      }
      return ann;
    }));
  }, [updateAnnotation]);

  const handleCreateAnnotation = useCallback(async (type: string) => {
    const newAnnotation: Omit<LabAnnotation, 'id'> = {
      type: type as LabAnnotation['type'],
      x1: currentPosition.x,
      y1: currentPosition.y,
      x2: type !== 'text' ? currentPosition.x + 1000 : undefined,
      y2: type !== 'text' ? currentPosition.y + 500 : undefined,
      rotation: 0,
      z_index: 0,
      thickness: 2,
      color: '#FF6B35',
      border_color: '#000000',
      border_style: 'solid',
      text_content: type === 'text' ? 'Nouveau texte' : undefined,
      text_size: type === 'text' ? 16 : undefined,
      text_font: type === 'text' ? 'Arial' : undefined,
      text_bold: false,
      text_italic: false,
    };

    try {
      await createAnnotation(newAnnotation);
    } catch (err) {
      console.error('Failed to create annotation:', err);
    }
  }, [createAnnotation, currentPosition]);



  const handleEditModeToggle = useCallback(() => {
    const newEditMode = !editMode;
    onEditModeChange?.(newEditMode);
  }, [editMode, onEditModeChange]);



  // Render states
  if (loading) {
    return (
      <div className={`relative w-full h-96 flex flex-col items-center justify-center bg-gray-50 dark:bg-gray-900 rounded-lg ${className}`}>
        <Loader2 className="w-8 h-8 animate-spin text-blue-600 mb-4" />
        <div className="text-sm text-gray-600 dark:text-gray-300">Chargement des annotations...</div>
      </div>
    );
  }

  if (error) {
    return (
      <div className={`relative w-full h-96 flex flex-col items-center justify-center bg-gray-50 dark:bg-gray-900 rounded-lg ${className}`}>
        <AlertTriangle className="w-12 h-12 text-red-500 mb-4" />
        <div className="text-red-700 dark:text-red-300 text-center max-w-md">{error}</div>
        <Button
          onClick={fetchAnnotations}
          className="mt-4"
          variant="outline"
        >
          Réessayer
        </Button>
      </div>
    );
  }

  const currentAnnotations = editMode ? editingAnnotations : annotations;

  return (
    <div
      ref={containerRef}
      className={`relative w-full h-96 bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-900 rounded-lg border overflow-hidden ${className}`}
      onMouseMove={handleMouseMove}
    >
      {/* Grille et repères */}
      <Grid coordinateSystem={coordinateSystem} visible={showGrid} />
      <Landmarks coordinateSystem={coordinateSystem} />

      {/* Annotations */}
      <div className="absolute inset-0">
        {currentAnnotations.map(annotation => (
          <AnnotationRenderer
            key={annotation.id}
            annotation={annotation}
            isEditing={editMode}
            coordinateSystem={{ cmlToScreen, screenToCml }}
            onSelect={() => {}}
            onDelete={(id: string) => deleteAnnotation(id)}
            onDrag={handleAnnotationDrag}
          />
        ))}
      </div>

      {/* Interface utilisateur */}
      <AnnotationToolbar
        showGrid={showGrid}
        showRuler={showRuler}
        onToggleGrid={() => setShowGrid(!showGrid)}
        onToggleRuler={() => setShowRuler(!showRuler)}
        onZoomIn={zoomIn}
        onZoomOut={zoomOut}
        onCreateAnnotation={handleCreateAnnotation}
      />

      <RulerComponent position={currentPosition} visible={showRuler} />

      {/* Bouton de basculement du mode édition */}
      <button
        onClick={handleEditModeToggle}
        className={`absolute top-4 right-4 px-3 py-2 rounded-md text-sm shadow-lg z-40 transition-colors ${
          editMode
            ? 'bg-blue-600 text-white hover:bg-blue-700'
            : 'bg-gray-200 text-gray-700 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600'
        }`}
        title={editMode ? 'Quitter le mode édition' : 'Activer le mode édition'}
      >
        <Edit3 className="w-4 h-4" />
      </button>

      {/* Indicateur de mode édition */}
      {editMode && (
        <div className="absolute top-4 right-16 bg-blue-600 text-white px-3 py-2 rounded-md text-sm shadow-lg z-40">
          Mode Édition
        </div>
      )}
    </div>
  );
};

export default AnnotationLab;
