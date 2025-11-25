<?php

namespace App\Traits;

trait ProvidesFeedback
{
    /**
     * Retourner une réponse avec un message de succès
     */
    protected function success(string $message, $data = null, int $status = 200)
    {
        if (request()->header('X-Inertia')) {
            return redirect()->back()->with('success', $message);
        }

        $response = ['success' => true, 'message' => $message];
        if ($data !== null) {
            $response['data'] = $data;
        }
        return response()->json($response, $status);
    }

    /**
     * Retourner une réponse avec un message d'erreur
     */
    protected function error(string $message, $errors = null, int $status = 400)
    {
        if (request()->header('X-Inertia')) {
            return redirect()->back()->with('error', $message)->withErrors($errors ?? []);
        }

        $response = ['success' => false, 'error' => $message];
        if ($errors !== null) {
            $response['errors'] = $errors;
        }
        return response()->json($response, $status);
    }

    /**
     * Retourner une réponse avec un message d'avertissement
     */
    protected function warning(string $message, $data = null, int $status = 200)
    {
        if (request()->header('X-Inertia')) {
            return redirect()->back()->with('warning', $message);
        }

        $response = ['success' => true, 'warning' => $message];
        if ($data !== null) {
            $response['data'] = $data;
        }
        return response()->json($response, $status);
    }

    /**
     * Retourner une réponse avec un message d'information
     */
    protected function info(string $message, $data = null, int $status = 200)
    {
        if (request()->header('X-Inertia')) {
            return redirect()->back()->with('info', $message);
        }

        $response = ['success' => true, 'info' => $message];
        if ($data !== null) {
            $response['data'] = $data;
        }
        return response()->json($response, $status);
    }

    /**
     * Messages de feedback prédéfinis
     */
    protected function feedbackMessages(): array
    {
        return [
            'created' => fn($resource) => "{$resource} créé(e) avec succès",
            'updated' => fn($resource) => "{$resource} mis(e) à jour avec succès",
            'deleted' => fn($resource) => "{$resource} supprimé(e) avec succès",
            'saved' => fn($resource) => "{$resource} enregistré(e) avec succès",
            'sent' => fn($resource) => "{$resource} envoyé(e) avec succès",
            'connected' => 'Connexion réussie',
            'disconnected' => 'Déconnexion réussie',
            'payment_success' => 'Paiement effectué avec succès',
            'reservation_created' => 'Réservation créée avec succès',
            'reservation_cancelled' => 'Réservation annulée avec succès',
            'config_saved' => 'Configuration enregistrée avec succès',
            'creation_failed' => fn($resource) => "Impossible de créer {$resource}",
            'update_failed' => fn($resource) => "Impossible de mettre à jour {$resource}",
            'deletion_failed' => fn($resource) => "Impossible de supprimer {$resource}",
            'load_failed' => fn($resource) => "Impossible de charger {$resource}",
            'save_failed' => fn($resource) => "Impossible d'enregistrer {$resource}",
            'network_error' => 'Erreur de connexion. Vérifiez votre connexion internet.',
            'unauthorized' => 'Vous n\'avez pas les permissions nécessaires',
            'not_found' => 'Ressource non trouvée',
            'validation_error' => 'Les données fournies sont invalides',
            'payment_failed' => 'Le paiement a échoué',
            'reservation_failed' => 'Impossible de créer la réservation',
        ];
    }
}

