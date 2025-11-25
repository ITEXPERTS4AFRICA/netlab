import AppLayoutTemplate from '@/layouts/app/app-sidebar-layout';
import { type BreadcrumbItem } from '@/types';
import { type ReactNode } from 'react';
import { Toaster } from 'sonner';
import { CmlTokenRefresher } from '@/components/cml-token-refresher';
import { FeedbackManager } from '@/components/FeedbackManager';

interface AppLayoutProps {
    children: ReactNode;
    breadcrumbs?: BreadcrumbItem[];
    classNmae?: string;
}

export default function AppLayout({ children, breadcrumbs, ...props }: AppLayoutProps) {
    return (
        <FeedbackManager>
            <AppLayoutTemplate breadcrumbs={breadcrumbs} {...props}>
                <CmlTokenRefresher 
                    showLoader={true} 
                    autoRefresh={true}
                    checkInterval={600000} // Vérifier toutes les 10 minutes (réduit pour éviter trop de requêtes)
                />
                {children}
                <Toaster position="bottom-left" />
            </AppLayoutTemplate>
        </FeedbackManager>
    );
}

