<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\CiscoApiService;
use Illuminate\Support\Facades\Log;

/**
 * Controller for handling smart lab annotations via API
 *
 * This controller provides JSON API endpoints for CML lab smart annotations
 * which are based on node tags and provide intelligent annotation features.
 * Frontend components should use these endpoints for AJAX requests.
 */
class SmartAnnotationsController extends Controller
{


    /**
     * Display a listing of smart annotations for a specific lab.
     * Returns JSON response for AJAX requests from frontend components.
     */
    public function index($lab_id, CiscoApiService $smartAnnotationService)
    {
        $token = session('cml_token');
        if (!$token) {
            return response()->json(['error' => 'Authentication required'], 401);
        }

        $smartAnnotations = $smartAnnotationService->getSmartAnnotations($token, $lab_id);

        if (isset($smartAnnotations['error'])) {
            Log::warning('Failed to fetch smart annotations via API', [
                'lab_id' => $lab_id,
                'error' => $smartAnnotations['error'],
                'status' => $smartAnnotations['status'] ?? 'unknown',
                'body' => $smartAnnotations['body'] ?? null
            ]);

            // If it's a 404, it might mean smart annotations are not supported
            if (isset($smartAnnotations['status']) && $smartAnnotations['status'] === 404) {
                return response()->json([
                    'error' => 'Smart annotations not available for this lab or CML instance',
                    'details' => $smartAnnotations['body'] ?? 'No additional details available'
                ], 404);
            }

            return response()->json($smartAnnotations, 500);
        }

        return response()->json($smartAnnotations, 200);
    }

    /**
     * Get a specific smart annotation by ID.
     * Returns JSON response for AJAX requests.
     */
    public function show($lab_id, $smart_annotation_id, CiscoApiService $smartAnnotationService)
    {
        $token = session('cml_token');
        if (!$token) {
            return response()->json(['error' => 'Authentication required'], 401);
        }

        $smartAnnotation = $smartAnnotationService->getSmartAnnotation($token, $lab_id, $smart_annotation_id);

        if (isset($smartAnnotation['error'])) {
            Log::warning('Failed to fetch smart annotation for show via API', [
                'lab_id' => $lab_id,
                'smart_annotation_id' => $smart_annotation_id,
                'error' => $smartAnnotation['error']
            ]);
            return response()->json($smartAnnotation, 500);
        }

        return response()->json($smartAnnotation, 200);
    }

    /**
     * Update the specified smart annotation in storage.
     * Returns JSON response for AJAX requests.
     */
    public function update(Request $request, $lab_id, $smart_annotation_id, CiscoApiService $smartAnnotationService)
    {
        // Validate lab_id and smart annotation id
        $request->merge(['lab_id' => $lab_id, 'smart_annotation_id' => $smart_annotation_id]);
        $request->validate([
            'lab_id' => 'required|regex:/^[\da-f]{8}-[\da-f]{4}-4[\da-f]{3}-[89ab][\da-f]{3}-[\da-f]{12}(?!\n)$/',
            'smart_annotation_id' => 'required|string',
        ]);

        $token = session('cml_token');
        if (!$token) {
            return response()->json(['error' => 'Authentication required'], 401);
        }

        $data = $request->only([
            'type', 'rotation', 'border_color', 'border_style', 'color',
            'thickness', 'x1', 'y1', 'x2', 'y2', 'z_index',
            'text_content', 'text_bold', 'text_font', 'text_italic',
            'text_size', 'text_unit', 'line_start', 'line_end',
            'border_radius', 'node_tags', 'auto_position', 'smart_content'
        ]);

        $result = $smartAnnotationService->updateSmartAnnotation($token, $lab_id, $smart_annotation_id, $data);

        if (isset($result['error'])) {
            Log::warning('Failed to update smart annotation via API', [
                'lab_id' => $lab_id,
                'smart_annotation_id' => $smart_annotation_id,
                'error' => $result['error'],
                'status' => $result['status'] ?? 'unknown',
                'body' => $result['body'] ?? null
            ]);
            return response()->json($result, 500);
        }

        return response()->json([
            'message' => 'Smart annotation updated successfully',
            'smart_annotation' => $result
        ], 200);
    }
}
