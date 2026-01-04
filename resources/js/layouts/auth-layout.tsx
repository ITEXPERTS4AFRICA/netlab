import AuthLayoutTemplate from '@/layouts/auth/auth-card-layout';
import { Toaster } from 'sonner';


export default function AuthLayout({ children, title, description, ...props }: { children: React.ReactNode; title: string; description: string }) {
    return (
        <AuthLayoutTemplate title={title} description={description} {...props} >
            {children}
            <Toaster />
        </AuthLayoutTemplate>
    );
}
