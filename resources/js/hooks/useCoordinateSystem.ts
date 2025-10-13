import { useState, useCallback } from 'react';
import { CoordinateSystem } from '@/types/annotation';

export const useCoordinateSystem = (initialScale: number = 0.002) => {
  const [coordinateSystem, setCoordinateSystem] = useState<CoordinateSystem>({
    scale: initialScale,
    offsetX: 0,
    offsetY: 0,
    zoom: 1,
  });

  const cmlToScreen = useCallback((cmlX: number, cmlY: number) => {
    const screenX = (cmlX * coordinateSystem.scale * coordinateSystem.zoom) + coordinateSystem.offsetX;
    const screenY = (cmlY * coordinateSystem.scale * coordinateSystem.zoom) + coordinateSystem.offsetY;
    return { x: screenX, y: screenY };
  }, [coordinateSystem]);

  const screenToCml = useCallback((screenX: number, screenY: number) => {
    const cmlX = (screenX - coordinateSystem.offsetX) / (coordinateSystem.scale * coordinateSystem.zoom);
    const cmlY = (screenY - coordinateSystem.offsetY) / (coordinateSystem.scale * coordinateSystem.zoom);
    return { x: cmlX, y: cmlY };
  }, [coordinateSystem]);

  const zoomIn = useCallback(() => {
    setCoordinateSystem(prev => ({
      ...prev,
      zoom: Math.min(prev.zoom * 1.2, 5)
    }));
  }, []);

  const zoomOut = useCallback(() => {
    setCoordinateSystem(prev => ({
      ...prev,
      zoom: Math.max(prev.zoom / 1.2, 0.1)
    }));
  }, []);

  const resetView = useCallback((containerWidth: number, containerHeight: number) => {
    setCoordinateSystem({
      scale: initialScale,
      offsetX: containerWidth / 2,
      offsetY: containerHeight / 2,
      zoom: 1,
    });
  }, [initialScale]);

  const pan = useCallback((deltaX: number, deltaY: number) => {
    setCoordinateSystem(prev => ({
      ...prev,
      offsetX: prev.offsetX + deltaX,
      offsetY: prev.offsetY + deltaY,
    }));
  }, []);

  return {
    coordinateSystem,
    setCoordinateSystem,
    cmlToScreen,
    screenToCml,
    zoomIn,
    zoomOut,
    resetView,
    pan,
  };
};
