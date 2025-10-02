import { InertiaLinkProps } from '@inertiajs/react';
import { LucideIcon } from 'lucide-react';

export interface Auth {
    user: User;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavGroup {
    title: string;
    items: NavItem[];
}

export interface NavItem {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
    icon?: LucideIcon | null;
    isActive?: boolean;
    subItems?: NavItem[];
}

export interface SharedData {
    name: string;
    quote?: { message: string; author: string };
    auth: Auth;
    sidebarOpen: boolean;
    [key: string]: unknown;
}

export interface User {
    id: number;
    name: string;
    email?: string;
    avatar?: string;
    email_verified_at?: string | null;
    created_at: string;
    updated_at: string;
    [key: string]: unknown; // This allows for additional properties...
}

export interface LabAnnotation {
    rotation: number;
    type: 'text' | 'rectangle' | 'ellipse' | 'line';
    border_color?: string;
    border_style?: string;
    color?: string;
    thickness?: number;
    x1: number;
    y1: number;
    z_index?: number;
    // Text annotation properties
    text_bold?: boolean;
    text_content?: string;
    text_font?: string;
    text_italic?: boolean;
    text_size?: number;
    text_unit?: string;
    // Shape annotation properties
    x2?: number;
    y2?: number;
    border_radius?: number;
    // Line annotation properties
    line_start?: string;
    line_end?: string;
    id: string;
}
