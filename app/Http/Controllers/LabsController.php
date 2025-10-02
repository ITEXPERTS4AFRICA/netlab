<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Services\CiscoApiService;
use Illuminate\Support\Facades\Cache;
use App\Models\Lab;

class LabsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, CiscoApiService $cisco)
    {
        $labs_ids = Cache::has('labs_ids') ? Cache::get('labs_ids') : $cisco->getLabs(session('cml_token'));

        if (!Cache::has('labs_ids')) {
            Cache::put('labs_ids', $labs_ids);
        }

        $page = max(1, (int) $request->query('page', 1));
        $perPage = max(1, (int) $request->query('per_page', 10));
        $total = count($labs_ids);
        $totalPages = ceil($total / $perPage);

        $currentLabIds = array_slice($labs_ids, ($page - 1) * $perPage, $perPage);

        $labs = collect($currentLabIds)->map(function ($id) use ($cisco) {
            return $cisco->getlab(session('cml_token'), $id);
        })->toArray();

        foreach($labs as $key => $lab) {
            $Lab = Lab::where('cml_id', $lab['id'])->first();
            if(!$Lab) {
                Lab::create([
                    'cml_id' => $lab['id'],
                    'created' => $lab['created'],
                    'modified' => $lab['modified'],
                    'lab_description' => $lab['lab_description'],
                    'node_count' => $lab['node_count'],
                    'state' => $lab['state'],
                    'lab_title' => $lab['lab_title'],
                    'owner' => $lab['owner'],
                    'link_count' => $lab['link_count'],
                    'effective_permissions' => $lab['effective_permissions']
                ]);
            }
        }


        return Inertia::render('labs/Labs', [
            'labs' => $labs,
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => $totalPages,
            ],
        ]);
    }


    public function domain(Request $request, $token,)
    {
        return redirect()->away('https://54.38.146.213/lab/' . $request->lab_id);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
