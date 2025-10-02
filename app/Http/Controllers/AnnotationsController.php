<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\CiscoApiService;
use Illuminate\Validation\Rule;


class AnnotationsController extends Controller
{
    protected $ciscoApiService;

    public function __construct(CiscoApiService $ciscoApiService)
    {
        $this->ciscoApiService = $ciscoApiService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, $lab_id)
    {
        // Validate lab_id matches CML UUID pattern
        $request->merge(['lab_id' => $lab_id]);
        $request->validate([
            'lab_id' => 'required|regex:/^[\da-f]{8}-[\da-f]{4}-4[\da-f]{3}-[89ab][\da-f]{3}-[\da-f]{12}(?!\n)$/',
        ]);

        $token = session('cml_token');
        if (!$token) {
            return response()->json(['error' => 'No CML token'], 401);
        }

        $annotations = $this->ciscoApiService->getLabAnnotations($token, $lab_id);

        if (isset($annotations['error'])) {
            return response()->json($annotations, 500);
        }

        return response()->json($annotations, 200);
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
            return response()->json(['error' => 'No CML token'], 401);
        }

        $data = $request->only([
            'type', 'rotation', 'border_color', 'border_style', 'color',
            'thickness', 'x1', 'y1', 'x2', 'y2', 'z_index',
            'text_content', 'text_bold', 'text_font', 'text_italic',
            'text_size', 'text_unit', 'line_start', 'line_end',
            'border_radius'
        ]);

        $result = $this->ciscoApiService->createLabAnnotation($token, $lab_id, $data);

        if (isset($result['error'])) {
            return response()->json($result, 500);
        }

        return response()->json($result, 201);
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
            return response()->json(['error' => 'No CML token'], 401);
        }

        $data = $request->only([
            'type', 'rotation', 'border_color', 'border_style', 'color',
            'thickness', 'x1', 'y1', 'x2', 'y2', 'z_index',
            'text_content', 'text_bold', 'text_font', 'text_italic',
            'text_size', 'text_unit', 'line_start', 'line_end',
            'border_radius'
        ]);

        $result = $this->ciscoApiService->updateLabAnnotation($token, $lab_id, $annotation_id, $data);

        if (isset($result['error'])) {
            return response()->json($result, 500);
        }

        return response()->json($result, 200);
    }

    /**
     * Remove the specified resource from storage.
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
            return response()->json(['error' => 'No CML token'], 401);
        }

        $result = $this->ciscoApiService->deleteLabAnnotation($token, $lab_id, $annotation_id);

        if (isset($result['error'])) {
            return response()->json($result, 500); 
        }

        return response()->json(['message' => 'Annotation deleted successfully'], 200);
    }
}
