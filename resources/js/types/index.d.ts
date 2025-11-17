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

export interface Lab  {
    /** Identifiant unique du laboratoire */
    id: string;
    /** État actuel du lab (DEFINED_ON_CORE, STOPPED, etc.) */
    state: string;
    /** Titre/nom du laboratoire */
    lab_title: string;
    /** Nombre de nœuds/équipements réseau */
    node_count: string|number;
    /** Description détaillée du laboratoire */
    lab_description: string;
    /** Date de création du lab (format ISO) */
    created: string;
    /** Date de dernière modification (format ISO) */
    modified: string;
};


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

export interface Pagination {
    /** Page actuelle */
    page: number;
    /** Nombre d'éléments par page */
    per_page: number;
    /** Nombre total d'éléments */
    total: number;
    /** Nombre total de pages */
    total_pages: number;
}
