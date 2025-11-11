<?php

namespace App\Services\Cisco;

class TelemetryService extends BaseCiscoApiService
{
    /**
     * Soumettre un feedback
     */
    public function submitFeedback(array $data): array
    {
        return $this->post('/v0/feedback', $data);
    }

    /**
     * Obtenir les événements de télémétrie
     */
    public function getTelemetryEvents(): array
    {
        return $this->get('/v0/telemetry/events');
    }

    /**
     * Obtenir les paramètres de télémétrie
     */
    public function getTelemetrySettings(): array
    {
        return $this->get('/v0/telemetry');
    }

    /**
     * Définir les paramètres de télémétrie
     */
    public function setTelemetrySettings(array $data): array
    {
        return $this->put('/v0/telemetry', $data);
    }

    /**
     * Obtenir les diagnostics
     */
    public function getDiagnostics(string $category): array
    {
        return $this->get("/v0/diagnostics/{$category}");
    }

    /**
     * Obtenir les données d'événements de diagnostic
     */
    public function getDiagnosticEventData(): array
    {
        return $this->get('/v0/diagnostic_event_data');
    }
}

