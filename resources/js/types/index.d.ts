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
    border_radius?: number;
    // Text annotation properties
    text_content?: string;
    text_size?: number;
    text_font?: string;
    text_bold?: boolean;
    text_italic?: boolean;
    text_unit?: string;
    // Line annotation properties
    line_start?: string | null;
    line_end?: string | null;
}
