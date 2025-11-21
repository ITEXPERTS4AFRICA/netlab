<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lab;
use App\Models\LabDocumentationMedia;
use App\Models\LabSnapshot;
use App\Services\Cisco\LabService;
use App\Services\Cisco\ImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Inertia\Response;

class LabController extends Controller
{
    protected LabService $labService;
    protected ImportService $importService;

    public function __construct(LabService $labService, ImportService $importService)
    {
        $this->labService = $labService;
        $this->importService = $importService;
    }

    /**
     * Afficher la liste des labs
     */
    public function index(Request $request): Response
    {
        $query = Lab::query();

        // Recherche
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('lab_title', 'like', "%{$search}%")
                  ->orWhere('lab_description', 'like', "%{$search}%")
                  ->orWhere('short_description', 'like', "%{$search}%")
                  ->orWhere('cml_id', 'like', "%{$search}%");
            });
        }

        // Filtres
        if ($request->has('state') && $request->state !== 'all') {
            $query->where('state', $request->state);
        }

        if ($request->has('is_published') && Lab::hasColumn('is_published')) {
            $query->where('is_published', $request->boolean('is_published'));
        }

        if ($request->has('is_featured') && Lab::hasColumn('is_featured')) {
            $query->where('is_featured', $request->boolean('is_featured'));
        }

        if ($request->has('difficulty_level') && $request->difficulty_level !== 'all') {
            $query->where('difficulty_level', $request->difficulty_level);
        }

        $labs = $query->withCount(['reservations', 'documentationMedia'])
            ->orderBy('created_at', 'desc')
            ->paginate(20)
            ->withQueryString();

        // Calculer les stats correctement (is_published peut être null pour les anciens labs)
        $totalLabs = Lab::count();
        
        // Vérifier si les colonnes existent avant de les utiliser
        $hasPublishedColumn = Lab::hasColumn('is_published');
        $hasFeaturedColumn = Lab::hasColumn('is_featured');
        
        if ($hasPublishedColumn) {
            $publishedLabs = Lab::where('is_published', true)->count();
            $pendingLabs = Lab::where(function($q) {
                $q->where('is_published', false)
                  ->orWhereNull('is_published');
            })->count();
        } else {
            $publishedLabs = $totalLabs; // Si la colonne n'existe pas, considérer tous les labs comme publiés
            $pendingLabs = 0;
        }
        
        if ($hasFeaturedColumn) {
            $featuredLabs = Lab::where('is_featured', true)->count();
        } else {
            $featuredLabs = 0;
        }

        return Inertia::render('admin/labs/index', [
            'labs' => $labs,
            'filters' => $request->only(['search', 'state', 'is_published', 'is_featured', 'difficulty_level']),
            'stats' => [
                'total' => $totalLabs,
                'published' => $publishedLabs,
                'featured' => $featuredLabs,
                'pending' => $pendingLabs,
            ],
        ]);
    }

    // Note: La création manuelle de labs est désactivée
    // Les labs doivent être synchronisés depuis CML uniquement

    /**
     * Afficher un lab
     */
    public function show(Lab $lab): Response
    {
        $lab->load(['documentationMedia' => function ($query) {
            $query->orderBy('order');
        }]);

        // S'assurer que documentation_media est disponible pour le frontend
        $lab->setRelation('documentation_media', $lab->documentationMedia);

        return Inertia::render('admin/labs/show', [
            'lab' => $lab,
        ]);
    }

    /**
     * Afficher le formulaire d'édition
     */
    public function edit(Lab $lab): Response
    {
        $lab->load(['documentationMedia' => function ($query) {
            $query->orderBy('order');
        }]);

        return Inertia::render('admin/labs/edit', [
            'lab' => $lab,
        ]);
    }

    /**
     * Mettre à jour un lab
     */
    public function update(Request $request, Lab $lab)
    {
        $validated = $request->validate([
            'lab_title' => ['required', 'string', 'max:255'],
            'lab_description' => ['nullable', 'string'],
            'short_description' => ['nullable', 'string', 'max:500'],
            'price_cents' => ['nullable', 'integer', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'readme' => ['nullable', 'string'],
            'tags' => ['nullable', 'array'],
            'categories' => ['nullable', 'array'],
            'difficulty_level' => ['nullable', 'in:beginner,intermediate,advanced,expert'],
            'estimated_duration_minutes' => ['nullable', 'integer', 'min:1'],
            'is_featured' => ['boolean'],
            'is_published' => ['boolean'],
            'requirements' => ['nullable', 'array'],
            'learning_objectives' => ['nullable', 'array'],
        ]);

        $lab->update($validated);

        return redirect()->route('admin.labs.show', $lab)
            ->with('success', 'Lab mis à jour avec succès.');
    }

    /**
     * Supprimer un lab
     */
    public function destroy(Lab $lab)
    {
        // Supprimer les médias associés
        foreach ($lab->documentationMedia as $media) {
            $media->deleteFile();
        }

        $lab->delete();

        return redirect()->route('admin.labs.index')
            ->with('success', 'Lab supprimé avec succès.');
    }

    /**
     * Synchroniser les labs depuis CML
     */
    public function syncFromCml(Request $request)
    {
        try {
            $token = session('cml_token');

            // Si pas de token, essayer de s'authentifier avec les credentials de la config
            if (!$token) {
                $credentials = \App\Helpers\CmlConfigHelper::getCredentials();
                
                if (!\App\Helpers\CmlConfigHelper::isConfigured()) {
                    return redirect()->route('admin.labs.index')
                        ->with('error', 'Configuration CML incomplète. Veuillez configurer CML dans /admin/cml-config.');
                }

                // Authentifier avec les credentials de la config
                $authService = new \App\Services\Cisco\AuthService();
                $authService->setBaseUrl($credentials['base_url']);
                $authResult = $authService->authExtended($credentials['username'], $credentials['password']);

                if (isset($authResult['error']) || !isset($authResult['token'])) {
                    \Log::error('Échec authentification CML pour synchronisation', [
                        'error' => $authResult['error'] ?? 'Token non reçu',
                    ]);
                    return redirect()->route('admin.labs.index')
                        ->with('error', 'Échec de l\'authentification CML. Vérifiez vos credentials dans /admin/cml-config.');
                }

                $token = $authResult['token'];
                session()->put('cml_token', $token);
                
                // S'assurer que le LabService utilise la bonne URL de base
                $this->labService->setBaseUrl($credentials['base_url']);
            } else {
                // S'assurer que le LabService utilise la bonne URL de base même avec un token existant
                $baseUrl = \App\Helpers\CmlConfigHelper::getBaseUrl();
                if ($baseUrl) {
                    $this->labService->setBaseUrl($baseUrl);
                }
            }

            $this->labService->setToken($token);
            $cmlLabs = $this->labService->getLabs();

            if (isset($cmlLabs['error'])) {
                \Log::error('Erreur récupération labs CML', [
                    'error' => $cmlLabs['error'],
                    'status' => $cmlLabs['status'] ?? null,
                ]);
                return redirect()->route('admin.labs.index')
                    ->with('error', 'Erreur lors de la récupération des labs CML: ' . ($cmlLabs['error'] ?? 'Erreur inconnue'));
            }

            // Vérifier si la réponse est vide ou invalide
            if (!is_array($cmlLabs)) {
                \Log::warning('Réponse CML invalide', ['response' => gettype($cmlLabs)]);
                return redirect()->route('admin.labs.index')
                    ->with('error', 'Réponse invalide de l\'API CML. Vérifiez votre connexion.');
            }

            if (empty($cmlLabs)) {
                return redirect()->route('admin.labs.index')
                    ->with('info', 'Aucun lab trouvé dans CML. Vérifiez que des labs existent sur votre serveur CML.');
            }

            $syncedCount = 0;
            $updatedCount = 0;
            $errorCount = 0;

            // Si c'est un tableau d'IDs, récupérer les détails
            foreach ($cmlLabs as $labId) {
                // Si c'est juste un ID (UUID), récupérer les détails complets
                if (is_string($labId)) {
                    $labData = $this->labService->getLab($labId);
                } else {
                    $labData = $labId;
                }

                if (isset($labData['error'])) {
                    \Log::warning('Erreur récupération détails lab', [
                        'lab_id' => is_string($labId) ? $labId : 'unknown',
                        'error' => $labData['error'],
                    ]);
                    $errorCount++;
                    continue;
                }

                if (!isset($labData['id']) && !isset($labData['uuid'])) {
                    \Log::warning('Lab sans ID valide', ['data' => $labData]);
                    $errorCount++;
                    continue;
                }

                // CML utilise des UUID4, on les stocke dans cml_id
                $cmlId = $labData['id'] ?? $labData['uuid'] ?? null;

                if (!$cmlId || !$this->isValidUuid($cmlId)) {
                    \Log::warning('Lab ID invalide ignoré', ['data' => $labData]);
                    $errorCount++;
                    continue;
                }

                // Vérifier si le lab existe déjà (matching par UUID CML)
                $existingLab = Lab::where('cml_id', $cmlId)->first();

                // Préparer les attributs avec gestion correcte des champs JSON
                $labAttributes = [
                    'cml_id' => $cmlId,
                    'lab_title' => $labData['lab_title'] ?? null,
                    'state' => $labData['state'] ?? null,
                    'node_count' => $labData['node_count'] ?? null,
                    'link_count' => $labData['link_count'] ?? null,
                    'owner' => $labData['owner'] ?? null,
                    'created' => $labData['created'] ?? null,
                    'modified' => $labData['modified'] ?? null,
                ];

                // Gérer lab_description (colonne JSON dans la DB, mais CML retourne une string)
                // PostgreSQL JSON nécessite un JSON valide, donc on convertit la string en JSON
                if (isset($labData['lab_description']) && !empty($labData['lab_description'])) {
                    if (is_string($labData['lab_description'])) {
                        // Convertir la string en JSON valide (string JSON)
                        $labAttributes['lab_description'] = json_encode($labData['lab_description'], JSON_UNESCAPED_UNICODE);
                    } elseif (is_array($labData['lab_description'])) {
                        // Déjà un array, Laravel le convertira en JSON
                        $labAttributes['lab_description'] = $labData['lab_description'];
                    }
                } else {
                    $labAttributes['lab_description'] = null;
                }

                // Gérer effective_permissions (doit être un array ou null)
                if (isset($labData['effective_permissions'])) {
                    if (is_array($labData['effective_permissions']) && !empty($labData['effective_permissions'])) {
                        $labAttributes['effective_permissions'] = $labData['effective_permissions'];
                    } else {
                        $labAttributes['effective_permissions'] = null;
                    }
                } else {
                    $labAttributes['effective_permissions'] = null;
                }

                if ($existingLab) {
                    // Mettre à jour le lab existant (sans écraser les métadonnées personnalisées)
                    $existingLab->update($labAttributes);
                    $updatedCount++;
                } else {
                    // Créer un nouveau lab
                    Lab::create($labAttributes);
                    $syncedCount++;
                }
            }

            // Construire le message de résultat
            $messageParts = [];
            if ($syncedCount > 0) {
                $messageParts[] = "{$syncedCount} nouveau(x) lab(s) ajouté(s)";
            }
            if ($updatedCount > 0) {
                $messageParts[] = "{$updatedCount} lab(s) mis à jour";
            }
            if ($errorCount > 0) {
                $messageParts[] = "{$errorCount} erreur(s) rencontrée(s)";
            }

            if (empty($messageParts)) {
                $message = "Aucun lab synchronisé. Vérifiez que des labs existent dans CML.";
            } else {
                $message = "Synchronisation terminée: " . implode(', ', $messageParts) . ".";
            }

            return redirect()->route('admin.labs.index')
                ->with($syncedCount > 0 || $updatedCount > 0 ? 'success' : 'info', $message);
        } catch (\Exception $e) {
            \Log::error('Erreur synchronisation CML', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('admin.labs.index')
                ->with('error', 'Erreur lors de la synchronisation: ' . $e->getMessage());
        }
    }

    /**
     * Toggle featured status
     */
    public function toggleFeatured(Lab $lab)
    {
        $lab->is_featured = !$lab->is_featured;
        $lab->save();

        return redirect()->back()
            ->with('success', $lab->is_featured ? 'Lab mis en avant.' : 'Lab retiré des favoris.');
    }

    /**
     * Toggle published status
     */
    public function togglePublished(Lab $lab)
    {
        if (!Lab::hasColumn('is_published')) {
            return redirect()->back()
                ->with('error', 'La colonne is_published n\'existe pas. Exécutez les migrations.');
        }
        
        $lab->is_published = !$lab->is_published;
        $lab->save();

        return redirect()->back()
            ->with('success', $lab->is_published ? 'Lab publié.' : 'Lab dépublié.');
    }

    /**
     * Restreindre l'accès à un lab (le rendre privé)
     */
    public function toggleRestricted(Lab $lab)
    {
        // Utiliser le champ metadata pour stocker la restriction
        $metadata = $lab->metadata ?? [];
        $metadata['is_restricted'] = !($metadata['is_restricted'] ?? false);

        $lab->update(['metadata' => $metadata]);

        return redirect()->back()
            ->with('success', $metadata['is_restricted'] ? 'Accès restreint activé.' : 'Accès restreint désactivé.');
    }

    /**
     * Upload un média pour la documentation
     */
    public function uploadMedia(Request $request, Lab $lab)
    {
        $validator = Validator::make($request->all(), [
            'file' => ['required', 'file', 'max:10240'], // 10MB max
            'type' => ['required', 'in:image,video,document,link'],
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'order' => ['nullable', 'integer'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $file = $request->file('file');
        $type = $request->input('type');

        // Déterminer le chemin de stockage
        $path = $file->store("labs/{$lab->id}/documentation", 'public');

        $media = LabDocumentationMedia::create([
            'lab_id' => $lab->id,
            'type' => $type,
            'title' => $request->input('title'),
            'description' => $request->input('description'),
            'file_path' => $path,
            'file_url' => asset('storage/' . $path),
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'order' => $request->input('order', 0),
        ]);

        return response()->json([
            'success' => true,
            'media' => $media,
        ], 201);
    }

    /**
     * Ajouter un lien externe
     */
    public function addLink(Request $request, Lab $lab)
    {
        $validated = $request->validate([
            'url' => ['required', 'url'],
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'order' => ['nullable', 'integer'],
        ]);

        $media = LabDocumentationMedia::create([
            'lab_id' => $lab->id,
            'type' => 'link',
            'title' => $validated['title'] ?? parse_url($validated['url'], PHP_URL_HOST),
            'description' => $validated['description'] ?? null,
            'file_url' => $validated['url'],
            'order' => $validated['order'] ?? 0,
        ]);

        return response()->json([
            'success' => true,
            'media' => $media,
        ], 201);
    }

    /**
     * Mettre à jour un média
     */
    public function updateMedia(Request $request, Lab $lab, LabDocumentationMedia $media)
    {
        // Vérifier que le média appartient au lab
        if ($media->lab_id !== $lab->id) {
            return response()->json(['error' => 'Média non trouvé'], 404);
        }

        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'order' => ['nullable', 'integer'],
            'is_active' => ['boolean'],
            'url' => ['nullable', 'url'], // Pour les liens, on accepte 'url' qui sera mappé à 'file_url'
        ]);

        // Si c'est une mise à jour d'URL pour un lien, mapper 'url' vers 'file_url'
        if (isset($validated['url']) && $media->type === 'link') {
            $validated['file_url'] = $validated['url'];
            unset($validated['url']);
        }

        $media->update($validated);

        return response()->json([
            'success' => true,
            'media' => $media->fresh(),
        ]);
    }

    /**
     * Supprimer un média
     */
    public function deleteMedia(Lab $lab, LabDocumentationMedia $media)
    {
        // Vérifier que le média appartient au lab
        if ($media->lab_id !== $lab->id) {
            return response()->json(['error' => 'Média non trouvé'], 404);
        }

        $media->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Réorganiser l'ordre des médias
     */
    public function reorderMedia(Request $request, Lab $lab)
    {
        $validated = $request->validate([
            'media' => ['required', 'array'],
            'media.*.id' => ['required', 'exists:lab_documentation_media,id'],
            'media.*.order' => ['required', 'integer'],
        ]);

        foreach ($validated['media'] as $item) {
            LabDocumentationMedia::where('id', $item['id'])
                ->where('lab_id', $lab->id)
                ->update(['order' => $item['order']]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Vérifier si une chaîne est un UUID valide
     */
    private function isValidUuid(string $uuid): bool
    {
        return (bool) preg_match('/^[\da-f]{8}-[\da-f]{4}-4[\da-f]{3}-[89ab][\da-f]{3}-[\da-f]{12}$/i', $uuid);
    }

    /**
     * Sauvegarder la configuration actuelle d'un lab
     */
    public function saveSnapshot(Request $request, Lab $lab)
    {
        try {
            $token = session('cml_token');

            if (!$token) {
                return response()->json(['error' => 'Token CML non disponible'], 401);
            }

            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'description' => ['nullable', 'string'],
                'is_default' => ['boolean'],
            ]);

            $this->labService->setToken($token);

            // Télécharger la configuration complète du lab en YAML
            $configYaml = $this->labService->downloadLab($lab->cml_id);

            if (is_array($configYaml) && isset($configYaml['error'])) {
                return response()->json([
                    'error' => 'Erreur lors du téléchargement de la configuration: ' . ($configYaml['error'] ?? 'Erreur inconnue')
                ], 500);
            }

            // Récupérer les métadonnées du lab
            $labInfo = $this->labService->getLab($lab->cml_id);
            $topology = $this->labService->getTopology($lab->cml_id);

            $metadata = [
                'state' => $labInfo['state'] ?? null,
                'node_count' => $labInfo['node_count'] ?? null,
                'link_count' => $labInfo['link_count'] ?? null,
                'topology_summary' => [
                    'nodes' => count($topology['nodes'] ?? []),
                    'links' => count($topology['links'] ?? []),
                ],
            ];

            // Créer le snapshot
            $snapshot = LabSnapshot::create([
                'lab_id' => $lab->id,
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'config_yaml' => $configYaml,
                'metadata' => $metadata,
                'is_default' => $validated['is_default'] ?? false,
                'created_by' => auth()->id(),
            ]);

            // Si c'est le snapshot par défaut, désactiver les autres
            if ($snapshot->is_default) {
                $snapshot->setAsDefault();
            }

            return response()->json([
                'success' => true,
                'snapshot' => $snapshot,
                'message' => 'Snapshot créé avec succès',
            ], 201);
        } catch (\Exception $e) {
            \Log::error('Erreur sauvegarde snapshot', [
                'lab_id' => $lab->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Erreur lors de la sauvegarde: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Restaurer un lab depuis un snapshot
     */
    public function restoreSnapshot(Request $request, Lab $lab, LabSnapshot $snapshot)
    {
        try {
            // Vérifier que le snapshot appartient au lab
            if ($snapshot->lab_id !== $lab->id) {
                return response()->json(['error' => 'Snapshot non trouvé'], 404);
            }

            $token = session('cml_token');

            if (!$token) {
                return response()->json(['error' => 'Token CML non disponible'], 401);
            }

            $validated = $request->validate([
                'confirm' => ['required', 'boolean', 'accepted'],
            ]);

            if (!$validated['confirm']) {
                return response()->json(['error' => 'Confirmation requise'], 422);
            }

            $this->labService->setToken($token);
            $this->importService->setToken($token);

            // Convertir le YAML en JSON pour l'import
            // CML peut accepter YAML directement, mais on essaie d'abord JSON
            try {
                // Parser le YAML en JSON
                $yamlContent = $snapshot->config_yaml;

                // Essayer d'importer directement avec le YAML
                // L'API CML peut accepter YAML dans certains cas
                $importResult = $this->importService->importLabFromYaml($yamlContent, [
                    'title' => $lab->lab_title . ' (Restored from snapshot)',
                ]);

                if (isset($importResult['error'])) {
                    // Si l'import échoue, retourner le YAML pour téléchargement manuel
                    return response()->json([
                        'success' => false,
                        'error' => 'Erreur lors de l\'import: ' . ($importResult['error'] ?? 'Erreur inconnue'),
                        'config_yaml' => $yamlContent,
                        'snapshot' => $snapshot,
                        'manual_restore' => true,
                    ], 500);
                }

                // Si l'import réussit, mettre à jour le lab avec le nouvel ID
                if (isset($importResult['id'])) {
                    $lab->update(['cml_id' => $importResult['id']]);
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Lab restauré avec succès',
                    'import_result' => $importResult,
                    'snapshot' => $snapshot,
                ]);
            } catch (\Exception $e) {
                // En cas d'erreur, retourner le YAML pour restauration manuelle
                return response()->json([
                    'success' => false,
                    'error' => 'Erreur lors de la restauration: ' . $e->getMessage(),
                    'config_yaml' => $snapshot->config_yaml,
                    'snapshot' => $snapshot,
                    'manual_restore' => true,
                ], 500);
            }
        } catch (\Exception $e) {
            \Log::error('Erreur restauration snapshot', [
                'lab_id' => $lab->id,
                'snapshot_id' => $snapshot->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Erreur lors de la restauration: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Liste des snapshots d'un lab
     */
    public function listSnapshots(Lab $lab)
    {
        $snapshots = $lab->snapshots()
            ->with('creator')
            ->orderBy('snapshot_at', 'desc')
            ->get();

        return response()->json([
            'snapshots' => $snapshots,
        ]);
    }

    /**
     * Supprimer un snapshot
     */
    public function deleteSnapshot(Lab $lab, LabSnapshot $snapshot)
    {
        if ($snapshot->lab_id !== $lab->id) {
            return response()->json(['error' => 'Snapshot non trouvé'], 404);
        }

        $snapshot->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Définir un snapshot comme défaut
     */
    public function setDefaultSnapshot(Lab $lab, LabSnapshot $snapshot)
    {
        if ($snapshot->lab_id !== $lab->id) {
            return response()->json(['error' => 'Snapshot non trouvé'], 404);
        }

        $snapshot->setAsDefault();

        return response()->json([
            'success' => true,
            'snapshot' => $snapshot->fresh(),
        ]);
    }
}
