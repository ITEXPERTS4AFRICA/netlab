<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;


class UserSettingsController extends Controller
{
    /**
     * Display the user's settings page.
     */
    public function index(): Response
    {
        /** @var User $user */
        $user = Auth::user();

        return Inertia::render('settings/user-settings', [
            'settings' => [
                'notification_enabled' => $user->notification_enabled,
                'notification_type' => $user->notification_type,
                'timezone' => $user->timezone,
                'language' => $user->language,
                'auto_start_labs' => $user->auto_start_labs,
                'notification_advance_minutes' => $user->notification_advance_minutes,
                'preferences' => $user->preferences ?? [],
            ],
            'available_timezones' => $this->getAvailableTimezones(),
            'available_languages' => $this->getAvailableLanguages(),
        ]);
    }

    /**
     * Update the user's settings.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'notification_enabled' => 'boolean',
            'notification_type' => 'in:email,browser,both',
            'timezone' => 'string|max:50',
            'language' => 'string|size:2',
            'auto_start_labs' => 'boolean',
            'notification_advance_minutes' => 'integer|min:5|max:60',
            'preferences' => 'array',
        ]);

        /** @var User $user */
        $user = Auth::user();

        foreach ($validated as $key => $value) {
            $user->$key = $value;
        }

        $user->save();


        return back()->with('success', 'Settings updated successfully!');
    }

    /**
     * Get available timezones for selection.
     */
    private function getAvailableTimezones(): array
    {
        return [
            'UTC' => 'UTC (Coordinated Universal Time)',
            'America/New_York' => 'Eastern Time (US & Canada)',
            'America/Chicago' => 'Central Time (US & Canada)',
            'America/Denver' => 'Mountain Time (US & Canada)',
            'America/Los_Angeles' => 'Pacific Time (US & Canada)',
            'Europe/London' => 'London',
            'Europe/Paris' => 'Paris',
            'Europe/Berlin' => 'Berlin',
            'Asia/Tokyo' => 'Tokyo',
            'Asia/Shanghai' => 'Shanghai',
            'Asia/Kolkata' => 'India Standard Time',
            'Australia/Sydney' => 'Sydney',
            'Africa/Cairo' => 'Cairo',
            'Africa/Lagos' => 'Lagos',
            'Africa/Abidjan' => 'Abidjan',
        ];
    }

    /**
     * Get available languages for selection.
     */
    private function getAvailableLanguages(): array
    {
        return [
            'en' => 'English',
            'fr' => 'Français',
            'es' => 'Español',
            'de' => 'Deutsch',
            'it' => 'Italiano',
            'pt' => 'Português',
            'ru' => 'Русский',
            'ja' => '日本語',
            'ko' => '한국어',
            'zh' => '中文',
            'ar' => 'العربية',
        ];
    }

    /**
     * Test notification settings.
     */
    public function testNotification(Request $request)
    {
        $user = Auth::user();

        // Here you would implement actual notification sending
        // For now, we'll just return a success message

        return back()->with('success', 'Test notification sent! Check your ' .
            ($user->notification_type === 'email' ? 'email' :
             ($user->notification_type === 'browser' ? 'browser notifications' : 'email and browser')));
    }
}
