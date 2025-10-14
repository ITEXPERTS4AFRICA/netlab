import AppLayoutTemplate from '@/layouts/app/app-sidebar-layout';
import NotificationCenter from '@/components/notification-center';
import { type BreadcrumbItem } from '@/types';
import { type ReactNode } from 'react';
import { Toaster } from 'sonner';

interface AppLayoutProps {
    children: ReactNode;
    breadcrumbs?: BreadcrumbItem[];
    classNmae?: string;
}

export default ({ children, breadcrumbs, ...props }: AppLayoutProps) => (
    <AppLayoutTemplate breadcrumbs={breadcrumbs} {...props}>
        {children}
        <Toaster  position='bottom-left'/>
    </AppLayoutTemplate>
);
