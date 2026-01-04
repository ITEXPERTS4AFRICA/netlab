import AppLogoIcon from './app-logo-icon';


export default function NavBarHome() {
    return (
        <nav className="blur-10  shadow-md">
            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div className="flex justify-between h-16">
                    <div className="flex">
                        <AppLogoIcon className="h-10 w-auto" />
                    </div>
                </div>
            </div>
        </nav>
    );
}
