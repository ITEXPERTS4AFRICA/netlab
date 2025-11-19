import AppLayoutTemplate from '@/layouts/app/app-sidebar-layout';
import { type BreadcrumbItem } from '@/types';
import { type ReactNode, useEffect } from 'react';
import { Toaster, toast } from 'sonner';
import { usePage } from '@inertiajs/react';
import { CmlTokenRefresher } from '@/components/cml-token-refresher';

interface AppLayoutProps {
    children: ReactNode;
    breadcrumbs?: BreadcrumbItem[];
    classNmae?: string;
}

type FlashProps = {
    flash?: {
        success?: string | null;
        error?: string | null;
    };
};

export default function AppLayout({ children, breadcrumbs, ...props }: AppLayoutProps) {
    const page = usePage<FlashProps>();
    const success = page.props.flash?.success;
    const error = page.props.flash?.error;

    useEffect(() => {
        if (success) {
            toast.success(success);
        }
    }, [success]);

    useEffect(() => {
        if (error) {
            toast.error(error);
        }
    }, [error]);

    return (
    <AppLayoutTemplate breadcrumbs={breadcrumbs} {...props}>
        <CmlTokenRefresher 
            showLoader={true} 
            autoRefresh={true}
            checkInterval={600000} // Vérifier toutes les 10 minutes (réduit pour éviter trop de requêtes)
        />
        {children}
            <Toaster position="bottom-left" />
    </AppLayoutTemplate>
);
}

