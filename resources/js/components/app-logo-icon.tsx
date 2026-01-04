import { cn } from '@/lib/utils';
import {  SourceAppLogoIcon  }  from '@/media';


interface AppLogoIconProps {
    className?: string;
}

const baseCssClasses = 'w-full h-full object-contain ';

export default function AppLogoIcon({ className, ...props }: AppLogoIconProps) {
    return <img src={SourceAppLogoIcon} alt="Logo" className={cn(baseCssClasses, className)} {...props} />;
}
