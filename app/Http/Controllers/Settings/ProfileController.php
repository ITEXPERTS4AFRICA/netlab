<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ProfileUpdateRequest;
use App\Models\Payment;
use App\Models\Reservation;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Show the user's profile settings page.
     */
    public function edit(Request $request): Response
    {
        $user = $request->user();

        $activeReservations = Reservation::with('lab:id,lab_title')
            ->where('user_id', $user->id)
            ->where('status', '!=', 'cancelled')
            ->where('end_at', '>', now())
            ->orderBy('start_at')
            ->get()
            ->map(function (Reservation $reservation) {
                return [
                    'id' => $reservation->id,
                    'lab_title' => $reservation->lab->lab_title ?? 'Lab',
                    'status' => $reservation->status,
                    'start_at' => $reservation->start_at?->toIso8601String(),
                    'end_at' => $reservation->end_at?->toIso8601String(),
                    'estimated_cents' => $reservation->estimated_cents,
                ];
            });

        $recentReservations = Reservation::with('lab:id,lab_title')
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(function (Reservation $reservation) {
                return [
                    'id' => $reservation->id,
                    'lab_title' => $reservation->lab->lab_title ?? 'Lab',
                    'status' => $reservation->status,
                    'start_at' => $reservation->start_at?->toIso8601String(),
                    'end_at' => $reservation->end_at?->toIso8601String(),
                    'estimated_cents' => $reservation->estimated_cents,
                    'created_at' => $reservation->created_at?->toIso8601String(),
                ];
            });

        $recentPayments = Payment::with('reservation.lab:id,lab_title')
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(function (Payment $payment) {
                return [
                    'id' => $payment->id,
                    'status' => $payment->status,
                    'amount' => $payment->amount,
                    'currency' => $payment->currency,
                    'created_at' => $payment->created_at?->toIso8601String(),
                    'reservation' => $payment->reservation ? [
                        'id' => $payment->reservation->id,
                        'lab_title' => $payment->reservation->lab->lab_title ?? 'Lab',
                    ] : null,
                ];
            });

        $completedSeconds = Reservation::where('user_id', $user->id)
            ->where('status', 'completed')
            ->get(['start_at', 'end_at'])
            ->reduce(function ($carry, $reservation) {
                if ($reservation->start_at && $reservation->end_at) {
                    return $carry + max(0, $reservation->end_at->diffInSeconds($reservation->start_at));
                }
                return $carry;
            }, 0);

        $profileDashboard = [
            'stats' => [
                'total_reservations' => Reservation::where('user_id', $user->id)->count(),
                'active_reservations' => $activeReservations->count(),
                'pending_payments' => Payment::where('user_id', $user->id)->where('status', 'pending')->count(),
                'completed_hours' => round($completedSeconds / 3600, 1),
                'last_activity_at' => $user->last_activity_at?->toIso8601String(),
            ],
            'active_reservations' => $activeReservations,
            'recent_reservations' => $recentReservations,
            'recent_payments' => $recentPayments,
        ];

        return Inertia::render('settings/profile', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => $request->session()->get('status'),
            'profileDashboard' => $profileDashboard,
        ]);
    }

    /**
     * Update the user's profile settings.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        
        // Convertir les compétences et certifications en array si ce sont des strings
        if (isset($validated['skills']) && is_string($validated['skills'])) {
            $validated['skills'] = array_filter(array_map('trim', explode(',', $validated['skills'])));
        }
        
        if (isset($validated['certifications']) && is_string($validated['certifications'])) {
            $validated['certifications'] = array_filter(array_map('trim', explode(',', $validated['certifications'])));
        }
        
        // Si education est une string JSON, la décoder
        if (isset($validated['education']) && is_string($validated['education'])) {
            $decoded = json_decode($validated['education'], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $validated['education'] = $decoded;
            }
        }
        
        $request->user()->fill($validated);

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return to_route('profile.edit');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
