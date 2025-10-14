import React, { useState, useEffect } from 'react';
import { Wifi, WifiOff, Network, Router, Server, Globe } from 'lucide-react';

interface SplashScreenProps {
    onLoadingComplete?: () => void;
}

export default function SplashScreen({ onLoadingComplete }: SplashScreenProps) {
    const [isOnline, setIsOnline] = useState(navigator.onLine);
    const [loadingProgress, setLoadingProgress] = useState(0);
    const [currentNode, setCurrentNode] = useState(0);

    const networkNodes = [
        { icon: Server, label: 'Initializing server...', color: 'text-blue-400' },
        { icon: Router, label: 'Establishing routes...', color: 'text-green-400' },
        { icon: Network, label: 'Building network...', color: 'text-purple-400' },
        { icon: Globe, label: 'Connecting globally...', color: 'text-yellow-400' },
    ];

    useEffect(() => {
        // Network status monitoring
        const handleOnline = () => setIsOnline(true);
        const handleOffline = () => setIsOnline(false);

        window.addEventListener('online', handleOnline);
        window.addEventListener('offline', handleOffline);

        // Network node animation
        const nodeInterval = setInterval(() => {
            setCurrentNode(prev => (prev + 1) % networkNodes.length);
        }, 800);

        // Simple loading progress
        const progressInterval = setInterval(() => {
            setLoadingProgress(prev => {
                if (prev >= 100) {
                    clearInterval(progressInterval);
                    setTimeout(() => {
                        onLoadingComplete?.();
                    }, 300);
                    return 100;
                }
                return prev + 2;
            });
        }, 50);

        return () => {
            window.removeEventListener('online', handleOnline);
            window.removeEventListener('offline', handleOffline);
            clearInterval(nodeInterval);
            clearInterval(progressInterval);
        };
    }, [onLoadingComplete, networkNodes.length]);

    const CurrentIcon = networkNodes[currentNode]?.icon || Server;

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-background">
            {/* Network Grid Background */}
            <div className="absolute inset-0 opacity-5">
                <svg width="100%" height="100%" className="absolute inset-0">
                    <defs>
                        <pattern id="network-grid" width="40" height="40" patternUnits="userSpaceOnUse">
                            <path d="M 40 0 L 0 0 0 40" fill="none" stroke="currentColor" strokeWidth="0.5"/>
                        </pattern>
                    </defs>
                    <rect width="100%" height="100%" fill="url(#network-grid)" />
                </svg>
            </div>

            {/* Connection Lines */}
            <div className="absolute inset-0 overflow-hidden opacity-10">
                <svg width="100%" height="100%" className="absolute inset-0">
                    <defs>
                        <line x1="20%" y1="20%" x2="80%" y2="20%" stroke="currentColor" strokeWidth="1" opacity="0.3">
                            <animate attributeName="opacity" values="0.1;0.4;0.1" dur="3s" repeatCount="indefinite"/>
                        </line>
                        <line x1="80%" y1="20%" x2="80%" y2="80%" stroke="currentColor" strokeWidth="1" opacity="0.3">
                            <animate attributeName="opacity" values="0.1;0.4;0.1" dur="3s" begin="0.5s" repeatCount="indefinite"/>
                        </line>
                        <line x1="80%" y1="80%" x2="20%" y2="80%" stroke="currentColor" strokeWidth="1" opacity="0.3">
                            <animate attributeName="opacity" values="0.1;0.4;0.1" dur="3s" begin="1s" repeatCount="indefinite"/>
                        </line>
                        <line x1="20%" y1="80%" x2="20%" y2="20%" stroke="currentColor" strokeWidth="1" opacity="0.3">
                            <animate attributeName="opacity" values="0.1;0.4;0.1" dur="3s" begin="1.5s" repeatCount="indefinite"/>
                        </line>
                    </defs>
                </svg>
            </div>

            <div className="relative z-10 text-center max-w-sm mx-auto px-6">
                {/* Network Status */}
                <div className="mb-8 flex justify-center">
                    <div className={`flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-medium border ${
                        isOnline
                            ? 'bg-green-50 text-green-700 border-green-200'
                            : 'bg-red-50 text-red-700 border-red-200'
                    }`}>
                        {isOnline ? (
                            <>
                                <Wifi className="w-4 h-4" />
                                <span>Connected</span>
                            </>
                        ) : (
                            <>
                                <WifiOff className="w-4 h-4" />
                                <span>Offline</span>
                            </>
                        )}
                    </div>
                </div>

                {/* Network Node Animation */}
                <div className="mb-8 flex justify-center">
                    <div className="relative">
                        <div className="w-20 h-20 rounded-full bg-gradient-to-br from-primary/10 to-primary/5 border border-primary/20 flex items-center justify-center">
                            <CurrentIcon className={`w-10 h-10 ${networkNodes[currentNode]?.color || 'text-primary'} transition-all duration-300`} />
                        </div>
                        {/* Pulsing rings */}
                        <div className="absolute inset-0 rounded-full border-2 border-primary/20 animate-ping"></div>
                        <div className="absolute inset-0 rounded-full border border-primary/10 animate-ping animation-delay-1000"></div>
                    </div>
                </div>

                {/* App Title */}
                <div className="mb-8">
                    <h1 className="text-2xl font-bold text-foreground mb-2 font-mono">
                        NetLab
                    </h1>
                    <p className="text-muted-foreground text-sm font-mono">
                        Network Laboratory
                    </p>
                </div>

                {/* Progress Bar */}
                <div className="mb-6">
                    <div className="w-full bg-border rounded-full h-1.5 mb-3">
                        <div
                            className="h-full bg-gradient-to-r from-primary to-primary/80 rounded-full transition-all duration-200 ease-out"
                            style={{ width: `${loadingProgress}%` }}
                        ></div>
                    </div>
                    <div className="flex justify-between items-center text-xs text-muted-foreground font-mono">
                        <span>{networkNodes[currentNode]?.label || 'Loading...'}</span>
                        <span>{loadingProgress}%</span>
                    </div>
                </div>

                {/* Network Stats */}
                <div className="grid grid-cols-2 gap-4 text-xs font-mono text-muted-foreground">
                    <div className="text-center p-2 bg-muted/30 rounded">
                        <div className="font-semibold text-primary">{isOnline ? 'Online' : 'Offline'}</div>
                        <div>Status</div>
                    </div>
                    <div className="text-center p-2 bg-muted/30 rounded">
                        <div className="font-semibold text-primary">{Math.round(loadingProgress)}%</div>
                        <div>Ready</div>
                    </div>
                </div>

                {/* Offline Warning */}
                {!isOnline && (
                    <div className="mt-6 p-3 bg-destructive/10 border border-destructive/20 rounded-lg">
                        <p className="text-destructive text-xs font-mono">
                            ⚠️ Limited connectivity detected
                        </p>
                    </div>
                )}
            </div>
        </div>
    );
}
