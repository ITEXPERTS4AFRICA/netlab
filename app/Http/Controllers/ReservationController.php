<?php

namespace App\Http\Controllers;

use App\Models\Lab;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class ReservationController extends Controller
{
    use AuthorizesRequests;
    public function index(Request $request)
    {
        $user = Auth::user();
        $reservations = Reservation::with('lab','rate')
            ->when(! $request->query('all'), fn($q) => $q->where('user_id', $user->id))
            ->orderBy('start_at','desc')
            ->paginate(20);

        return response()->json($reservations);
    }

    public function store(Request $request)
    {
        $request->validate([
            'lab_id' => 'required|exists:labs,id',
            'start_at' => 'required|date|after:now',
            'end_at' => 'required|date|after:start_at',
            'rate_id' => 'nullable|exists:rates,id',
        ]);

        $user = Auth::user();
        $lab = \App\Models\Lab::findOrFail($request->lab_id);

        // check overlap
        $start = new \DateTime($request->start_at);
        $end = new \DateTime($request->end_at);

        $conflict = Reservation::where('lab_id', $lab->id)
            ->where('status', '!=', 'cancelled')
            ->where(function($q) use ($start,$end){
                $q->whereBetween('start_at', [$start->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s')])
                  ->orWhereBetween('end_at', [$start->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s')])
                  ->orWhere(function($q2) use ($start,$end){
                      $q2->where('start_at','<',$start->format('Y-m-d H:i:s'))->where('end_at','>',$end->format('Y-m-d H:i:s'));
                  });
            })->exists();

        if ($conflict) {
            return response()->json(['error' => 'Requested time slot conflicts with existing reservation'], 422);
        }

        $reservation = Reservation::create([
            'user_id' => $user->id,
            'lab_id' => $lab->id,
            'rate_id' => $request->rate_id,
            'start_at' => $request->start_at,
            'end_at' => $request->end_at,
            'status' => 'pending',
        ]);

        return response()->json($reservation, 201);
    }

    public function show(Reservation $reservation)
    {
        $this->authorize('view', $reservation);
        return response()->json($reservation->load('lab','rate','usageRecord'));
    }

    public function destroy(Reservation $reservation)
    {
        $this->authorize('delete', $reservation);
        $reservation->delete();
        return response()->json(null,204);
    }
}
