<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\CiscoApiService;
use Illuminate\Support\Facades\Log;

/**
 * Controller for handling lab annotations via API
 *
 * This controller provides JSON API endpoints for CML lab annotations
 * including creating, reading, updating, and deleting annotations.
 * Frontend components should use these endpoints for AJAX requests.
 */
class AnnotationsController extends Controller
{
    protected CiscoApiService $annotationService;

    public function __construct(CiscoApiService $annotationService)
    {
        $this->annotationService = $annotationService;
    }

    /**
     * Display a listing of annotations for a specific lab.
     * Returns JSON response for AJAX requests from frontend components.
     */
    public function index($lab_id)
    {
        $token = session('cml_token');
        if (!$token) {
            return response()->json(['error' => 'Authentication required'], 401);
        }

        $annotations = $this->annotationService->getTopology($token, $lab_id);

        if (isset($annotations['error'])) {
            Log::warning('Failed to fetch annotations via API', [
                'lab_id' => $lab_id,
                'error' => $annotations['error'],
                'status' => $annotations['status'] ?? 'unknown',
                'body' => $annotations['body'] ?? null
            ]);

            // If it's a 404, it might mean annotations are not supported
            if (isset($annotations['status']) && $annotations['status'] === 404) {
                return response()->json([
                    'error' => 'Annotations not available for this lab or CML instance',
                    'details' => $annotations['body'] ?? 'No additional details available'
                ], 404);
            }

            return response()->json($annotations, 500);
        }

        return response()->json($annotations, 200);
    }

    /**
     * Get a specific annotation by ID.
     * Returns JSON response for AJAX requests.
     */
    public function show($lab_id, $annotation_id)
    {
        $token = session('cml_token');
        if (!$token) {
            return response()->json(['error' => 'Authentication required'], 401);
        }

        // For now, we'll fetch all annotations and find the specific one
        // In a real implementation, you might want a dedicated API endpoint
        $annotations = $this->annotationService->getTopology($token, $lab_id);

        if (isset($annotations['error'])) {
            Log::warning('Failed to fetch annotations for show via API', [
                'lab_id' => $lab_id,
                'annotation_id' => $annotation_id,
                'error' => $annotations['error']
            ]);
            return response()->json($annotations, 500);
        }

        $annotation = collect($annotations)->firstWhere('id', $annotation_id);

        if (!$annotation) {
            return response()->json(['error' => 'Annotation not found'], 404);
        }

        return response()->json($annotation, 200);
    }

    /**
     * Store a newly created annotation in storage.
     * Returns JSON response for AJAX requests.
     */
    public function store(Request $request, $lab_id)
    {
        // Validate lab_id and annotation data
        $request->merge(['lab_id' => $lab_id]);
        $request->validate([
            'lab_id' => 'required|regex:/^[\da-f]{8}-[\da-f]{4}-4[\da-f]{3}-[89ab][\da-f]{3}-[\da-f]{12}(?!\n)$/',
            'type' => 'required|in:text,rectangle,ellipse,line',
            'x1' => 'required|numeric',
            'y1' => 'required|numeric',
        ]);

        $token = session('cml_token');
        if (!$token) {
            return response()->json(['error' => 'Authentication required'], 401);
        }

        $data = $request->only([
            'type',
            'rotation',
            'border_color',
            'border_style',
            'color',
            'thickness',
            'x1',
            'y1',
            'x2',
            'y2',
            'z_index',
            'text_content',
            'text_bold',
            'text_font',
            'text_italic',
            'text_size',
            'text_unit',
            'line_start',
            'line_end',
            'border_radius'
        ]);

        $result = $this->annotationService->createLabAnnotation($token, $lab_id, $data);

        if (isset($result['error'])) {
            Log::warning('Failed to create annotation via API', [
                'lab_id' => $lab_id,
                'error' => $result['error'],
                'status' => $result['status'] ?? 'unknown',
                'body' => $result['body'] ?? null
            ]);
            return response()->json($result, 500);
        }

        return response()->json([
            'message' => 'Annotation created successfully',
            'annotation' => $result
        ], 201);
    }



    /**
     * Update the specified annotation in storage.
     * Returns JSON response for AJAX requests.
     */
    public function update(Request $request, $lab_id, $annotation_id)
    {
        // Validate lab_id and annotation id
        $request->merge(['lab_id' => $lab_id, 'annotation_id' => $annotation_id]);
        $request->validate([
            'lab_id' => 'required|regex:/^[\da-f]{8}-[\da-f]{4}-4[\da-f]{3}-[89ab][\da-f]{3}-[\da-f]{12}(?!\n)$/',
            'annotation_id' => 'required|string',
        ]);

        $token = session('cml_token');
        if (!$token) {
            return response()->json(['error' => 'Authentication required'], 401);
        }

        $data = $request->only([
            'type',
            'rotation',
            'border_color',
            'border_style',
            'color',
            'thickness',
            'x1',
            'y1',
            'x2',
            'y2',
            'z_index',
            'text_content',
            'text_bold',
            'text_font',
            'text_italic',
            'text_size',
            'text_unit',
            'line_start',
            'line_end',
            'border_radius'
        ]);

        $result = $this->annotationService->updateLabAnnotation($token, $lab_id, $annotation_id, $data);

        if (isset($result['error'])) {
            Log::warning('Failed to update annotation via API', [
                'lab_id' => $lab_id,
                'annotation_id' => $annotation_id,
                'error' => $result['error'],
                'status' => $result['status'] ?? 'unknown',
                'body' => $result['body'] ?? null
            ]);
            return response()->json($result, 500);
        }

        return response()->json([
            'message' => 'Annotation updated successfully',
            'annotation' => $result
        ], 200);
    }

    /**
     * Remove the specified annotation from storage.
     * Returns JSON response for AJAX requests.
     */
    public function destroy(Request $request, $lab_id, $annotation_id)
    {
        // Validate lab_id and annotation id
        $request->merge(['lab_id' => $lab_id, 'annotation_id' => $annotation_id]);
        $request->validate([
            'lab_id' => 'required|regex:/^[\da-f]{8}-[\da-f]{4}-4[\da-f]{3}-[89ab][\da-f]{3}-[\da-f]{12}(?!\n)$/',
            'annotation_id' => 'required|string',
        ]);

        $token = session('cml_token');
        if (!$token) {
            return response()->json(['error' => 'Authentication required'], 401);
        }

        $result = $this->annotationService->deleteLabAnnotation($token, $lab_id, $annotation_id);

        if (isset($result['error'])) {
            Log::warning('Failed to delete annotation via API', [
                'lab_id' => $lab_id,
                'annotation_id' => $annotation_id,
                'error' => $result['error'],
                'status' => $result['status'] ?? 'unknown',
                'body' => $result['body'] ?? null
            ]);
            return response()->json($result, 500);
        }

        return response()->json([
            'message' => 'Annotation deleted successfully'
        ], 200);
    }

    /**
     * Display the lab schema/topology.
     */
    public function schema($lab_id)
    {
        $token = session('cml_token');
        if (!$token) {
            return response()->json(['error' => 'No CML token'], 401);
        }

        $schema = $this->annotationService->getTopology($token, $lab_id);

        if (isset($schema['error'])) {
            Log::warning('Failed to fetch lab schema via API', [
                'lab_id' => $lab_id,
                'error' => $schema['error'],
                'status' => $schema['status'] ?? 'unknown',
                'body' => $schema['body'] ?? null
            ]);

            // If it's a 404, it might mean schema is not available
            if (isset($schema['status']) && $schema['status'] === 404) {
                return response()->json([
                    'error' => 'Lab schema not available for this lab or CML instance',
                    'details' => $schema['body'] ?? 'No additional details available'
                ], 404);
            }

            return response()->json($schema, 500);
        }

        return response()->json($schema, 200);
    }
}
