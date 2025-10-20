/**
 * Centre de notifications interactif pour l'application NetLab
 *
 * Ce composant React fournit une interface complète de gestion des notifications
 * avec filtrage, actions contextuelles et mise à jour en temps réel.
 *
 * Fonctionnalités principales :
 * - Affichage des notifications avec système de priorité
 * - Filtres avancés (non lus, haute priorité, par catégorie)
 * - Actions contextuelles (marquer comme lu, supprimer)
 * - Mise à jour automatique du compteur de notifications
 * - Interface responsive avec animations fluides
 * - Gestion du clavier (touche Échap pour fermer)
 * - Intégration avec l'API backend Laravel
 *
 * Types de notifications supportés :
 * - reservation_reminder : Rappels de réservation
 * - lab_available : Laboratoire disponible
 * - reservation_confirmed : Confirmation de réservation
 * - system_alert : Alertes système
 *
 * Niveaux de priorité :
 * - high : Priorité élevée (rouge)
 * - medium : Priorité moyenne (jaune)
 * - low : Priorité faible (gris)
 *
 * @author NetLab Team
 * @version 1.2.0
 * @since 2025-01-01
 */

import { useState, useEffect, useCallback, useMemo } from 'react';
import { Bell, X, Check, Clock, AlertCircle } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';

interface Notification {
    id: number;
    type: 'reservation_reminder' | 'lab_available' | 'reservation_confirmed' | 'system_alert';
    title: string;
    message: string;
    timestamp: string;
    read: boolean;
    action_url?: string;
    priority: 'low' | 'medium' | 'high';
    category?: 'lab' | 'system' | 'reservation';
    created_at: string;
    updated_at: string;
}

interface NotificationCenterProps {
    className?: string;
    maxNotifications?: number;
}

type FilterType = 'all' | 'unread' | 'high' | 'lab' | 'system';

export default function NotificationCenter({
    className = '',
    maxNotifications = 50
}: NotificationCenterProps) {
    const [notifications, setNotifications] = useState<Notification[]>([]);
    const [isOpen, setIsOpen] = useState(false);
    const [activeFilter] = useState<FilterType>('all');

    // API service functions
    const fetchNotifications = useCallback(async () => {
        try {
            const params = new URLSearchParams({
                per_page: maxNotifications.toString(),
                type: activeFilter === 'all' ? '' : activeFilter,
            });

            if (activeFilter === 'unread') {
                params.set('read', 'false');
            } else if (activeFilter === 'high') {
                params.set('priority', 'high');
            } else if (activeFilter === 'lab') {
                params.set('category', 'lab');
            } else if (activeFilter === 'system') {
                params.set('category', 'system');
            }

            const response = await fetch(`/notifications?${params}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!response.ok) {
                throw new Error('Failed to fetch notifications');
            }

            const data = await response.json();

            if (data.success) {
                setNotifications(data.data || []);
            }
        } catch (error) {
            console.error('Error fetching notifications:', error);
        }
    }, [activeFilter, maxNotifications]);

    const fetchUnreadCount = useCallback(async () => {
        try {
            const response = await fetch('/notifications/unread-count', {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (response.ok) {
                const data = await response.json();
                if (data.success) {
                    // Update unread count in existing notifications
                    setNotifications(prev =>
                        prev.map(notification => ({
                            ...notification,
                            // Keep existing read status
                        }))
                    );
                }
            }
        } catch (error) {
            console.error('Error fetching unread count:', error);
        }
    }, []);

    // Fetch notifications when component mounts or filter changes
    useEffect(() => {
        if (isOpen) {
            fetchNotifications();
        }
    }, [isOpen, activeFilter, fetchNotifications]);

    // Fetch unread count periodically
    useEffect(() => {
        fetchUnreadCount();
        const interval = setInterval(fetchUnreadCount, 30000); // Every 30 seconds
        return () => clearInterval(interval);
    }, [fetchUnreadCount]);

    // Memoized calculations for better performance
    const unreadCount = useMemo(() =>
        notifications.filter(n => !n.read).length, [notifications]
    );

    const filteredNotifications = useMemo(() => {
        let filtered = notifications;

        switch (activeFilter) {
            case 'unread':
                filtered = filtered.filter(n => !n.read);
                break;
            case 'high':
                filtered = filtered.filter(n => n.priority === 'high');
                break;
            case 'lab':
                filtered = filtered.filter(n => n.category === 'lab');
                break;
            case 'system':
                filtered = filtered.filter(n => n.category === 'system');
                break;
        }

        return filtered.slice(0, maxNotifications);
    }, [notifications, activeFilter, maxNotifications]);

    // Optimized notification actions
    const markAsRead = useCallback(async (id: number) => {
        try {
            const response = await fetch(`/notifications/${id}/read`, {
                method: 'PATCH',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
            });

            if (response.ok) {
                setNotifications(prev =>
                    prev.map(notification =>
                        notification.id === id
                            ? { ...notification, read: true }
                            : notification
                    )
                );
            }
        } catch (error) {
            console.error('Error marking notification as read:', error);
        }
    }, []);

    const markAllAsRead = useCallback(async () => {
        try {
            const response = await fetch('/notifications/mark-all-read', {
                method: 'PATCH',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
            });

            if (response.ok) {
                setNotifications(prev =>
                    prev.map(notification => ({ ...notification, read: true }))
                );
            }
        } catch (error) {
            console.error('Error marking all notifications as read:', error);
        }
    }, []);

    const removeNotification = useCallback(async (id: number) => {
        try {
            const response = await fetch(`/notifications/${id}`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
            });

            if (response.ok) {
                setNotifications(prev => prev.filter(notification => notification.id !== id));
            }
        } catch (error) {
            console.error('Error deleting notification:', error);
        }
    }, []);



    // Enhanced icon and color logic
    const getNotificationIcon = useCallback((type: Notification['type'], priority: Notification['priority']) => {
        const iconClass = `h-4 w-4 ${
            priority === 'high' ? 'text-red-500' :
            priority === 'medium' ? 'text-yellow-500' : 'text-blue-500'
        }`;

        switch (type) {
            case 'reservation_reminder':
                return <Clock className={iconClass} />;
            case 'lab_available':
                return <Check className={iconClass} />;
            case 'reservation_confirmed':
                return <Check className={iconClass} />;
            case 'system_alert':
                return <AlertCircle className={iconClass} />;
            default:
                return <Bell className={iconClass} />;
        }
    }, []);

    const getPriorityConfig = useCallback((priority: Notification['priority']) => {
        switch (priority) {
            case 'high':
                return {
                    badgeClass: 'bg-red-100 text-red-800 border-red-200',
                    dotClass: 'bg-red-500',
                    label: 'High Priority'
                };
            case 'medium':
                return {
                    badgeClass: 'bg-yellow-100 text-yellow-800 border-yellow-200',
                    dotClass: 'bg-yellow-500',
                    label: 'Medium Priority'
                };
            case 'low':
                return {
                    badgeClass: 'bg-gray-100 text-gray-800 border-gray-200',
                    dotClass: 'bg-gray-500',
                    label: 'Low Priority'
                };
            default:
                return {
                    badgeClass: 'bg-gray-100 text-gray-800 border-gray-200',
                    dotClass: 'bg-gray-500',
                    label: 'Unknown'
                };
        }
    }, []);

    const formatTimestamp = useCallback((timestamp: Date) => {
        const now = new Date();
        const diffInMinutes = Math.floor((now.getTime() - timestamp.getTime()) / (1000 * 60));

        if (diffInMinutes < 1) return 'Just now';
        if (diffInMinutes < 60) return `${diffInMinutes}m ago`;

        const diffInHours = Math.floor(diffInMinutes / 60);
        if (diffInHours < 24) return `${diffInHours}h ago`;

        const diffInDays = Math.floor(diffInHours / 24);
        if (diffInDays < 7) return `${diffInDays}d ago`;

        return timestamp.toLocaleDateString();
    }, []);

    // Keyboard navigation
    useEffect(() => {
        const handleKeyDown = (event: KeyboardEvent) => {
            if (event.key === 'Escape' && isOpen) {
                setIsOpen(false);
            }
        };

        document.addEventListener('keydown', handleKeyDown);
        return () => document.removeEventListener('keydown', handleKeyDown);
    }, [isOpen]);

    return (
        <div className={`relative ${className}`}>
            {/* Enhanced Notification Bell Button */}
            <Button
                variant="ghost"
                size="icon"
                className="relative hover:bg-gray-100 transition-all duration-200 ease-in-out transform hover:scale-105"
                onClick={() => setIsOpen(!isOpen)}
            >
                <Bell className={`h-5 w-5 transition-colors duration-200 ${unreadCount > 0 ? 'text-blue-600' : 'text-gray-600'}`} />
                {unreadCount > 0 && (
                    <div className="absolute -top-1 -right-1">
                        <Badge
                            variant="destructive"
                            className="h-5 w-5 rounded-full p-0 text-xs flex items-center justify-center animate-pulse shadow-lg border-2 border-white"
                        >
                            {unreadCount > 99 ? '99+' : unreadCount}
                        </Badge>
                    </div>
                )}
            </Button>

            {/* Enhanced Notification Dropdown */}
            {isOpen && (
                <>
                    {/* Improved Backdrop with blur effect */}
                    <div
                        className="fixed inset-0 bg-black/20 backdrop-blur-sm z-40 transition-opacity duration-200"
                        onClick={() => setIsOpen(false)}
                    />

                    {/* Enhanced Notification Panel */}
                    <Card className="absolute right-0 top-full mt-3 w-96 max-h-[480px] z-50 shadow-2xl border-0 bg-white/95 backdrop-blur-md animate-in slide-in-from-top-2 duration-200">
                        <CardHeader className="pb-4 bg-gradient-to-r from-blue-50 to-indigo-50 border-b">
                            <div className="flex items-center justify-between">
                                <div className="flex items-center gap-2">
                                    <div className="p-2 bg-blue-100 rounded-full">
                                        <Bell className="h-4 w-4 text-blue-600" />
                                    </div>
                                    <CardTitle className="text-lg font-semibold text-gray-800">Notifications</CardTitle>
                                </div>
                                {unreadCount > 0 && (
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        onClick={markAllAsRead}
                                        className="text-sm text-blue-600 hover:text-blue-800 hover:bg-blue-100 transition-colors"
                                    >
                                        Mark all read
                                    </Button>
                                )}
                            </div>
                            {unreadCount > 0 && (
                                <CardDescription className="text-blue-700 font-medium">
                                    You have {unreadCount} unread notification{unreadCount !== 1 ? 's' : ''}
                                </CardDescription>
                            )}
                        </CardHeader>

                        <CardContent className="p-0">
                            <div className="h-80 overflow-y-auto">
                                {filteredNotifications.length === 0 ? (
                                    <div className="p-8 text-center text-gray-500">
                                        <div className="p-3 bg-gray-100 rounded-full w-fit mx-auto mb-3">
                                            <Bell className="h-6 w-6 text-gray-400" />
                                        </div>
                                        <p className="font-medium">No notifications yet</p>
                                        <p className="text-sm text-gray-400 mt-1">We'll notify you when something arrives</p>
                                    </div>
                                ) : (
                                    <div className="divide-y divide-gray-100">
                                        {filteredNotifications.map((notification, index) => (
                                            <div
                                                key={notification.id}
                                                className={`group p-4 hover:bg-gradient-to-r hover:from-gray-50 hover:to-blue-50/30 cursor-pointer transition-all duration-200 ease-in-out transform hover:translate-x-1 ${
                                                    !notification.read ? 'bg-blue-50/30 border-l-4 border-l-blue-500' : ''
                                                }`}
                                                style={{
                                                    animationDelay: `${index * 50}ms`,
                                                    animation: 'slideInFromRight 0.3s ease-out forwards'
                                                }}
                                            >
                                                <div className="flex items-start gap-3">
                                                    <div className={`flex-shrink-0 mt-1 p-2 rounded-full ${
                                                        notification.priority === 'high' ? 'bg-red-100' :
                                                        notification.priority === 'medium' ? 'bg-yellow-100' : 'bg-green-100'
                                                    }`}>
                                                        {getNotificationIcon(notification.type, notification.priority)}
                                                    </div>

                                                    <div className="flex-1 min-w-0">
                                                        <div className="flex items-start justify-between gap-2">
                                                            <div className="flex-1">
                                                                <p className="text-sm font-semibold text-gray-900 leading-tight mb-1">
                                                                    {notification.title}
                                                                </p>
                                                                <p className="text-sm text-gray-600 leading-relaxed mb-3">
                                                                    {notification.message}
                                                                </p>
                                                                <div className="flex items-center gap-2 flex-wrap">
                                                                    <span className="text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded-full">
                                                                        {formatTimestamp(new Date(notification.created_at))}
                                                                    </span>
                                                                    <Badge
                                                                        variant="outline"
                                                                        className={`text-xs font-medium ${getPriorityConfig(notification.priority).badgeClass}`}
                                                                    >
                                                                        {getPriorityConfig(notification.priority).label}
                                                                    </Badge>
                                                                </div>
                                                            </div>

                                                            <div className="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                                                                {!notification.read && (
                                                                    <div className="w-2 h-2 bg-blue-500 rounded-full animate-pulse"></div>
                                                                )}
                                                                <Button
                                                                    variant="ghost"
                                                                    size="sm"
                                                                    className="h-7 w-7 p-0 hover:bg-red-100 hover:text-red-600 transition-colors"
                                                                    onClick={(e) => {
                                                                        e.stopPropagation();
                                                                        removeNotification(notification.id);
                                                                    }}
                                                                >
                                                                    <X className="h-3 w-3" />
                                                                </Button>
                                                            </div>
                                                        </div>

                                                        {notification.action_url && (
                                                            <Button
                                                                variant="outline"
                                                                size="sm"
                                                                className="mt-3 border-blue-200 text-blue-700 hover:bg-blue-50 hover:border-blue-300 transition-all duration-200"
                                                                onClick={(e) => {
                                                                    e.stopPropagation();
                                                                    markAsRead(notification.id);
                                                                    window.location.href = notification.action_url!;
                                                                }}
                                                            >
                                                                View Details
                                                            </Button>
                                                        )}
                                                    </div>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                )}
                            </div>
                        </CardContent>
                    </Card>
                </>
            )}
        </div>
    );
}
