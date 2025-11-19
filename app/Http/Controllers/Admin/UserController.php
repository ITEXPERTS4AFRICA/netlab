<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Reservation;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    /**
     * Afficher la liste des utilisateurs
     */
    public function index(Request $request): Response
    {
        $query = User::query();

        // Recherche
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('organization', 'like', "%{$search}%");
            });
        }

        // Filtre par rôle
        if ($request->has('role') && $request->role !== 'all') {
            $query->where('role', $request->role);
        }

        // Filtre par statut
        if ($request->has('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        $users = $query->withCount([
                'reservations',
                'reservations as active_reservations_count' => function ($q) {
                    $q->where('status', 'active')
                        ->where('end_at', '>', now());
                },
                'reservations as pending_reservations_count' => function ($q) {
                    $q->where('status', 'pending')
                        ->where('created_at', '>', now()->subMinutes(15));
                },
                'reservations as completed_reservations_count' => function ($q) {
                    $q->where('status', 'completed');
                },
                'payments as pending_payments_count' => function ($q) {
                    $q->where('status', 'pending');
                },
            ])
            ->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        $userIds = $users->getCollection()->pluck('id');

        $recentReservations = Reservation::with('lab:id,lab_title')
            ->whereIn('user_id', $userIds)
            ->orderByDesc('start_at')
            ->get()
            ->groupBy('user_id')
            ->map(function ($group) {
                return $group->take(3);
            });

        $nextReservations = Reservation::with('lab:id,lab_title')
            ->whereIn('user_id', $userIds)
            ->where('status', '!=', 'cancelled')
            ->where('start_at', '>', now())
            ->orderBy('start_at')
            ->get()
            ->groupBy('user_id')
            ->map(function ($group) {
                return $group->first();
            });

        $users->getCollection()->transform(function ($user) use ($recentReservations, $nextReservations) {
            $recent = $recentReservations->get($user->id, collect());
            $next = $nextReservations->get($user->id);

            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'is_active' => $user->is_active,
                'avatar' => $user->avatar,
                'organization' => $user->organization,
                'department' => $user->department,
                'total_reservations' => $user->reservations_count,
                'active_reservations_count' => $user->active_reservations_count,
                'pending_reservations_count' => $user->pending_reservations_count,
                'completed_reservations_count' => $user->completed_reservations_count,
                'pending_payments_count' => $user->pending_payments_count,
                'last_activity_at' => $user->last_activity_at?->toDateTimeString(),
                'created_at' => $user->created_at->toDateTimeString(),
                'recent_reservations' => $recent->map(function ($reservation) {
                    return [
                        'id' => $reservation->id,
                        'lab_title' => $reservation->lab->lab_title ?? 'Lab',
                        'status' => $reservation->status,
                        'start_at' => $reservation->start_at?->toIso8601String(),
                        'end_at' => $reservation->end_at?->toIso8601String(),
                        'estimated_cents' => $reservation->estimated_cents,
                    ];
                })->values(),
                'next_reservation' => $next ? [
                    'id' => $next->id,
                    'lab_title' => $next->lab->lab_title ?? 'Lab',
                    'start_at' => $next->start_at?->toIso8601String(),
                    'status' => $next->status,
                ] : null,
            ];
        });

        return Inertia::render('admin/users/index', [
            'users' => $users,
            'filters' => $request->only(['search', 'role', 'status']),
            'stats' => [
                'total' => User::count(),
                'active' => User::where('is_active', true)->count(),
                'admins' => User::where('role', 'admin')->count(),
                'instructors' => User::where('role', 'instructor')->count(),
                'students' => User::where('role', 'student')->count(),
            ],
        ]);
    }

    /**
     * Afficher le formulaire de création
     */
    public function create(): Response
    {
        return Inertia::render('admin/users/create');
    }

    /**
     * Créer un nouvel utilisateur
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'in:admin,instructor,student,user'],
            'is_active' => ['boolean'],
            'phone' => ['nullable', 'string', 'max:20'],
            'organization' => ['nullable', 'string', 'max:255'],
            'department' => ['nullable', 'string', 'max:255'],
            'position' => ['nullable', 'string', 'max:255'],
            'bio' => ['nullable', 'string', 'max:1000'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'is_active' => $validated['is_active'] ?? true,
            'phone' => $validated['phone'] ?? null,
            'organization' => $validated['organization'] ?? null,
            'department' => $validated['department'] ?? null,
            'position' => $validated['position'] ?? null,
            'bio' => $validated['bio'] ?? null,
        ]);

        return redirect()->route('admin.users.index')
            ->with('success', 'Utilisateur créé avec succès.');
    }

    /**
     * Afficher un utilisateur
     */
    public function show(User $user): Response
    {
        $user->loadCount('reservations');
        $user->load(['reservations' => function ($query) {
            $query->latest()->limit(10);
        }]);

        return Inertia::render('admin/users/show', [
            'user' => $user,
        ]);
    }

    /**
     * Afficher le formulaire d'édition
     */
    public function edit(User $user): Response
    {
        return Inertia::render('admin/users/edit', [
            'user' => $user,
        ]);
    }

    /**
     * Mettre à jour un utilisateur
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'in:admin,instructor,student,user'],
            'is_active' => ['boolean'],
            'phone' => ['nullable', 'string', 'max:20'],
            'organization' => ['nullable', 'string', 'max:255'],
            'department' => ['nullable', 'string', 'max:255'],
            'position' => ['nullable', 'string', 'max:255'],
            'bio' => ['nullable', 'string', 'max:1000'],
            'skills' => ['nullable', 'array'],
            'certifications' => ['nullable', 'array'],
            'education' => ['nullable', 'array'],
        ]);

        $user->fill($validated);

        if ($request->filled('password')) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return redirect()->route('admin.users.index')
            ->with('success', 'Utilisateur mis à jour avec succès.');
    }

    /**
     * Supprimer un utilisateur
     */
    public function destroy(User $user)
    {
        // Empêcher la suppression de son propre compte
        if ($user->id === auth()->id()) {
            return redirect()->route('admin.users.index')
                ->with('error', 'Vous ne pouvez pas supprimer votre propre compte.');
        }

        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'Utilisateur supprimé avec succès.');
    }

    /**
     * Activer/Désactiver un utilisateur
     */
    public function toggleStatus(User $user)
    {
        $user->is_active = !$user->is_active;
        $user->save();

        return redirect()->back()
            ->with('success', $user->is_active ? 'Utilisateur activé.' : 'Utilisateur désactivé.');
    }
}
