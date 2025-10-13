import React from 'react';
import {
  Grid3X3, Ruler, ZoomIn, ZoomOut,
  Type, Square, Circle, Minus
} from 'lucide-react';
import { Card, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { Separator } from '@/components/ui/separator';

interface AnnotationToolbarProps {
  showGrid: boolean;
  showRuler: boolean;
  onToggleGrid: () => void;
  onToggleRuler: () => void;
  onZoomIn: () => void;
  onZoomOut: () => void;
  onCreateAnnotation: (type: string) => void;
}

export const AnnotationToolbar: React.FC<AnnotationToolbarProps> = ({
  showGrid,
  showRuler,
  onToggleGrid,
  onToggleRuler,
  onZoomIn,
  onZoomOut,
  onCreateAnnotation,
}) => {
  return (
    <div className="absolute top-4 left-4 z-50 flex flex-col gap-2">
      <Card className="bg-background/90 backdrop-blur-sm border shadow-lg">
        <CardContent className="p-3">
          <div className="flex flex-col gap-2">
            <div className="flex items-center gap-1">
              <span className="text-xs font-medium text-muted-foreground">Outils:</span>
            </div>

            <div className="flex gap-1">
              <TooltipProvider>
                <Tooltip>
                  <TooltipTrigger asChild>
                    <Button
                      variant={showGrid ? "default" : "ghost"}
                      size="sm"
                      className="w-8 h-8 p-0"
                      onClick={onToggleGrid}
                    >
                      <Grid3X3 className="w-4 h-4" />
                    </Button>
                  </TooltipTrigger>
                  <TooltipContent>Grille {showGrid ? 'ON' : 'OFF'}</TooltipContent>
                </Tooltip>
              </TooltipProvider>

              <TooltipProvider>
                <Tooltip>
                  <TooltipTrigger asChild>
                    <Button
                      variant={showRuler ? "default" : "ghost"}
                      size="sm"
                      className="w-8 h-8 p-0"
                      onClick={onToggleRuler}
                    >
                      <Ruler className="w-4 h-4" />
                    </Button>
                  </TooltipTrigger>
                  <TooltipContent>Règle {showRuler ? 'ON' : 'OFF'}</TooltipContent>
                </Tooltip>
              </TooltipProvider>
            </div>

            <Separator />

            <div className="flex gap-1">
              <TooltipProvider>
                <Tooltip>
                  <TooltipTrigger asChild>
                    <Button
                      variant="ghost"
                      size="sm"
                      className="w-8 h-8 p-0"
                      onClick={onZoomIn}
                    >
                      <ZoomIn className="w-4 h-4" />
                    </Button>
                  </TooltipTrigger>
                  <TooltipContent>Zoom +</TooltipContent>
                </Tooltip>
              </TooltipProvider>

              <TooltipProvider>
                <Tooltip>
                  <TooltipTrigger asChild>
                    <Button
                      variant="ghost"
                      size="sm"
                      className="w-8 h-8 p-0"
                      onClick={onZoomOut}
                    >
                      <ZoomOut className="w-4 h-4" />
                    </Button>
                  </TooltipTrigger>
                  <TooltipContent>Zoom -</TooltipContent>
                </Tooltip>
              </TooltipProvider>
            </div>

            <Separator />

            <div className="flex gap-1">
              <TooltipProvider>
                <Tooltip>
                  <TooltipTrigger asChild>
                    <Button
                      variant="ghost"
                      size="sm"
                      className="w-8 h-8 p-0"
                      onClick={() => onCreateAnnotation('text')}
                    >
                      <Type className="w-4 h-4" />
                    </Button>
                  </TooltipTrigger>
                  <TooltipContent>Texte</TooltipContent>
                </Tooltip>
              </TooltipProvider>

              <TooltipProvider>
                <Tooltip>
                  <TooltipTrigger asChild>
                    <Button
                      variant="ghost"
                      size="sm"
                      className="w-8 h-8 p-0"
                      onClick={() => onCreateAnnotation('rectangle')}
                    >
                      <Square className="w-4 h-4" />
                    </Button>
                  </TooltipTrigger>
                  <TooltipContent>Rectangle</TooltipContent>
                </Tooltip>
              </TooltipProvider>

              <TooltipProvider>
                <Tooltip>
                  <TooltipTrigger asChild>
                    <Button
                      variant="ghost"
                      size="sm"
                      className="w-8 h-8 p-0"
                      onClick={() => onCreateAnnotation('ellipse')}
                    >
                      <Circle className="w-4 h-4" />
                    </Button>
                  </TooltipTrigger>
                  <TooltipContent>Cercle</TooltipContent>
                </Tooltip>
              </TooltipProvider>

              <TooltipProvider>
                <Tooltip>
                  <TooltipTrigger asChild>
                    <Button
                      variant="ghost"
                      size="sm"
                      className="w-8 h-8 p-0"
                      onClick={() => onCreateAnnotation('line')}
                    >
                      <Minus className="w-4 h-4" />
                    </Button>
                  </TooltipTrigger>
                  <TooltipContent>Ligne</TooltipContent>
                </Tooltip>
              </TooltipProvider>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  );
};
