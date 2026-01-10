import { Button } from '@/components/ui/button';
import { type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { motion } from 'framer-motion';
import { SourceAppLogoIcon, } from "@/media";
import { dashboard, login, register, logout, home } from '@/routes';
import {useState} from "react";


const Header = () => {

    const { auth } = usePage<SharedData>().props;

    const [open, setOpen] = useState<boolean>(false);



    return (
        <motion.header
            initial={{ y: -20, opacity: 0 }}
            animate={{ y: 0, opacity: 1 }}
            transition={{ duration: 0.5 }}
            className={`glass fixed top-0 right-0 left-0 z-50 ${open ? 'h-auto' : 'clip-path-for-nab h-120'} border-b border-border/50 bg-background/30 backdrop-blur-md`}
        >
            <div className="container mx-auto px-6">
                <nav className="flex h-20 items-center justify-between">
                    {/* Logo */}
                    <Link className="flex items-center gap-4" href={auth.user ? dashboard() : home()}>
                        <img src={SourceAppLogoIcon} alt="NetLab Logo" className="h-8 w-8 object-contain" />
                        <h1 className="bold text-2xl" ><span className="bg-gradient-to-br from-accent to-ring bg-clip-text text-transparent">Net</span>-Lab</h1>
                    </Link>

                    {/* Button Bugger Mobile */}
                    <Button className="focus:outline-none md:hidden" onClick={() => setOpen(!open)}>
                        {/* icone simple */}
                        <svg className="h-6 w-6 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                strokeLinecap="round"
                                strokeLinejoin="round"
                                strokeWidth={2}
                                d={open ? 'M6 18L18 6M6 6l12 12' : 'M4 6h16M4 12h16m-7 6h7'}
                            />
                        </svg>
                    </Button>

                    {/* Mobile Menu */}
                    <div
                        className={`absolute top-20 right-0 left-0 transform border-b border-border/50 bg-background/80 backdrop-blur-md transition-transform duration-300 ease-in-out md:hidden ${open ? 'translate-y-0 opacity-100' : '-translate-y-10 opacity-0'} `}
                    >
                        <div className="container mx-auto px-6 py-4">
                            <div className="flex flex-col gap-4">
                                <a href="#features" className="text-sm font-medium text-foreground/80 hover:text-foreground">
                                    Fonctionnalités
                                </a>
                                <a href="#process" className="text-sm font-medium text-foreground/80 hover:text-foreground">
                                    À propos
                                </a>
                                <a href="#cta" className="text-sm font-medium text-foreground/80 hover:text-foreground">
                                    Documentation
                                </a>
                            </div>
                            {/* cta button  */}
                            <div className="mt-4 flex flex-col gap-3">
                                {auth.user ? (
                                    <div>
                                        <Button variant="default" size="sm" className="mb-2 w-full">
                                            <Link href={dashboard()}>Tableau de bord</Link>
                                        </Button>

                                        <Button variant="destructive" size="sm" className="w-full">
                                            <Link href={logout()}>Déconnexion</Link>
                                        </Button>
                                    </div>
                                ) : (
                                    <div>
                                        <Button variant="default" asChild size="sm" className="mb-2 w-full">
                                            <Link href={login()}>Connexion</Link>
                                        </Button>
                                        <Button variant="secondary" asChild size="sm" className="w-full">
                                            <Link href={register()}>S'inscrire</Link>
                                        </Button>
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>

                    {/* Navigation */}
                    <div className="hidden items-center gap-8 md:flex">
                        <a href="#features" className="group relative text-sm font-medium text-foreground/80 transition-colors hover:text-foreground">
                            Fonctionnalités
                            <span className="bg-amber absolute -bottom-1 left-0 h-0.5 w-0 transition-all group-hover:w-full" />
                        </a>
                        <a href="#process" className="group relative text-sm font-medium text-foreground/80 transition-colors hover:text-foreground">
                            À propos
                            <span className="bg-amber absolute -bottom-1 left-0 h-0.5 w-0 transition-all group-hover:w-full" />
                        </a>
                        <a href="#cta" className="group relative text-sm font-medium text-foreground/80 transition-colors hover:text-foreground">
                            Documentation
                            <span className="bg-amber absolute -bottom-1 left-0 h-0.5 w-0 transition-all group-hover:w-full" />
                        </a>
                    </div>

                    {/* CTA Buttons */}
                    {auth.user ? (
                        <div className="hidden items-center gap-3 md:flex">
                            <Button variant="default" size="sm">
                                <Link href={dashboard()}>Tableau de bord</Link>
                            </Button>

                            <Button variant="destructive" size="sm">
                                <Link href={logout()}>Déconnexion</Link>
                            </Button>
                        </div>
                    ) : (
                        <div className="hidden items-center gap-3 md:flex">
                            <Button variant="default" size="sm">
                                <Link href={login()}>Connexion</Link>
                            </Button>
                            <Button variant="secondary" size="sm">
                                <Link href={register()}>S'inscrire</Link>
                            </Button>
                        </div>
                    )}
                </nav>
            </div>
        </motion.header>
    );
};
export default Header;
