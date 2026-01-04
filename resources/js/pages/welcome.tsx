
import { dashboard, login, register } from '@/routes';
import { type SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import NavBarHome from '@/components/app-nav-bar-home';
import NetworkBackground from '@/components/app-network-background';

export default function Welcome() {
    const { auth } = usePage<SharedData>().props;

    return (
        <>
            <Head title="NetLab - Laboratoire Virtuel Cisco">
                <link rel="preconnect" href="https://fonts.bunny.net" />
                <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
                <link rel="shortcut icon" href="/favicon.ico" type="image/x-icon" />
            </Head> 
            <NavBarHome/>
            <NetworkBackground className='fixed'/>
        </>
    );
}
