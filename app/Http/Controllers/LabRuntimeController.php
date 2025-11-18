<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Lab;
use App\Models\UsageRecord;
use App\Services\CiscoApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class LabRuntimeController extends Controller
{
    /**
     * Résoudre le lab depuis un paramètre de route
     * Peut être soit un ID de base de données (integer) soit un UUID CML (string)
     */
    private function resolveLab(string|int $labParam): ?Lab
    {
        // Si c'est un entier, chercher par ID de base de données
        if (is_numeric($labParam)) {
            return Lab::find($labParam);
        }
        
        // Sinon, chercher par cml_id (UUID)
        return Lab::where('cml_id', $labParam)->first();
    }

    public function start(Request $request, string|int $lab, CiscoApiService $cisco)
    {
        $labModel = $this->resolveLab($lab);
        
        if (!$labModel) {
            $error = 'Lab non trouvé.';
            \Log::error($error, ['lab_param' => $lab]);
            if ($request->header('X-Inertia')) {
                return redirect()->back()->withErrors(['error' => $error]);
            }
            return response()->json(['error' => $error], 404);
        }

        $token = session('cml_token');

        \Log::info('Démarrage du lab', [
            'lab_id' => $labModel->id,
            'cml_id' => $labModel->cml_id,
            'has_token' => !empty($token),
        ]);

        if (!$token) {
            $error = 'Token CML non disponible. Veuillez vous reconnecter.';
            \Log::error($error);
            if ($request->header('X-Inertia')) {
                return redirect()->back()->withErrors(['error' => $error]);
            }
            return response()->json(['error' => $error], 401);
        }

        $cisco->setToken($token);
        $startResp = $cisco->labs->startLab($labModel->cml_id);

        \Log::info('Réponse startLab', [
            'lab_id' => $labModel->id,
            'cml_id' => $labModel->cml_id,
            'response_type' => gettype($startResp),
            'has_error' => is_array($startResp) && isset($startResp['error']),
            'response_keys' => is_array($startResp) ? array_keys($startResp) : null,
        ]);

        if (is_array($startResp) && isset($startResp['error'])) {
            $errorMessage = $startResp['error'];
            $errorDetail = $startResp['body'] ?? $startResp['detail'] ?? null;
            
            \Log::error('Erreur lors du démarrage du lab', [
                'lab_id' => $labModel->id,
                'cml_id' => $labModel->cml_id,
                'error' => $errorMessage,
                'status' => $startResp['status'] ?? null,
                'detail' => $errorDetail,
            ]);
            
            // Construire un message d'erreur plus détaillé
            $fullErrorMessage = $errorMessage;
            if ($errorDetail) {
                $fullErrorMessage .= ' - ' . (is_string($errorDetail) ? $errorDetail : json_encode($errorDetail));
            }
            
            // Si c'est une requête Inertia, rediriger avec erreur
            if ($request->header('X-Inertia')) {
                return redirect()->back()->withErrors(['error' => $fullErrorMessage]);
            }
            return response()->json([
                'error' => 'Failed to start lab',
                'message' => $fullErrorMessage,
                'detail' => $startResp
            ], $startResp['status'] ?? 500);
        }

        // Mettre à jour l'état du lab dans la base de données
        $labModel->state = 'RUNNING';
        $labModel->save();

        \Log::info('Lab démarré avec succès', [
            'lab_id' => $labModel->id,
            'cml_id' => $labModel->cml_id,
            'state_updated' => true,
        ]);

        $user = Auth::user();
        $usage = UsageRecord::create([
            'reservation_id' => null,
            'user_id' => $user->id,
            'lab_id' => $labModel->id,
            'started_at' => now(),
        ]);

        // Si c'est une requête Inertia, rediriger vers la page workspace
        if ($request->header('X-Inertia')) {
            return redirect()->route('labs.workspace', ['lab' => $labModel->id])
                ->with('success', 'Lab démarré avec succès');
        }

        // Sinon, retourner du JSON pour les appels API
        return response()->json([
            'started' => true,
            'usage_record' => $usage,
            'lab' => [
                'id' => $labModel->id,
                'cml_id' => $labModel->cml_id,
                'state' => $labModel->state,
            ],
        ]);
    }

    public function stop(Request $request, string|int $lab, CiscoApiService $cisco)
    {
        $labModel = $this->resolveLab($lab);
        
        if (!$labModel) {
            $error = 'Lab non trouvé.';
            \Log::error($error, ['lab_param' => $lab]);
            if ($request->header('X-Inertia')) {
                return redirect()->back()->withErrors(['error' => $error]);
            }
            return response()->json(['error' => $error], 404);
        }

        $token = session('cml_token');
        
        if (!$token) {
            $error = 'Token CML non disponible. Veuillez vous reconnecter.';
            \Log::error($error);
            if ($request->header('X-Inertia')) {
                return redirect()->back()->withErrors(['error' => $error]);
            }
            return response()->json(['error' => $error], 401);
        }

        $cisco->setToken($token);
        $stopResp = $cisco->labs->stopLab($labModel->cml_id);
        
        if (is_array($stopResp) && isset($stopResp['error'])) {
            // Si c'est une requête Inertia, rediriger avec erreur
            if ($request->header('X-Inertia')) {
                return redirect()->back()->withErrors(['error' => 'Failed to stop lab: ' . ($stopResp['error'] ?? 'Unknown error')]);
            }
            return response()->json(['error' => 'Failed to stop lab', 'detail' => $stopResp], 500);
        }

        // find last usage record for this lab without end
        $usage = UsageRecord::where('lab_id', $labModel->id)->whereNull('ended_at')->latest('started_at')->first();
        if (! $usage) {
            if ($request->header('X-Inertia')) {
                return redirect()->back()->withErrors(['error' => 'No running usage record found']);
            }
            return response()->json(['error' => 'No running usage record found'], 404);
        }

        $usage->ended_at = now();
        $duration = $usage->ended_at->diffInSeconds($usage->started_at);
        $usage->duration_seconds = max(1, $duration);

        // cost calculation left to billing step
        $usage->save();

        // Mettre à jour l'état du lab dans la base de données
        $labModel->state = 'STOPPED';
        $labModel->save();

        \Log::info('Lab arrêté avec succès', [
            'lab_id' => $labModel->id,
            'cml_id' => $labModel->cml_id,
            'state_updated' => true,
        ]);

        // Si c'est une requête Inertia, rediriger vers la page workspace
        if ($request->header('X-Inertia')) {
            return redirect()->route('labs.workspace', ['lab' => $labModel->id])
                ->with('success', 'Lab arrêté avec succès');
        }

        // Sinon, retourner du JSON pour les appels API
        return response()->json([
            'stopped' => true,
            'usage_record' => $usage,
            'lab' => [
                'id' => $labModel->id,
                'cml_id' => $labModel->cml_id,
                'state' => $labModel->state,
            ],
        ]);
    }

    /**
     * Réinitialiser (wipe) un lab
     */
    public function wipe(Request $request, string|int $lab, CiscoApiService $cisco)
    {
        $labModel = $this->resolveLab($lab);
        
        if (!$labModel) {
            $error = 'Lab non trouvé.';
            \Log::error($error, ['lab_param' => $lab]);
            if ($request->header('X-Inertia')) {
                return redirect()->back()->withErrors(['error' => $error]);
            }
            return response()->json(['error' => $error], 404);
        }

        $token = session('cml_token');
        
        if (!$token) {
            $error = 'Token CML non disponible. Veuillez vous reconnecter.';
            \Log::error($error);
            if ($request->header('X-Inertia')) {
                return redirect()->back()->withErrors(['error' => $error]);
            }
            return response()->json(['error' => $error], 401);
        }

        $cisco->setToken($token);
        $wipeResp = $cisco->labs->wipeLab($labModel->cml_id);

        \Log::info('Réponse wipeLab', [
            'lab_id' => $labModel->id,
            'cml_id' => $labModel->cml_id,
            'response_type' => gettype($wipeResp),
            'has_error' => is_array($wipeResp) && isset($wipeResp['error']),
        ]);

        if (is_array($wipeResp) && isset($wipeResp['error'])) {
            $errorMessage = $wipeResp['error'];
            $errorDetail = $wipeResp['body'] ?? $wipeResp['detail'] ?? null;
            
            \Log::error('Erreur lors du wipe du lab', [
                'lab_id' => $labModel->id,
                'cml_id' => $labModel->cml_id,
                'error' => $errorMessage,
                'status' => $wipeResp['status'] ?? null,
                'detail' => $errorDetail,
            ]);
            
            $fullErrorMessage = $errorMessage;
            if ($errorDetail) {
                $fullErrorMessage .= ' - ' . (is_string($errorDetail) ? $errorDetail : json_encode($errorDetail));
            }
            
            if ($request->header('X-Inertia')) {
                return redirect()->back()->withErrors(['error' => $fullErrorMessage]);
            }
            return response()->json([
                'error' => 'Failed to wipe lab',
                'message' => $fullErrorMessage,
                'detail' => $wipeResp
            ], $wipeResp['status'] ?? 500);
        }

        \Log::info('Lab wiped avec succès', [
            'lab_id' => $labModel->id,
            'cml_id' => $labModel->cml_id,
        ]);

        if ($request->header('X-Inertia')) {
            return redirect()->route('labs.workspace', ['lab' => $labModel->id])
                ->with('success', 'Lab réinitialisé avec succès');
        }

        return response()->json(['wiped' => true]);
    }

    /**
     * Redémarrer un lab (stop puis start)
     */
    public function restart(Request $request, string|int $lab, CiscoApiService $cisco)
    {
        $labModel = $this->resolveLab($lab);
        
        if (!$labModel) {
            $error = 'Lab non trouvé.';
            \Log::error($error, ['lab_param' => $lab]);
            if ($request->header('X-Inertia')) {
                return redirect()->back()->withErrors(['error' => $error]);
            }
            return response()->json(['error' => $error], 404);
        }

        $token = session('cml_token');
        
        if (!$token) {
            $error = 'Token CML non disponible. Veuillez vous reconnecter.';
            \Log::error($error);
            if ($request->header('X-Inertia')) {
                return redirect()->back()->withErrors(['error' => $error]);
            }
            return response()->json(['error' => $error], 401);
        }

        $cisco->setToken($token);
        
        // Arrêter le lab d'abord
        $stopResp = $cisco->labs->stopLab($labModel->cml_id);
        
        if (is_array($stopResp) && isset($stopResp['error'])) {
            \Log::error('Erreur lors de l\'arrêt du lab pour restart', [
                'lab_id' => $labModel->id,
                'cml_id' => $labModel->cml_id,
                'error' => $stopResp['error'],
            ]);
            
            if ($request->header('X-Inertia')) {
                return redirect()->back()->withErrors(['error' => 'Failed to stop lab: ' . ($stopResp['error'] ?? 'Unknown error')]);
            }
            return response()->json(['error' => 'Failed to stop lab', 'detail' => $stopResp], 500);
        }

        // Attendre un peu avant de redémarrer
        sleep(2);

        // Redémarrer le lab
        $startResp = $cisco->labs->startLab($labModel->cml_id);

        if (is_array($startResp) && isset($startResp['error'])) {
            \Log::error('Erreur lors du redémarrage du lab', [
                'lab_id' => $labModel->id,
                'cml_id' => $labModel->cml_id,
                'error' => $startResp['error'],
            ]);
            
            if ($request->header('X-Inertia')) {
                return redirect()->back()->withErrors(['error' => 'Failed to restart lab: ' . ($startResp['error'] ?? 'Unknown error')]);
            }
            return response()->json(['error' => 'Failed to restart lab', 'detail' => $startResp], 500);
        }

        \Log::info('Lab redémarré avec succès', [
            'lab_id' => $labModel->id,
            'cml_id' => $labModel->cml_id,
        ]);

        if ($request->header('X-Inertia')) {
            return redirect()->route('labs.workspace', ['lab' => $labModel->id])
                ->with('success', 'Lab redémarré avec succès');
        }

        return response()->json(['restarted' => true]);
    }

    /**
     * Exporter (télécharger) un lab
     */
    public function export(Request $request, string|int $lab, CiscoApiService $cisco)
    {
        $labModel = $this->resolveLab($lab);
        
        if (!$labModel) {
            $error = 'Lab non trouvé.';
            \Log::error($error, ['lab_param' => $lab]);
            return response()->json(['error' => $error], 404);
        }

        $token = session('cml_token');
        
        if (!$token) {
            $error = 'Token CML non disponible. Veuillez vous reconnecter.';
            \Log::error($error);
            return response()->json(['error' => $error], 401);
        }

        $cisco->setToken($token);
        $yamlContent = $cisco->labs->downloadLab($labModel->cml_id);

        if (is_array($yamlContent) && isset($yamlContent['error'])) {
            \Log::error('Erreur lors de l\'export du lab', [
                'lab_id' => $labModel->id,
                'cml_id' => $labModel->cml_id,
                'error' => $yamlContent['error'],
            ]);
            
            return response()->json([
                'error' => 'Failed to export lab',
                'detail' => $yamlContent
            ], $yamlContent['status'] ?? 500);
        }

        $filename = ($labModel->lab_title ?? 'lab') . '_' . date('Y-m-d_H-i-s') . '.yaml';
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);

        return response($yamlContent, 200)
            ->header('Content-Type', 'application/yaml')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }
}


