<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Lab;
use App\Models\UsageRecord;
use App\Services\CiscoApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LabRuntimeController extends Controller
{
    public function start(Request $request, Lab $lab, CiscoApiService $cisco)
    {
        $token = session('cml_token');

        $startResp = $cisco->startLab($token, $lab->cml_id);

        if (is_array($startResp) && isset($startResp['error'])) {
            return response()->json(['error' => 'Failed to start lab', 'detail' => $startResp], 500);
        }

        $user = Auth::user();
        $usage = UsageRecord::create([
            'reservation_id' => null,
            'user_id' => $user->id,
            'lab_id' => $lab->id,
            'started_at' => now(),
        ]);

        return response()->json(['started' => true, 'usage_record' => $usage]);
    }

    public function stop(Request $request, Lab $lab, CiscoApiService $cisco)
    {
        $token = session('cml_token');
        $stopResp = $cisco->stopLab($token, $lab->cml_id);
        if (is_array($stopResp) && isset($stopResp['error'])) {
            return response()->json(['error' => 'Failed to stop lab', 'detail' => $stopResp], 500);
        }

        // find last usage record for this lab without end
        $usage = UsageRecord::where('lab_id', $lab->id)->whereNull('ended_at')->latest('started_at')->first();
        if (! $usage) {
            return response()->json(['error' => 'No running usage record found'], 404);
        }

        $usage->ended_at = now();
        $duration = $usage->ended_at->diffInSeconds($usage->started_at);
        $usage->duration_seconds = max(1, $duration);

        // cost calculation left to billing step
        $usage->save();

        return response()->json(['stopped' => true, 'usage_record' => $usage]);
    }
}


