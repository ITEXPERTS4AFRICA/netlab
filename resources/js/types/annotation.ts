export interface LabAnnotation {
  id: string;
  type: 'text' | 'rectangle' | 'ellipse' | 'line';
  x1: number;
  y1: number;
  x2?: number;
  y2?: number;
  rotation?: number;
  z_index?: number;
  thickness?: number;
  color?: string;
  border_color?: string;
  border_style?: string;
  text_content?: string;
  text_size?: number;
  text_font?: string;
  text_bold?: boolean;
  text_italic?: boolean;
  text_unit?: string;
  border_radius?: number;
  line_start?: string | null;
  line_end?: string | null;
}

export interface CoordinateSystem {
  scale: number;
  offsetX: number;
  offsetY: number;
  zoom: number;
}

export interface ViewportState {
  coordinateSystem: CoordinateSystem;
  showGrid: boolean;
  showRuler: boolean;
  currentPosition: { x: number; y: number };
}
