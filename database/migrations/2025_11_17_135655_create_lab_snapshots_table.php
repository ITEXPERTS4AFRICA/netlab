<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('lab_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lab_id')->constrained('labs')->cascadeOnDelete();
            $table->string('name'); // Nom du snapshot (ex: "Configuration initiale", "Avant modification", etc.)
            $table->text('description')->nullable(); // Description optionnelle
            $table->text('config_yaml'); // Configuration complète du lab en YAML
            $table->json('config_json')->nullable(); // Configuration parsée en JSON (optionnel, pour recherche)
            $table->json('metadata')->nullable(); // Métadonnées supplémentaires (state, topology summary, etc.)
            $table->boolean('is_default')->default(false); // Snapshot par défaut pour restauration automatique
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete(); // Utilisateur qui a créé le snapshot
            $table->timestamp('snapshot_at')->useCurrent(); // Date/heure du snapshot
            $table->timestamps();

            $table->index('lab_id');
            $table->index('is_default');
            $table->index(['lab_id', 'is_default']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lab_snapshots');
    }
};
