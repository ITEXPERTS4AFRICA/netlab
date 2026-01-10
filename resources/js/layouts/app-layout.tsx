import AppLayoutTemplate from '@/layouts/app/app-sidebar-layout';
import { type BreadcrumbItem } from '@/types';
import { type ReactNode, useEffect } from 'react';
import { Toaster, toast } from 'sonner';
import { usePage } from '@inertiajs/react';
import { CmlTokenRefresher } from '@/components/cml-token-refresher';
import { LucideScreenShareOff } from 'lucide-react';

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
        <section className="hidden md:block">
        <CmlTokenRefresher 
                showLoader={true} 
                autoRefresh={true}
                checkInterval={600000} // Vérifier toutes les 10 minutes (réduit pour éviter trop de requêtes)
        />
            {children}
                <Toaster position="bottom-left" />
        </section>
        <section className="md:hidden h-screen w-screen flex flex-col items-center justify-center p-6  backdrop-blur-md z-10 " >
            <LucideScreenShareOff className="mb-4 h-16 w-16 text-muted-foreground" />
            <h1 className="mb-2 text-2xl font-semibold text-center ">Optimisé pour les grands écrans</h1>
            <p className="text-center text-muted-foreground">Veuillez visiter notre site sur un appareil avec un écran plus grand pour une meilleure expérience.</p>
        </section>
    </AppLayoutTemplate>
);
}

