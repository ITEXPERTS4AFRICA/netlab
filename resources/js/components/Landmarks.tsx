import React from 'react';
import { CoordinateSystem } from '@/types/annotation';

interface LandmarksProps {
  coordinateSystem: CoordinateSystem;
}

interface Landmark {
  x: number;
  y: number;
  label: string;
  color: string;
}

export const Landmarks: React.FC<LandmarksProps> = ({ coordinateSystem }) => {
  const landmarks: Landmark[] = [
    { x: 0, y: 0, label: "Centre", color: "#ef4444" },
    { x: 10000, y: 10000, label: "NE", color: "#3b82f6" },
    { x: -10000, y: -10000, label: "SW", color: "#10b981" },
    { x: 10000, y: -10000, label: "SE", color: "#f59e0b" },
    { x: -10000, y: 10000, label: "NW", color: "#8b5cf6" }
  ];

  return (
    <>
      {landmarks.map((landmark, index) => {
        const screenX = (landmark.x * coordinateSystem.scale * coordinateSystem.zoom) + coordinateSystem.offsetX;
        const screenY = (landmark.y * coordinateSystem.scale * coordinateSystem.zoom) + coordinateSystem.offsetY;

        return (
          <div
            key={`landmark-${index}`}
            className="absolute w-3 h-3 rounded-full border-2 border-white shadow-lg pointer-events-none z-10"
            style={{
              left: `${screenX - 6}px`,
              top: `${screenY - 6}px`,
              backgroundColor: landmark.color,
            }}
            title={`${landmark.label} (${landmark.x}, ${landmark.y})`}
          >
            <div className="absolute -top-6 left-1/2 transform -translate-x-1/2 text-xs font-mono bg-black/80 text-white px-1 rounded whitespace-nowrap">
              {landmark.label}
            </div>
          </div>
        );
      })}
    </>
  );
};
