<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lab;
use Illuminate\Http\Request;

class LabController extends Controller
{
    public function index(Request $request)
    {
        $query = Lab::query();
        $perPage = max(1, (int) $request->query('per_page', 20));
        $labs = $query->orderBy('name')->paginate($perPage);
        return response()->json($labs);
    }

    public function show(Lab $lab)
    {
        return response()->json($lab);
    }
}


