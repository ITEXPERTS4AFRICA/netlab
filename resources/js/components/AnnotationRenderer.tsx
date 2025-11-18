import React, { useRef, useState, useCallback } from 'react';
import { LabAnnotation } from '@/types/annotation';
import { Button } from '@/components/ui/button';
import { Trash2 } from 'lucide-react';

interface CoordinateSystem {
  cmlToScreen: (x: number, y: number) => { x: number; y: number };
  screenToCml: (x: number, y: number) => { x: number; y: number };
}

interface AnnotationRendererProps {
  annotation: LabAnnotation;
  isEditing: boolean;
  coordinateSystem: CoordinateSystem;
  onSelect: (annotation: LabAnnotation) => void;
  onDelete: (id: string) => void;
  onDrag: (id: string, deltaX: number, deltaY: number) => void;
}

export const AnnotationRenderer: React.FC<AnnotationRendererProps> = ({
  annotation,
  isEditing,
  coordinateSystem,
  onSelect,
  onDelete,
  onDrag,
}) => {
  const { cmlToScreen, screenToCml } = coordinateSystem;

  const startPos = cmlToScreen(annotation.x1, annotation.y1);
  const endPos = annotation.x2 !== undefined && annotation.y2 !== undefined
    ? cmlToScreen(annotation.x2, annotation.y2)
    : null;

  const commonStyle: React.CSSProperties = {
    zIndex: (annotation.z_index || 0) + 1000,
    position: 'absolute' as const,
    left: `${startPos.x}px`,
    top: `${startPos.y}px`,
    transform: annotation.rotation ? `rotate(${annotation.rotation}deg)` : undefined,
  };

  // Appliquer les styles visuels
  if (annotation.border_color) {
    commonStyle.borderColor = hexToRgba(annotation.border_color);
  }
  if (annotation.thickness) {
    commonStyle.borderWidth = `${annotation.thickness}px`;
  }
  if (annotation.border_style) {
    commonStyle.borderStyle = annotation.border_style || 'solid';
  }
  if (annotation.color) {
    commonStyle.backgroundColor = hexToRgba(annotation.color, 'transparent');
  }

  if (isEditing) {
    commonStyle.boxShadow = '0 0 0 2px rgba(59, 130, 246, 0.8)';
    commonStyle.cursor = 'move';
  }

  const renderAnnotationContent = () => {
    switch (annotation.type) {
      case 'text':
        return (
          <div
            style={{
              whiteSpace: 'pre-wrap',
              fontSize: `${annotation.text_size || 12}px`,
              fontFamily: annotation.text_font || 'Arial, sans-serif',
              fontWeight: annotation.text_bold ? 'bold' : 'normal',
              fontStyle: annotation.text_italic ? 'italic' : 'normal',
              color: annotation.color || '#000000',
              padding: '2px 4px',
              background: hexToRgba(annotation.color || '#FFFFFF', '0.1'),
              border: `1px solid ${hexToRgba(annotation.border_color || '#000000')}`,
              borderRadius: '2px',
            }}
            className="annotation-text"
          >
            {annotation.text_content || 'Texte'}
          </div>
        );

      case 'rectangle':
        return (
          <div
            style={{
              width: endPos ? `${Math.abs(endPos.x - startPos.x)}px` : '50px',
              height: endPos ? `${Math.abs(endPos.y - startPos.y)}px` : '30px',
              borderRadius: annotation.border_radius || '0px',
              border: `${annotation.thickness || 2}px ${annotation.border_style || 'solid'} ${hexToRgba(annotation.border_color || '#000000')}`,
              backgroundColor: hexToRgba(annotation.color || '#FF6B35', '0.3'),
            }}
            className="annotation-rectangle"
          />
        );

      case 'ellipse':
        return (
          <div
            style={{
              width: endPos ? `${Math.abs(endPos.x - startPos.x)}px` : '40px',
              height: endPos ? `${Math.abs(endPos.y - startPos.y)}px` : '40px',
              borderRadius: '50%',
              border: `${annotation.thickness || 2}px ${annotation.border_style || 'solid'} ${hexToRgba(annotation.border_color || '#000000')}`,
              backgroundColor: hexToRgba(annotation.color || '#FF6B35', '0.3'),
            }}
            className="annotation-ellipse"
          />
        );

      case 'line': {
        if (!endPos) return null;

        const deltaX = endPos.x - startPos.x;
        const deltaY = endPos.y - startPos.y;
        const length = Math.sqrt(deltaX * deltaX + deltaY * deltaY);
        const angle = Math.atan2(deltaY, deltaX) * (180 / Math.PI);

        return (
          <div
            style={{
              width: `${length}px`,
              height: `${annotation.thickness || 2}px`,
              backgroundColor: hexToRgba(annotation.color || '#000000'),
              transform: `rotate(${angle}deg)`,
              transformOrigin: '0 0',
            }}
            className="annotation-line"
          />
        );
      }

      default:
        return null;
    }
  };

  const annotationContent = (
    <div
      style={commonStyle}
      className={`annotation-${annotation.type} ${isEditing ? 'editing' : ''}`}
      onClick={(e) => {
        e.stopPropagation();
        onSelect(annotation);
      }}
    >
      {renderAnnotationContent()}
      {isEditing && (
        <Button
          variant="destructive"
          size="sm"
          className="absolute -top-2 -right-2 w-6 h-6 p-0 opacity-0 group-hover:opacity-100 transition-opacity z-10"
          onClick={(e) => {
            e.stopPropagation();
            onDelete(annotation.id);
          }}
        >
          <Trash2 className="w-3 h-3" />
        </Button>
      )}
    </div>
  );

  const dragRef = useRef<HTMLDivElement>(null);
  const [isDragging, setIsDragging] = useState(false);
  const [dragStart, setDragStart] = useState({ x: 0, y: 0, startX: 0, startY: 0 });

  const handleMouseDown = useCallback((e: React.MouseEvent) => {
    if (!isEditing) return;
    e.preventDefault();
    e.stopPropagation();
    setIsDragging(true);
    setDragStart({ 
      x: e.clientX, 
      y: e.clientY, 
      startX: startPos.x, 
      startY: startPos.y 
    });
  }, [isEditing, startPos]);

  React.useEffect(() => {
    if (!isDragging) return;

    const handleMouseMove = (e: MouseEvent) => {
      if (!isDragging || !isEditing) return;
      e.preventDefault();
      const deltaX = e.clientX - dragStart.x;
      const deltaY = e.clientY - dragStart.y;
      
      const deltaCml = screenToCml(deltaX, deltaY);
      onDrag(annotation.id, deltaCml.x, deltaCml.y);
    };

    const handleMouseUp = () => {
      setIsDragging(false);
    };

    document.addEventListener('mousemove', handleMouseMove);
    document.addEventListener('mouseup', handleMouseUp);
    
    return () => {
      document.removeEventListener('mousemove', handleMouseMove);
      document.removeEventListener('mouseup', handleMouseUp);
    };
  }, [isDragging, isEditing, dragStart, screenToCml, onDrag, annotation.id]);

  if (isEditing) {
    return (
      <div
        ref={dragRef}
        className="annotation-draggable group"
        style={{
          position: 'absolute',
          left: `${startPos.x}px`,
          top: `${startPos.y}px`,
          cursor: isDragging ? 'grabbing' : 'move',
        }}
        onMouseDown={handleMouseDown}
      >
          {annotationContent}
        </div>
    );
  }

  return annotationContent;
};

// Helper function
const hexToRgba = (hex: string | undefined, fallback: string = 'transparent'): string => {
  if (!hex) return fallback;

  if (hex.length === 9 && hex.startsWith('#')) {
    const r = parseInt(hex.slice(1, 3), 16);
    const g = parseInt(hex.slice(3, 5), 16);
    const b = parseInt(hex.slice(5, 7), 16);
    const a = parseInt(hex.slice(7, 9), 16) / 255;
    return `rgba(${r}, ${g}, ${b}, ${a})`;
  }

  if (hex.length === 7 && hex.startsWith('#')) {
    return hex;
  }

  return fallback;
};
