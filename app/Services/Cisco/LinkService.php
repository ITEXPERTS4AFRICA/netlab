<?php

namespace App\Services\Cisco;

class LinkService extends BaseCiscoApiService
{
    /**
     * Obtenir la condition d'un link
     */
    public function getLinkCondition(string $labId, string $linkId): array
    {
        return $this->get("/api/v0/labs/{$labId}/links/{$linkId}/condition");
    }

    /**
     * Mettre à jour la condition d'un link
     */
    public function updateLinkCondition(string $labId, string $linkId, array $data): array
    {
        return $this->patch("/api/v0/labs/{$labId}/links/{$linkId}/condition", $data);
    }

    /**
     * Supprimer la condition d'un link
     */
    public function deleteLinkCondition(string $labId, string $linkId): array
    {
        return $this->delete("/api/v0/labs/{$labId}/links/{$linkId}/condition");
    }

    /**
     * Créer un link
     */
    public function createLink(string $labId, array $data): array
    {
        return $this->post("/api/v0/labs/{$labId}/links", $data);
    }

    /**
     * Obtenir les links d'un lab
     */
    public function getLabLinks(string $labId): array
    {
        return $this->get("/api/v0/labs/{$labId}/links");
    }

    /**
     * Obtenir un link spécifique
     */
    public function getLink(string $labId, string $linkId): array
    {
        return $this->get("/api/v0/labs/{$labId}/links/{$linkId}");
    }

    /**
     * Supprimer un link
     */
    public function deleteLink(string $labId, string $linkId): array
    {
        return $this->delete("/api/v0/labs/{$labId}/links/{$linkId}");
    }

    /**
     * Démarrer un link
     */
    public function startLink(string $labId, string $linkId): array
    {
        return $this->put("/api/v0/labs/{$labId}/links/{$linkId}/state/start");
    }

    /**
     * Arrêter un link
     */
    public function stopLink(string $labId, string $linkId): array
    {
        return $this->put("/api/v0/labs/{$labId}/links/{$linkId}/state/stop");
    }

    /**
     * Vérifier si un link a convergé
     */
    public function checkLinkIfConverged(string $labId, string $linkId): array
    {
        return $this->get("/api/v0/labs/{$labId}/links/{$linkId}/check_if_converged");
    }

    /**
     * Démarrer la capture d'un link
     */
    public function startLinkCapture(string $labId, string $linkId): array
    {
        return $this->put("/api/v0/labs/{$labId}/links/{$linkId}/capture/start");
    }

    /**
     * Arrêter la capture d'un link
     */
    public function stopLinkCapture(string $labId, string $linkId): array
    {
        return $this->put("/api/v0/labs/{$labId}/links/{$linkId}/capture/stop");
    }

    /**
     * Obtenir le statut de capture d'un link
     */
    public function getLinkCaptureStatus(string $labId, string $linkId): array
    {
        return $this->get("/api/v0/labs/{$labId}/links/{$linkId}/capture/status");
    }

    /**
     * Obtenir la clé de capture d'un link
     */
    public function getLinkCaptureKey(string $labId, string $linkId): array
    {
        return $this->get("/api/v0/labs/{$labId}/links/{$linkId}/capture/key");
    }

    /**
     * Télécharger un fichier PCAP
     */
    public function downloadPcap(string $linkCaptureKey)
    {
        $response = \Illuminate\Support\Facades\Http::withToken($this->token)
            ->withOptions(['verify' => false])
            ->withHeaders(['Accept' => 'application/octet-stream'])
            ->get("{$this->baseUrl}/api/v0/pcap/{$linkCaptureKey}");

        return $this->handleRawResponse($response, 'Unable to download PCAP');
    }

    /**
     * Obtenir les paquets PCAP
     */
    public function getPcapPackets(string $linkCaptureKey): array
    {
        return $this->get("/api/v0/pcap/{$linkCaptureKey}/packets");
    }

    /**
     * Télécharger un paquet PCAP spécifique
     */
    public function downloadPcapPacket(string $linkCaptureKey, string $packetId)
    {
        $response = \Illuminate\Support\Facades\Http::withToken($this->token)
            ->withOptions(['verify' => false])
            ->withHeaders(['Accept' => 'application/octet-stream'])
            ->get("{$this->baseUrl}/api/v0/pcap/{$linkCaptureKey}/packet/{$packetId}");

        return $this->handleRawResponse($response, 'Unable to download PCAP packet');
    }
}

