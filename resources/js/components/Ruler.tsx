import React from 'react';
import { Ruler } from 'lucide-react';

interface RulerProps {
  position: { x: number; y: number };
  visible: boolean;
}

export const RulerComponent: React.FC<RulerProps> = ({ position, visible }) => {
  if (!visible) return null;

  return (
    <div className="absolute bottom-4 left-4 bg-background/80 backdrop-blur-sm p-3 rounded-lg border shadow-lg z-40">
      <div className="flex items-center gap-2 text-xs text-muted-foreground">
        <Ruler className="w-4 h-4" />
        <div className="font-mono">
          <div>X: {Math.round(position.x)}</div>
          <div>Y: {Math.round(position.y)}</div>
        </div>
      </div>
    </div>
  );
};
