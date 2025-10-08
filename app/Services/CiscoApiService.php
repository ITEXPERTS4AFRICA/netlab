<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Phiki\Adapters\CommonMark\Transformers\Annotations\Annotation;

class CiscoApiService {
    protected $baseUrl;
    protected $token;

    public function __construct()
    {
        $this->baseUrl = config('services.cml.base_url');
        $this->token = session('cml_token');
    }

    public function auth_extended(String $username, String $password){
        try{
            $url = "{$this->baseUrl}/v0/auth_extended";
            $response = Http::withOptions(['verify' => false])
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->post($url, [
                    'username' => $username,
                    'password' => $password,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['token'])) {
                    session()->put('cml_token', $data['token']);
                    $this->token = $data['token'];
                }
                return $data;
            }

            if ($response->status() === 403) {
                return ['error' => 'Accès refusé. Identifiants invalides.'];
            }
            return ['error' => 'Authentification incorrecte', 'status' => $response->status()];
        }catch(\Exception $e){
            return ['error'=>'Exception: '. $e->getMessage()];
        }
        finally{
            $this->token = session('cml_token');
        }
    }


    public function getLabsAnnotation($token, $lab_id){
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->get("{$this->baseUrl}/v0/labs/{$lab_id}/annotations");
        return $response->successful() ? $response->json() : ['error' => 'Unable to fetch lab annotations', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function getLabSchema($token, $lab_id){
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->get("{$this->baseUrl}/v0/labs/{$lab_id}/topology");
        return $response->successful() ? $response->json() : ['error' => 'Unable to fetch lab schema', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function getUsers($token)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->get("{$this->baseUrl}/v0/users");
        return $response->successful() ? $response->json() : ['error' => 'Unable to fetch users', 'status' => $response->status()];
    }

    public function getLabs($token)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->get("{$this->baseUrl}/v0/labs");
        return $response->successful() ? $response->json() : ['error' => 'Unable to fetch labs', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function getDevices($token)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->get("{$this->baseUrl}/v0/devices");
        return $response->successful() ? $response->json() : ['error' => 'Unable to fetch devices', 'status' => $response->status()];
    }

    public function getLab($token, $id)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->get("{$this->baseUrl}/v0/labs/{$id}");
        return $response->successful() ? $response->json() : ['error' => 'Unable to fetch lab', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function startLab($token, $id)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->put("{$this->baseUrl}/v0/labs/{$id}/start");
        return $response->successful() ? $response->json() : ['error' => 'Unable to start lab', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function stopLab($token, $id)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->put("{$this->baseUrl}/v0/labs/{$id}/stop");
        return $response->successful() ? $response->json() : ['error' => 'Unable to stop lab', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function wipeLab($token, $id)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->put("{$this->baseUrl}/v0/labs/{$id}/wipe");
        return $response->successful() ? $response->json() : ['error' => 'Unable to wipe lab', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function logout($token){
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->delete("{$this->baseUrl}/v0/logout");
        return $response->successful() ? $response : $response->json();
    }

    public function setToken($token){
        $this->token = $token;
        session()->put('cml_token', $token);
    }

    /**
     * Revoke the current token if the CML API exposes a revoke endpoint.
     * This is a best-effort call and will not throw on failure.
     */
    public function revokeToken(): void
    {
        if (! $this->token) {
            return;
        }

        try {
            $url = "{$this->baseUrl}/v0/revoke";
            Http::withToken($this->token)->withOptions(['verify' => false])->post($url);
        } catch (\Exception $e) {
            // ignore - revoke best effort
        }

        // Ensure local session cleared
        session()->forget('cml_token');
        $this->token = null;
    }

    public function deleteLab($token, $id)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->delete("{$this->baseUrl}/v0/labs/{$id}");
        return $response->successful() ? $response->json() : ['error' => 'Unable to delete lab', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function updateLab($token, $id, $data)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->patch("{$this->baseUrl}/v0/labs/{$id}", $data);
        return $response->successful() ? $response->json() : ['error' => 'Unable to update lab', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function getLabState($token, $id)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->get("{$this->baseUrl}/v0/labs/{$id}/state");
        return $response->successful() ? $response->json() : ['error' => 'Unable to fetch lab state', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function getPyatsTestbed($token, $id)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/yaml'])->get("{$this->baseUrl}/v0/labs/{$id}/pyats_testbed");
        return $response->successful() ? $response->body() : ['error' => 'Unable to fetch pyATS testbed', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function checkIfConverged($token, $id)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->get("{$this->baseUrl}/v0/labs/{$id}/check_if_converged");
        return $response->successful() ? $response->json() : ['error' => 'Unable to check convergence', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function createInterfaces($token, $id, $data)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->post("{$this->baseUrl}/v0/labs/{$id}/interfaces", $data);
        return $response->successful() ? $response->json() : ['error' => 'Unable to create interfaces', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function getTopology($token, $id)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->get("{$this->baseUrl}/v0/labs/{$id}/topology");
        return $response->successful() ? $response->json() : ['error' => 'Unable to fetch topology', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function downloadLab($token, $id)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/yaml'])->get("{$this->baseUrl}/v0/labs/{$id}/download");
        return $response->successful() ? $response->body() : ['error' => 'Unable to download lab', 'status' => $response->status(), 'body' => $response->body()];
    }


    public function getLabAnnotations($token, $id)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->get("{$this->baseUrl}/v0/labs/{$id}/annotations");
        return $response->successful() ? $response->json() : ['error' => 'Unable to fetch lab annotations', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function createLabAnnotation($token, $id, $data)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->post("{$this->baseUrl}/v0/labs/{$id}/annotations", $data);
        return $response->successful() ? $response->json() : ['error' => 'Unable to create lab annotation', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function updateLabAnnotation($token, $id, $annotationId, $data)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->patch("{$this->baseUrl}/v0/labs/{$id}/annotations/{$annotationId}", $data);
        return $response->successful() ? $response->json() : ['error' => 'Unable to update lab annotation', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function deleteLabAnnotation($token, $id, $annotationId)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->delete("{$this->baseUrl}/v0/labs/{$id}/annotations/{$annotationId}");
        return $response->successful() ? $response->json() : ['error' => 'Unable to delete lab annotation', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function getLabEvents($token, $id)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->get("{$this->baseUrl}/v0/labs/{$id}/events");
        return $response->successful() ? $response->json() : ['error' => 'Unable to fetch lab events', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function getConnectorMappings($token, $id)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->get("{$this->baseUrl}/v0/labs/{$id}/connector_mappings");
        return $response->successful() ? $response->json() : ['error' => 'Unable to fetch connector mappings', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function updateConnectorMappings($token, $id, $data)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->patch("{$this->baseUrl}/v0/labs/{$id}/connector_mappings", $data);
        return $response->successful() ? $response->json() : ['error' => 'Unable to update connector mappings', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function getResourcePools($token, $id)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->get("{$this->baseUrl}/v0/labs/{$id}/resource_pools");
        return $response->successful() ? $response->json() : ['error' => 'Unable to fetch resource pools', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function findNodeByLabel($token, $id, $searchQuery)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->get("{$this->baseUrl}/v0/labs/{$id}/find/node/label/{$searchQuery}");
        return $response->successful() ? $response->json() : ['error' => 'Unable to find node by label', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function findNodesByTag($token, $id, $searchQuery)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->get("{$this->baseUrl}/v0/labs/{$id}/find_all/node/tag/{$searchQuery}");
        return $response->successful() ? $response->json() : ['error' => 'Unable to find nodes by tag', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function getLabElementState($token, $id)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->get("{$this->baseUrl}/v0/labs/{$id}/lab_element_state");
        return $response->successful() ? $response->json() : ['error' => 'Unable to fetch lab element state', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function getSimulationStats($token, $id)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->get("{$this->baseUrl}/v0/labs/{$id}/simulation_stats");
        return $response->successful() ? $response->json() : ['error' => 'Unable to fetch simulation stats', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function getLabTile($token, $id)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->get("{$this->baseUrl}/v0/labs/{$id}/tile");
        return $response->successful() ? $response->json() : ['error' => 'Unable to fetch lab tile info', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function getInterface($token, $lab_id, $interface_id)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->get("{$this->baseUrl}/v0/labs/{$lab_id}/interfaces/{$interface_id}");
        return $response->successful() ? $response->json() : ['error' => 'Unable to fetch interface details', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function updateInterface($token, $lab_id, $interface_id, $data)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->patch("{$this->baseUrl}/v0/labs/{$lab_id}/interfaces/{$interface_id}", $data);
        return $response->successful() ? $response->json() : ['error' => 'Unable to update interface', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function deleteInterface($token, $lab_id, $interface_id)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->delete("{$this->baseUrl}/v0/labs/{$lab_id}/interfaces/{$interface_id}");
        return $response->successful() ? $response->json() : ['error' => 'Unable to delete interface', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function getInterfaceState($token, $lab_id, $interface_id)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->get("{$this->baseUrl}/v0/labs/{$lab_id}/interfaces/{$interface_id}/state");
        return $response->successful() ? $response->json() : ['error' => 'Unable to fetch interface state', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function startInterface($token, $lab_id, $interface_id)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->put("{$this->baseUrl}/v0/labs/{$lab_id}/interfaces/{$interface_id}/state/start");
        return $response->successful() ? $response->json() : ['error' => 'Unable to start interface', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function stopInterface($token, $lab_id, $interface_id)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->put("{$this->baseUrl}/v0/labs/{$lab_id}/interfaces/{$interface_id}/state/stop");
        return $response->successful() ? $response->json() : ['error' => 'Unable to stop interface', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function getNodeInterfaces($token, $lab_id, $node_id)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->get("{$this->baseUrl}/v0/labs/{$lab_id}/nodes/{$node_id}/interfaces");
        return $response->successful() ? $response->json() : ['error' => 'Unable to fetch node interfaces', 'status' => $response->status(), 'body' => $response->body()];
    }



    public function addNode($token, $lab_id, $data)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->post("{$this->baseUrl}/v0/labs/{$lab_id}/nodes", $data);
        return $response->successful() ? $response->json() : ['error' => 'Unable to add node', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function getLabNodes($token, $lab_id)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->get("{$this->baseUrl}/v0/labs/{$lab_id}/nodes");
        return $response->successful() ? $response->json() : ['error' => 'Unable to fetch lab nodes', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function getNode($token, $lab_id, $node_id)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->get("{$this->baseUrl}/v0/labs/{$lab_id}/nodes/{$node_id}");
        return $response->successful() ? $response->json() : ['error' => 'Unable to fetch node details', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function updateNode($token, $lab_id, $node_id, $data)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->patch("{$this->baseUrl}/v0/labs/{$lab_id}/nodes/{$node_id}", $data);
        return $response->successful() ? $response->json() : ['error' => 'Unable to update node', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function deleteNode($token, $lab_id, $node_id)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->delete("{$this->baseUrl}/v0/labs/{$lab_id}/nodes/{$node_id}");
        return $response->successful() ? $response->json() : ['error' => 'Unable to delete node', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function wipeNodeDisks($token, $lab_id, $node_id)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->put("{$this->baseUrl}/v0/labs/{$lab_id}/nodes/{$node_id}/wipe_disks");
        return $response->successful() ? $response->json() : ['error' => 'Unable to wipe node disks', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function extractNodeConfiguration($token, $lab_id, $node_id)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->put("{$this->baseUrl}/v0/labs/{$lab_id}/nodes/{$node_id}/extract_configuration");
        return $response->successful() ? $response->json() : ['error' => 'Unable to extract node configuration', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function getNodeState($token, $lab_id, $node_id)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->get("{$this->baseUrl}/v0/labs/{$lab_id}/nodes/{$node_id}/state");
        return $response->successful() ? $response->json() : ['error' => 'Unable to fetch node state', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function startNode($token, $lab_id, $node_id)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->put("{$this->baseUrl}/v0/labs/{$lab_id}/nodes/{$node_id}/state/start");
        return $response->successful() ? $response->json() : ['error' => 'Unable to start node', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function stopNode($token, $lab_id, $node_id)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->put("{$this->baseUrl}/v0/labs/{$lab_id}/nodes/{$node_id}/state/stop");
        return $response->successful() ? $response->json() : ['error' => 'Unable to stop node', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function checkNodeIfConverged($token, $lab_id, $node_id)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->get("{$this->baseUrl}/v0/labs/{$lab_id}/nodes/{$node_id}/check_if_converged");
        return $response->successful() ? $response->json() : ['error' => 'Unable to check node convergence', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function getNodeVncKey($token, $lab_id, $node_id)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->get("{$this->baseUrl}/v0/labs/{$lab_id}/nodes/{$node_id}/keys/vnc");
        return $response->successful() ? $response->json() : ['error' => 'Unable to fetch VNC key', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function getNodeConsoleKey($token, $lab_id, $node_id)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->get("{$this->baseUrl}/v0/labs/{$lab_id}/nodes/{$node_id}/keys/console");
        return $response->successful() ? $response->json() : ['error' => 'Unable to fetch console key', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function getConsoleLog($token, $lab_id, $node_id, $console_id)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->get("{$this->baseUrl}/v0/labs/{$lab_id}/nodes/{$node_id}/consoles/{$console_id}/log");
        return $response->successful() ? $response->json() : ['error' => 'Unable to fetch console log', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function cloneNodeImage($token, $lab_id, $node_id)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->put("{$this->baseUrl}/v0/labs/{$lab_id}/nodes/{$node_id}/clone_image");
        return $response->successful() ? $response->json() : ['error' => 'Unable to clone node image', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function getAllNodes($token)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->get("{$this->baseUrl}/v0/nodes");
        return $response->successful() ? $response->json() : ['error' => 'Unable to fetch all nodes', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function getNodeLayer3Addresses($token, $lab_id, $node_id)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->get("{$this->baseUrl}/v0/labs/{$lab_id}/nodes/{$node_id}/layer3_addresses");
        return $response->successful() ? $response->json() : ['error' => 'Unable to fetch node layer 3 addresses', 'status' => $response->status(), 'body' => $response->body()];
    }

    // Links API methods
    public function getLinkCondition($token, $lab_id, $link_id)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->get("{$this->baseUrl}/v0/labs/{$lab_id}/links/{$link_id}/condition");
        return $response->successful() ? $response->json() : ['error' => 'Unable to fetch link condition', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function updateLinkCondition($token, $lab_id, $link_id, $data)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->patch("{$this->baseUrl}/v0/labs/{$lab_id}/links/{$link_id}/condition", $data);
        return $response->successful() ? $response->json() : ['error' => 'Unable to update link condition', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function deleteLinkCondition($token, $lab_id, $link_id)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->delete("{$this->baseUrl}/v0/labs/{$lab_id}/links/{$link_id}/condition");
        return $response->successful() ? $response->json() : ['error' => 'Unable to delete link condition', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function createLink($token, $lab_id, $data)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->post("{$this->baseUrl}/v0/labs/{$lab_id}/links", $data);
        return $response->successful() ? $response->json() : ['error' => 'Unable to create link', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function getLabLinks($token, $lab_id)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->get("{$this->baseUrl}/v0/labs/{$lab_id}/links");
        return $response->successful() ? $response->json() : ['error' => 'Unable to fetch lab links', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function getLink($token, $lab_id, $link_id)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->get("{$this->baseUrl}/v0/labs/{$lab_id}/links/{$link_id}");
        return $response->successful() ? $response->json() : ['error' => 'Unable to fetch link details', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function deleteLink($token, $lab_id, $link_id)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->delete("{$this->baseUrl}/v0/labs/{$lab_id}/links/{$link_id}");
        return $response->successful() ? $response->json() : ['error' => 'Unable to delete link', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function startLink($token, $lab_id, $link_id)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->put("{$this->baseUrl}/v0/labs/{$lab_id}/links/{$link_id}/state/start");
        return $response->successful() ? $response->json() : ['error' => 'Unable to start link', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function stopLink($token, $lab_id, $link_id)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->put("{$this->baseUrl}/v0/labs/{$lab_id}/links/{$link_id}/state/stop");
        return $response->successful() ? $response->json() : ['error' => 'Unable to stop link', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function checkLinkIfConverged($token, $lab_id, $link_id)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->get("{$this->baseUrl}/v0/labs/{$lab_id}/links/{$link_id}/check_if_converged");
        return $response->successful() ? $response->json() : ['error' => 'Unable to check link convergence', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function startLinkCapture($token, $lab_id, $link_id)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->put("{$this->baseUrl}/v0/labs/{$lab_id}/links/{$link_id}/capture/start");
        return $response->successful() ? $response->json() : ['error' => 'Unable to start link capture', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function stopLinkCapture($token, $lab_id, $link_id)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->put("{$this->baseUrl}/v0/labs/{$lab_id}/links/{$link_id}/capture/stop");
        return $response->successful() ? $response->json() : ['error' => 'Unable to stop link capture', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function getLinkCaptureStatus($token, $lab_id, $link_id)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->get("{$this->baseUrl}/v0/labs/{$lab_id}/links/{$link_id}/capture/status");
        return $response->successful() ? $response->json() : ['error' => 'Unable to get link capture status', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function getLinkCaptureKey($token, $lab_id, $link_id)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->get("{$this->baseUrl}/v0/labs/{$lab_id}/links/{$link_id}/capture/key");
        return $response->successful() ? $response->json() : ['error' => 'Unable to get link capture key', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function downloadPcap($token, $link_capture_key)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/octet-stream'])->get("{$this->baseUrl}/v0/pcap/{$link_capture_key}");
        return $response->successful() ? $response->body() : ['error' => 'Unable to download PCAP', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function getPcapPackets($token, $link_capture_key)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->get("{$this->baseUrl}/v0/pcap/{$link_capture_key}/packets");
        return $response->successful() ? $response->json() : ['error' => 'Unable to get PCAP packets', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function downloadPcapPacket($token, $link_capture_key, $packet_id)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/octet-stream'])->get("{$this->baseUrl}/v0/pcap/{$link_capture_key}/packet/{$packet_id}");
        return $response->successful() ? $response->body() : ['error' => 'Unable to download PCAP packet', 'status' => $response->status(), 'body' => $response->body()];
    }

    // Runtime API methods (some already exist)
    public function getAllConsoleKeys($token)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->get("{$this->baseUrl}/v0/keys/console");
        return $response->successful() ? $response->json() : ['error' => 'Unable to get console keys', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function getAllVncKeys($token)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->get("{$this->baseUrl}/v0/keys/vnc");
        return $response->successful() ? $response->json() : ['error' => 'Unable to get VNC keys', 'status' => $response->status(), 'body' => $response->body()];
    }

    // System API methods
    public function getSystemAuthConfig($token)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->get("{$this->baseUrl}/v0/system/auth/config");
        return $response->successful() ? $response->json() : ['error' => 'Unable to get system auth config', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function updateSystemAuthConfig($token, $data)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->patch("{$this->baseUrl}/v0/system/auth/config", $data);
        return $response->successful() ? $response->json() : ['error' => 'Unable to update system auth config', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function setSystemAuthConfig($token, $data)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->put("{$this->baseUrl}/v0/system/auth/config", $data);
        return $response->successful() ? $response->json() : ['error' => 'Unable to set system auth config', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function testSystemAuth($token, $data)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->post("{$this->baseUrl}/v0/system/auth/test", $data);
        return $response->successful() ? $response->json() : ['error' => 'Unable to test system auth', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function getSystemAuthGroups($token)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->get("{$this->baseUrl}/v0/system/auth/groups");
        return $response->successful() ? $response->json() : ['error' => 'Unable to get system auth groups', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function refreshSystemAuth($token)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->put("{$this->baseUrl}/v0/system/auth/refresh");
        return $response->successful() ? $response->json() : ['error' => 'Unable to refresh system auth', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function addLabRepo($token, $data)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->post("{$this->baseUrl}/v0/lab_repos", $data);
        return $response->successful() ? $response->json() : ['error' => 'Unable to add lab repo', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function getLabRepos($token)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->get("{$this->baseUrl}/v0/lab_repos");
        return $response->successful() ? $response->json() : ['error' => 'Unable to get lab repos', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function refreshLabRepos($token)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->put("{$this->baseUrl}/v0/lab_repos/refresh");
        return $response->successful() ? $response->json() : ['error' => 'Unable to refresh lab repos', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function deleteLabRepo($token, $repo_id)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->delete("{$this->baseUrl}/v0/lab_repos/{$repo_id}");
        return $response->successful() ? $response->json() : ['error' => 'Unable to delete lab repo', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function getComputeHosts($token)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->get("{$this->baseUrl}/v0/system/compute_hosts");
        return $response->successful() ? $response->json() : ['error' => 'Unable to get compute hosts', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function getComputeHostsConfiguration($token)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->get("{$this->baseUrl}/v0/system/compute_hosts/configuration");
        return $response->successful() ? $response->json() : ['error' => 'Unable to get compute hosts configuration', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function updateComputeHostsConfiguration($token, $data)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->patch("{$this->baseUrl}/v0/system/compute_hosts/configuration", $data);
        return $response->successful() ? $response->json() : ['error' => 'Unable to update compute hosts configuration', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function getComputeHost($token, $compute_id)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->get("{$this->baseUrl}/v0/system/compute_hosts/{$compute_id}");
        return $response->successful() ? $response->json() : ['error' => 'Unable to get compute host', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function updateComputeHost($token, $compute_id, $data)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->patch("{$this->baseUrl}/v0/system/compute_hosts/{$compute_id}", $data);
        return $response->successful() ? $response->json() : ['error' => 'Unable to update compute host', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function deleteComputeHost($token, $compute_id)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->delete("{$this->baseUrl}/v0/system/compute_hosts/{$compute_id}");
        return $response->successful() ? $response->json() : ['error' => 'Unable to delete compute host', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function getExternalConnectors($token)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->get("{$this->baseUrl}/v0/system/external_connectors");
        return $response->successful() ? $response->json() : ['error' => 'Unable to get external connectors', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function updateExternalConnectors($token)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->put("{$this->baseUrl}/v0/system/external_connectors");
        return $response->successful() ? $response->json() : ['error' => 'Unable to update external connectors', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function getExternalConnector($token, $connector_id)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->get("{$this->baseUrl}/v0/system/external_connectors/{$connector_id}");
        return $response->successful() ? $response->json() : ['error' => 'Unable to get external connector', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function updateExternalConnector($token, $connector_id, $data)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->patch("{$this->baseUrl}/v0/system/external_connectors/{$connector_id}", $data);
        return $response->successful() ? $response->json() : ['error' => 'Unable to update external connector', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function deleteExternalConnector($token, $connector_id)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->delete("{$this->baseUrl}/v0/system/external_connectors/{$connector_id}");
        return $response->successful() ? $response->json() : ['error' => 'Unable to delete external connector', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function getMaintenanceMode($token)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->get("{$this->baseUrl}/v0/system/maintenance_mode");
        return $response->successful() ? $response->json() : ['error' => 'Unable to get maintenance mode', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function updateMaintenanceMode($token, $data)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->patch("{$this->baseUrl}/v0/system/maintenance_mode", $data);
        return $response->successful() ? $response->json() : ['error' => 'Unable to update maintenance mode', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function addSystemNotice($token, $data)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->post("{$this->baseUrl}/v0/system/notices", $data);
        return $response->successful() ? $response->json() : ['error' => 'Unable to add system notice', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function getSystemNotices($token)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->get("{$this->baseUrl}/v0/system/notices");
        return $response->successful() ? $response->json() : ['error' => 'Unable to get system notices', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function getSystemNotice($token, $notice_id)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->get("{$this->baseUrl}/v0/system/notices/{$notice_id}");
        return $response->successful() ? $response->json() : ['error' => 'Unable to get system notice', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function updateSystemNotice($token, $notice_id, $data)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->patch("{$this->baseUrl}/v0/system/notices/{$notice_id}", $data);
        return $response->successful() ? $response->json() : ['error' => 'Unable to update system notice', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function deleteSystemNotice($token, $notice_id)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->delete("{$this->baseUrl}/v0/system/notices/{$notice_id}");
        return $response->successful() ? $response->json() : ['error' => 'Unable to delete system notice', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function getSystemArchive($token)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->get("{$this->baseUrl}/v0/system_archive");
        return $response->successful() ? $response->body() : ['error' => 'Unable to get system archive', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function getSystemHealth($token)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->get("{$this->baseUrl}/v0/system_health");
        return $response->successful() ? $response->json() : ['error' => 'Unable to get system health', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function getSystemStats($token)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->get("{$this->baseUrl}/v0/system_stats");
        return $response->successful() ? $response->json() : ['error' => 'Unable to get system stats', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function getSystemInformation()
    {
        $response = Http::withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->get("{$this->baseUrl}/v0/system_information");
        return $response->successful() ? $response->json() : ['error' => 'Unable to get system information', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function getWebSessionTimeout()
    {
        $response = Http::withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->get("{$this->baseUrl}/v0/web_session_timeout");
        return $response->successful() ? $response->json() : ['error' => 'Unable to get web session timeout', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function updateWebSessionTimeout($token, $timeout)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->patch("{$this->baseUrl}/v0/web_session_timeout/{$timeout}");
        return $response->successful() ? $response->json() : ['error' => 'Unable to update web session timeout', 'status' => $response->status(), 'body' => $response->body()];
    }

    // Node definitions API methods
    public function getNodeDefinitionsImageDefinitions($token, $def_id)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->get("{$this->baseUrl}/v0/node_definitions/{$def_id}/image_definitions");
        return $response->successful() ? $response->json() : ['error' => 'Unable to get node definitions image definitions', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function getNodeDefinitions($token)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->get("{$this->baseUrl}/v0/node_definitions");
        return $response->successful() ? $response->json() : ['error' => 'Unable to get node definitions', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function createNodeDefinition($token, $data)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->post("{$this->baseUrl}/v0/node_definitions", $data);
        return $response->successful() ? $response->json() : ['error' => 'Unable to create node definition', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function updateNodeDefinition($token, $data)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->put("{$this->baseUrl}/v0/node_definitions", $data);
        return $response->successful() ? $response->json() : ['error' => 'Unable to update node definition', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function getNodeDefinition($token, $def_id)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->get("{$this->baseUrl}/v0/node_definitions/{$def_id}");
        return $response->successful() ? $response->json() : ['error' => 'Unable to get node definition', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function deleteNodeDefinition($token, $def_id)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->delete("{$this->baseUrl}/v0/node_definitions/{$def_id}");
        return $response->successful() ? $response->json() : ['error' => 'Unable to delete node definition', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function setNodeDefinitionReadOnly($token, $def_id)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->put("{$this->baseUrl}/v0/node_definitions/{$def_id}/read_only");
        return $response->successful() ? $response->json() : ['error' => 'Unable to set node definition read-only', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function getSimplifiedNodeDefinitions($token)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->get("{$this->baseUrl}/v0/simplified_node_definitions");
        return $response->successful() ? $response->json() : ['error' => 'Unable to get simplified node definitions', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function getNodeDefinitionSchema($token)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->get("{$this->baseUrl}/v0/node_definition_schema");
        return $response->successful() ? $response->json() : ['error' => 'Unable to get node definition schema', 'status' => $response->status(), 'body' => $response->body()];
    }

    // Sample labs
    public function getSampleLabs($token)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->get("{$this->baseUrl}/v0/sample/labs");
        return $response->successful() ? $response->json() : ['error' => 'Unable to get sample labs', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function getSampleLab($token, $lab_id)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->get("{$this->baseUrl}/v0/sample/labs/{$lab_id}");
        return $response->successful() ? $response->json() : ['error' => 'Unable to get sample lab', 'status' => $response->status(), 'body' => $response->body()];
    }

    // Image definitions API methods
    public function uploadImage($token, $data)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->post("{$this->baseUrl}/v0/images/upload", $data);
        return $response->successful() ? $response->json() : ['error' => 'Unable to upload image', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function deleteManagedImage($token, $filename)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->delete("{$this->baseUrl}/v0/images/manage/{$filename}");
        return $response->successful() ? $response->json() : ['error' => 'Unable to delete managed image', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function getImageDefinitionSchema($token)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->get("{$this->baseUrl}/v0/image_definition_schema");
        return $response->successful() ? $response->json() : ['error' => 'Unable to get image definition schema', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function getListImageDefinitionDropFolder($token)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->get("{$this->baseUrl}/v0/list_image_definition_drop_folder");
        return $response->successful() ? $response->json() : ['error' => 'Unable to get list image definition drop folder', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function getImageDefinitions($token)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->get("{$this->baseUrl}/v0/image_definitions");
        return $response->successful() ? $response->json() : ['error' => 'Unable to get image definitions', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function createImageDefinition($token, $data)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->post("{$this->baseUrl}/v0/image_definitions", $data);
        return $response->successful() ? $response->json() : ['error' => 'Unable to create image definition', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function updateImageDefinition($token, $data)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->put("{$this->baseUrl}/v0/image_definitions", $data);
        return $response->successful() ? $response->json() : ['error' => 'Unable to update image definition', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function getImageDefinition($token, $def_id)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->get("{$this->baseUrl}/v0/image_definitions/{$def_id}");
        return $response->successful() ? $response->json() : ['error' => 'Unable to get image definition', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function deleteImageDefinition($token, $def_id)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->delete("{$this->baseUrl}/v0/image_definitions/{$def_id}");
        return $response->successful() ? $response->json() : ['error' => 'Unable to delete image definition', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function setImageDefinitionReadOnly($token, $def_id)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->put("{$this->baseUrl}/v0/image_definitions/{$def_id}/read_only");
        return $response->successful() ? $response->json() : ['error' => 'Unable to set image definition read-only', 'status' => $response->status(), 'body' => $response->body()];
    }

    // Smart Annotations API methods
    public function getSmartAnnotations($token, $lab_id)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->get("{$this->baseUrl}/v0/labs/{$lab_id}/smart_annotations");
        return $response->successful() ? $response->json() : ['error' => 'Unable to get smart annotations', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function getSmartAnnotation($token, $lab_id, $smart_annotation_id)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->get("{$this->baseUrl}/v0/labs/{$lab_id}/smart_annotations/{$smart_annotation_id}");
        return $response->successful() ? $response->json() : ['error' => 'Unable to get smart annotation', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function updateSmartAnnotation($token, $lab_id, $smart_annotation_id, $data)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->patch("{$this->baseUrl}/v0/labs/{$lab_id}/smart_annotations/{$smart_annotation_id}", $data);
        return $response->successful() ? $response->json() : ['error' => 'Unable to update smart annotation', 'status' => $response->status(), 'body' => $response->body()];
    }

    // Resource pools API methods
    public function createResourcePools($token, $data)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->post("{$this->baseUrl}/v0/resource_pools", $data);
        return $response->successful() ? $response->json() : ['error' => 'Unable to create resource pools', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function getAllResourcePools($token)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->get("{$this->baseUrl}/v0/resource_pools");
        return $response->successful() ? $response->json() : ['error' => 'Unable to get all resource pools', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function getResourcePool($token, $resource_pool_id)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->get("{$this->baseUrl}/v0/resource_pools/{$resource_pool_id}");
        return $response->successful() ? $response->json() : ['error' => 'Unable to get resource pool', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function updateResourcePool($token, $resource_pool_id, $data)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->patch("{$this->baseUrl}/v0/resource_pools/{$resource_pool_id}", $data);
        return $response->successful() ? $response->json() : ['error' => 'Unable to update resource pool', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function deleteResourcePool($token, $resource_pool_id)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->delete("{$this->baseUrl}/v0/resource_pools/{$resource_pool_id}");
        return $response->successful() ? $response->json() : ['error' => 'Unable to delete resource pool', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function getResourcePoolUsage($token)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->get("{$this->baseUrl}/v0/resource_pool_usage");
        return $response->successful() ? $response->json() : ['error' => 'Unable to get resource pool usage', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function getSingleResourcePoolUsage($token, $resource_pool_id)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->get("{$this->baseUrl}/v0/resource_pool_usage/{$resource_pool_id}");
        return $response->successful() ? $response->json() : ['error' => 'Unable to get single resource pool usage', 'status' => $response->status(), 'body' => $response->body()];
    }

    // Licensing API methods
    public function getLicensing($token)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->get("{$this->baseUrl}/v0/licensing");
        return $response->successful() ? $response->json() : ['error' => 'Unable to get licensing', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function setProductLicense($token, $data)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->put("{$this->baseUrl}/v0/licensing/product_license", $data);
        return $response->successful() ? $response->json() : ['error' => 'Unable to set product license', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function updateLicensingFeatures($token, $data)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->patch("{$this->baseUrl}/v0/licensing/features", $data);
        return $response->successful() ? $response->json() : ['error' => 'Unable to update licensing features', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function getLicensingStatus($token)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->get("{$this->baseUrl}/v0/licensing/status");
        return $response->successful() ? $response->json() : ['error' => 'Unable to get licensing status', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function getLicensingTechSupport($token)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->get("{$this->baseUrl}/v0/licensing/tech_support");
        return $response->successful() ? $response->json() : ['error' => 'Unable to get licensing tech support', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function setupLicensingTransport($token, $data)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->put("{$this->baseUrl}/v0/licensing/transport", $data);
        return $response->successful() ? $response->json() : ['error' => 'Unable to setup licensing transport', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function setupLicensingRegistration($token, $data)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->post("{$this->baseUrl}/v0/licensing/registration", $data);
        return $response->successful() ? $response->json() : ['error' => 'Unable to setup licensing registration', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function renewLicensingAuthorization($token)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->put("{$this->baseUrl}/v0/licensing/authorization/renew");
        return $response->successful() ? $response->json() : ['error' => 'Unable to renew licensing authorization', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function requestLicensingRenewal($token)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->put("{$this->baseUrl}/v0/licensing/registration/renew");
        return $response->successful() ? $response->json() : ['error' => 'Unable to request licensing renewal', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function deregisterLicensing($token)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->delete("{$this->baseUrl}/v0/licensing/deregistration");
        return $response->successful() ? $response->json() : ['error' => 'Unable to deregister licensing', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function enableReservationMode($token)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->put("{$this->baseUrl}/v0/licensing/reservation/mode");
        return $response->successful() ? $response->json() : ['error' => 'Unable to enable reservation mode', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function initiateReservationRequest($token, $data)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->post("{$this->baseUrl}/v0/licensing/reservation/request", $data);
        return $response->successful() ? $response->json() : ['error' => 'Unable to initiate reservation request', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function completeReservation($token, $data)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->post("{$this->baseUrl}/v0/licensing/reservation/complete", $data);
        return $response->successful() ? $response->json() : ['error' => 'Unable to complete reservation', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function cancelReservation($token)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->delete("{$this->baseUrl}/v0/licensing/reservation/cancel");
        return $response->successful() ? $response->json() : ['error' => 'Unable to cancel reservation', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function releaseReservation($token)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->delete("{$this->baseUrl}/v0/licensing/reservation/release");
        return $response->successful() ? $response->json() : ['error' => 'Unable to release reservation', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function discardReservationCode($token, $data)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->post("{$this->baseUrl}/v0/licensing/reservation/discard", $data);
        return $response->successful() ? $response->json() : ['error' => 'Unable to discard reservation code', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function getConfirmationCode($token)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->get("{$this->baseUrl}/v0/licensing/reservation/confirmation_code");
        return $response->successful() ? $response->json() : ['error' => 'Unable to get confirmation code', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function removeConfirmationCode($token)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->delete("{$this->baseUrl}/v0/licensing/reservation/confirmation_code");
        return $response->successful() ? $response->json() : ['error' => 'Unable to remove confirmation code', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function getReturnCode($token)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->get("{$this->baseUrl}/v0/licensing/reservation/return_code");
        return $response->successful() ? $response->json() : ['error' => 'Unable to get return code', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function removeReturnCode($token)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->delete("{$this->baseUrl}/v0/licensing/reservation/return_code");
        return $response->successful() ? $response->json() : ['error' => 'Unable to remove return code', 'status' => $response->status(), 'body' => $response->body()];
    }

    // Telemetry API methods
    public function submitFeedback($token, $data)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->post("{$this->baseUrl}/v0/feedback", $data);
        return $response->successful() ? $response->json() : ['error' => 'Unable to submit feedback', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function getTelemetryEvents($token)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->get("{$this->baseUrl}/v0/telemetry/events");
        return $response->successful() ? $response->json() : ['error' => 'Unable to get telemetry events', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function getTelemetrySettings($token)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->get("{$this->baseUrl}/v0/telemetry");
        return $response->successful() ? $response->json() : ['error' => 'Unable to get telemetry settings', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function setTelemetrySettings($token, $data)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->put("{$this->baseUrl}/v0/telemetry", $data);
        return $response->successful() ? $response->json() : ['error' => 'Unable to set telemetry settings', 'status' => $response->status(), 'body' => $response->body()];
    }

    // Diagnostics API methods
    public function getDiagnostics($token, $category)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->get("{$this->baseUrl}/v0/diagnostics/{$category}");
        return $response->successful() ? $response->json() : ['error' => 'Unable to get diagnostics', 'status' => $response->status(), 'body' => $response->body()];
    }

    public function getDiagnosticEventData($token)
    {
        $response = Http::withToken($token)->withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json'])->get("{$this->baseUrl}/v0/diagnostic_event_data");
        return $response->successful() ? $response->json() : ['error' => 'Unable to get diagnostic event data', 'status' => $response->status(), 'body' => $response->body()];
    }
}
