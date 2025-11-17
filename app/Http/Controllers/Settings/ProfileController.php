<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ProfileUpdateRequest;
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
        return Inertia::render('settings/profile', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => $request->session()->get('status'),
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
