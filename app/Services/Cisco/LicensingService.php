<?php

namespace App\Services\Cisco;

class LicensingService extends BaseCiscoApiService
{
    /**
     * Obtenir les informations de licensing
     */
    public function getLicensing(): array
    {
        return $this->get('/api/v0/licensing');
    }

    /**
     * Définir la licence produit
     */
    public function setProductLicense(array $data): array
    {
        return $this->put('/api/v0/licensing/product_license', $data);
    }

    /**
     * Mettre à jour les fonctionnalités de licensing
     */
    public function updateLicensingFeatures(array $data): array
    {
        return $this->patch('/api/v0/licensing/features', $data);
    }

    /**
     * Obtenir le statut de licensing
     */
    public function getLicensingStatus(): array
    {
        return $this->get('/api/v0/licensing/status');
    }

    /**
     * Obtenir le support technique de licensing
     */
    public function getLicensingTechSupport(): array
    {
        return $this->get('/api/v0/licensing/tech_support');
    }

    /**
     * Configurer le transport de licensing
     */
    public function setupLicensingTransport(array $data): array
    {
        return $this->put('/api/v0/licensing/transport', $data);
    }

    /**
     * Configurer l'enregistrement de licensing
     */
    public function setupLicensingRegistration(array $data): array
    {
        return $this->post('/api/v0/licensing/registration', $data);
    }

    /**
     * Renouveler l'autorisation de licensing
     */
    public function renewLicensingAuthorization(): array
    {
        return $this->put('/api/v0/licensing/authorization/renew');
    }

    /**
     * Demander le renouvellement de licensing
     */
    public function requestLicensingRenewal(): array
    {
        return $this->put('/api/v0/licensing/registration/renew');
    }

    /**
     * Désenregistrer le licensing
     */
    public function deregisterLicensing(): array
    {
        return $this->delete('/api/v0/licensing/deregistration');
    }

    /**
     * Activer le mode de réservation
     */
    public function enableReservationMode(): array
    {
        return $this->put('/api/v0/licensing/reservation/mode');
    }

    /**
     * Initier une demande de réservation
     */
    public function initiateReservationRequest(array $data): array
    {
        return $this->post('/api/v0/licensing/reservation/request', $data);
    }

    /**
     * Compléter une réservation
     */
    public function completeReservation(array $data): array
    {
        return $this->post('/api/v0/licensing/reservation/complete', $data);
    }

    /**
     * Annuler une réservation
     */
    public function cancelReservation(): array
    {
        return $this->delete('/api/v0/licensing/reservation/cancel');
    }

    /**
     * Libérer une réservation
     */
    public function releaseReservation(): array
    {
        return $this->delete('/api/v0/licensing/reservation/release');
    }

    /**
     * Supprimer un code de réservation
     */
    public function discardReservationCode(array $data): array
    {
        return $this->post('/api/v0/licensing/reservation/discard', $data);
    }

    /**
     * Obtenir le code de confirmation
     */
    public function getConfirmationCode(): array
    {
        return $this->get('/api/v0/licensing/reservation/confirmation_code');
    }

    /**
     * Supprimer le code de confirmation
     */
    public function removeConfirmationCode(): array
    {
        return $this->delete('/api/v0/licensing/reservation/confirmation_code');
    }

    /**
     * Obtenir le code de retour
     */
    public function getReturnCode(): array
    {
        return $this->get('/api/v0/licensing/reservation/return_code');
    }

    /**
     * Supprimer le code de retour
     */
    public function removeReturnCode(): array
    {
        return $this->delete('/api/v0/licensing/reservation/return_code');
    }
}

