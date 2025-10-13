import React from 'react';
import { CoordinateSystem } from '@/types/annotation';

interface GridProps {
  coordinateSystem: CoordinateSystem;
  visible: boolean;
}

export const Grid: React.FC<GridProps> = ({ coordinateSystem, visible }) => {
  if (!visible) return null;

  const gridSize = 5000; // Taille de grille en unités CML
  const gridLines = [];

  // Grille principale
  for (let x = -30000; x <= 30000; x += gridSize) {
    const screenX = (x * coordinateSystem.scale * coordinateSystem.zoom) + coordinateSystem.offsetX;
    gridLines.push(
      <div
        key={`grid-x-${x}`}
        className="absolute border-l border-gray-300/50 pointer-events-none"
        style={{
          left: `${screenX}px`,
          top: 0,
          bottom: 0,
          width: '1px',
        }}
      />
    );
  }

  for (let y = -30000; y <= 30000; y += gridSize) {
    const screenY = (y * coordinateSystem.scale * coordinateSystem.zoom) + coordinateSystem.offsetY;
    gridLines.push(
      <div
        key={`grid-y-${y}`}
        className="absolute border-t border-gray-300/50 pointer-events-none"
        style={{
          top: `${screenY}px`,
          left: 0,
          right: 0,
          height: '1px',
        }}
      />
    );
  }

  return <>{gridLines}</>;
};
