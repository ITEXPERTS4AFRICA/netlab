import { Head, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { type BreadcrumbItem } from '@/types';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { Bell, Clock, Globe, Zap, CheckCircle2, Settings as SettingsIcon } from 'lucide-react';

interface UserSettingsProps {
    settings: {
        notification_enabled: boolean;
        notification_type: string;
        timezone: string;
        language: string;
        auto_start_labs: boolean;
        notification_advance_minutes: number;
        preferences: Record<string, string | number | boolean>;
    };
    available_timezones: Record<string, string>;
    available_languages: Record<string, string>;
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'User Settings',
        href: '/settings/user-settings',
    },
];

export default function UserSettings({ settings, available_timezones, available_languages }: UserSettingsProps) {
    const { data, setData, post, processing, errors, recentlySuccessful } = useForm({
        notification_enabled: settings.notification_enabled,
        notification_type: settings.notification_type,
        timezone: settings.timezone,
        language: settings.language,
        auto_start_labs: settings.auto_start_labs,
        notification_advance_minutes: settings.notification_advance_minutes,
        preferences: settings.preferences,
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/settings/user-settings');
    };

    const handleTestNotification = () => {
        post('/settings/test-notification');
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="User Settings" />

            <SettingsLayout>
                <div className="space-y-6">
                    {/* Header */}
                    <div className="flex items-center gap-3">
                        <div className="p-2 bg-blue-100 rounded-lg dark:bg-blue-900">
                            <SettingsIcon className="h-6 w-6 text-blue-600 dark:text-blue-400" />
                        </div>
                        <div>
                            <h1 className="text-2xl font-bold">User Settings</h1>
                            <p className="text-muted-foreground">Manage your account preferences and notification settings</p>
                        </div>
                    </div>

                    {/* Success Message */}
                    {recentlySuccessful && (
                        <Alert className="border-green-200 bg-green-50 dark:bg-green-950">
                            <CheckCircle2 className="h-4 w-4 text-green-600" />
                            <AlertDescription className="text-green-800 dark:text-green-200">
                                Settings updated successfully!
                            </AlertDescription>
                        </Alert>
                    )}

                    <form onSubmit={handleSubmit} className="space-y-6">
                        {/* Notification Settings */}
                        <Card>
                            <CardHeader>
                                <div className="flex items-center gap-2">
                                    <Bell className="h-5 w-5 text-blue-600" />
                                    <CardTitle>Notification Settings</CardTitle>
                                </div>
                                <CardDescription>
                                    Configure how and when you want to receive notifications about your lab reservations
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-6">
                                <div className="flex items-center justify-between p-4 border rounded-lg bg-gray-50 dark:bg-gray-900">
                                    <div className="space-y-1">
                                        <Label htmlFor="notification_enabled" className="text-base font-medium">
                                            Enable Notifications
                                        </Label>
                                        <div className="text-sm text-muted-foreground">
                                            Receive notifications about lab availability and reservations
                                        </div>
                                    </div>
                                    <Checkbox
                                        id="notification_enabled"
                                        checked={data.notification_enabled}
                                        onCheckedChange={(checked: boolean) => setData('notification_enabled', checked)}
                                        className="h-5 w-5"
                                    />
                                </div>

                                {data.notification_enabled && (
                                    <div className="space-y-4 pl-4 border-l-2 border-blue-200 dark:border-blue-800">
                                        <div className="space-y-2">
                                            <Label htmlFor="notification_type" className="text-sm font-medium">
                                                Notification Type
                                            </Label>
                                            <Select
                                                value={data.notification_type}
                                                onValueChange={(value) => setData('notification_type', value)}
                                            >
                                                <SelectTrigger className="w-full">
                                                    <SelectValue placeholder="Select notification type" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="email">
                                                        <div className="flex items-center gap-2">
                                                            <span>📧</span>
                                                            <span>Email Only</span>
                                                        </div>
                                                    </SelectItem>
                                                    <SelectItem value="browser">
                                                        <div className="flex items-center gap-2">
                                                            <span>🔔</span>
                                                            <span>Browser Only</span>
                                                        </div>
                                                    </SelectItem>
                                                    <SelectItem value="both">
                                                        <div className="flex items-center gap-2">
                                                            <span>📧🔔</span>
                                                            <span>Email & Browser</span>
                                                        </div>
                                                    </SelectItem>
                                                </SelectContent>
                                            </Select>
                                            {errors.notification_type && (
                                                <div className="text-sm text-red-600">{errors.notification_type}</div>
                                            )}
                                        </div>

                                        <div className="space-y-2">
                                            <Label htmlFor="notification_advance_minutes" className="text-sm font-medium">
                                                Advance Notice (minutes)
                                            </Label>
                                            <Input
                                                id="notification_advance_minutes"
                                                type="number"
                                                min="5"
                                                max="60"
                                                value={data.notification_advance_minutes}
                                                onChange={(e) => setData('notification_advance_minutes', parseInt(e.target.value) || 15)}
                                                className="w-full"
                                            />
                                            <div className="text-sm text-muted-foreground">
                                                How many minutes before your reservation to send notifications (5-60 minutes)
                                            </div>
                                            {errors.notification_advance_minutes && (
                                                <div className="text-sm text-red-600">{errors.notification_advance_minutes}</div>
                                            )}
                                        </div>

                                        <div className="pt-2">
                                            <Button type="button" variant="outline" onClick={handleTestNotification} className="w-full sm:w-auto">
                                                <Bell className="h-4 w-4 mr-2" />
                                                Test Notification
                                            </Button>
                                        </div>
                                    </div>
                                )}
                            </CardContent>
                        </Card>

                        {/* Lab Settings */}
                        <Card>
                            <CardHeader>
                                <div className="flex items-center gap-2">
                                    <Zap className="h-5 w-5 text-green-600" />
                                    <CardTitle>Lab Settings</CardTitle>
                                </div>
                                <CardDescription>
                                    Configure your lab usage preferences and automation options
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-6">
                                <div className="flex items-center justify-between p-4 border rounded-lg bg-gray-50 dark:bg-gray-900">
                                    <div className="space-y-1">
                                        <Label htmlFor="auto_start_labs" className="text-base font-medium">
                                            Auto-start Labs
                                        </Label>
                                        <div className="text-sm text-muted-foreground">
                                            Automatically start labs when your reservation begins
                                        </div>
                                    </div>
                                    <Checkbox
                                        id="auto_start_labs"
                                        checked={data.auto_start_labs}
                                        onCheckedChange={(checked: boolean) => setData('auto_start_labs', checked)}
                                        className="h-5 w-5"
                                    />
                                </div>
                            </CardContent>
                        </Card>

                        {/* Localization Settings */}
                        <Card>
                            <CardHeader>
                                <div className="flex items-center gap-2">
                                    <Globe className="h-5 w-5 text-purple-600" />
                                    <CardTitle>Localization</CardTitle>
                                </div>
                                <CardDescription>
                                    Set your preferred language and timezone for a personalized experience
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-6">
                                <div className="space-y-2">
                                    <Label htmlFor="timezone" className="text-sm font-medium">
                                        Timezone
                                    </Label>
                                    <Select
                                        value={data.timezone}
                                        onValueChange={(value) => setData('timezone', value)}
                                    >
                                        <SelectTrigger className="w-full">
                                            <SelectValue placeholder="Select your timezone" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {Object.entries(available_timezones).map(([key, label]) => (
                                                <SelectItem key={key} value={key}>
                                                    <div className="flex items-center gap-2">
                                                        <Clock className="h-4 w-4" />
                                                        <span>{label}</span>
                                                    </div>
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    {errors.timezone && (
                                        <div className="text-sm text-red-600">{errors.timezone}</div>
                                    )}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="language" className="text-sm font-medium">
                                        Language
                                    </Label>
                                    <Select
                                        value={data.language}
                                        onValueChange={(value) => setData('language', value)}
                                    >
                                        <SelectTrigger className="w-full">
                                            <SelectValue placeholder="Select your language" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {Object.entries(available_languages).map(([key, label]) => (
                                                <SelectItem key={key} value={key}>
                                                    <span>{label}</span>
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    {errors.language && (
                                        <div className="text-sm text-red-600">{errors.language}</div>
                                    )}
                                </div>
                            </CardContent>
                        </Card>

                        {/* Action Buttons */}
                        <div className="flex flex-col sm:flex-row gap-3 pt-6 border-t">
                            <Button type="submit" disabled={processing} className="flex-1 sm:flex-none">
                                {processing ? (
                                    <>
                                        <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></div>
                                        Saving...
                                    </>
                                ) : (
                                    <>
                                        <CheckCircle2 className="h-4 w-4 mr-2" />
                                        Save Settings
                                    </>
                                )}
                            </Button>
                            <Button type="button" variant="outline" onClick={() => window.history.back()}>
                                Cancel
                            </Button>
                        </div>
                    </form>
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
