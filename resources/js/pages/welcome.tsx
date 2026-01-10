import { Head } from '@inertiajs/react';
import NetworkBackground from '@/components/app-network-background';
import Header from '@/components/landing/Header';
import HeroSection from '@/components/landing/HeroSection';
import FeaturesSection from '@/components/landing/FeaturesSection';
import ProcessSection from '@/components/landing/ProcessSection';
import CTASection from '@/components/landing/CTASection';
import Footer from '@/components/landing/Footer';


export default function Welcome() {
    // const { auth } = usePage<SharedData>().props;

    return (
        <>
            <Head title="NetLab - Laboratoire Virtuel ">
                <link rel="preconnect" href="https://fonts.bunny.net" />
                <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

            </Head>
        <Header />
           
        <main>
            <HeroSection />
            <FeaturesSection />
            <ProcessSection />
            <CTASection />
        </main>
        <Footer />
        <NetworkBackground className="fixed -z-0"/>

        </>

    );
}
