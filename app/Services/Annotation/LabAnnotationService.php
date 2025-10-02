<?php

namespace App\Services\Annotation;

use Illuminate\Support\Facades\Http;
use App\Services\CiscoApiService;

class LabAnnotationService extends CiscoApiService{


    public function postLabsannotation($token, $id)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->post("{$this->baseUrl}/v0/labs/{$id}/annotations");
        return $response->successful() ? $response->json() : ['error' => 'Unable to post annotation', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function getLabsAnnotation($token, $id){
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->get("{$this->baseUrl}/v0/labs/{$id}/annotations");
        return $response->successful() ? $response->json() : ['error' => 'Unable to get annotation', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function getDetailsAnnotation($token, $lab_id, $annotation_id){
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->get("{$this->baseUrl}/v0/labs/{$lab_id}/annotations/{$annotation_id}");
        return $response->successful() ? $response->json() : ['error' => 'Unable to get annotation', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function putDetailsAnnotation($token, $lab_id, $annotation_id){
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->put("{$this->baseUrl}/v0/labs/{$lab_id}/annotations/{$annotation_id}");
        return $response->successful() ? $response->json() : ['error' => 'Unable to get annotation', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function deleteDetailsAnnotation($token, $lab_id, $annotation_id){
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->delete("{$this->baseUrl}/v0/labs/{$lab_id}/annotations/{$annotation_id}");
        return $response->successful() ? $response->json() : ['error' => 'Unable to get annotation', 'status' => $response->status(), 'body' => $response->body()];
    }
}
