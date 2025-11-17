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
        Schema::table('labs', function (Blueprint $table) {
            // Métadonnées de gestion
            $table->unsignedBigInteger('price_cents')->nullable()->after('link_count'); // Prix en centimes
            $table->string('currency', 3)->default('XOF')->after('price_cents');
            $table->text('readme')->nullable()->after('currency'); // README markdown
            $table->text('short_description')->nullable()->after('readme'); // Description courte pour les listes
            $table->json('tags')->nullable()->after('short_description'); // Tags pour la recherche
            $table->json('categories')->nullable()->after('tags'); // Catégories
            $table->string('difficulty_level')->nullable()->after('categories'); // beginner, intermediate, advanced, expert
            $table->integer('estimated_duration_minutes')->nullable()->after('difficulty_level'); // Durée estimée
            $table->boolean('is_featured')->default(false)->after('estimated_duration_minutes'); // Lab mis en avant
            $table->boolean('is_published')->default(false)->after('is_featured'); // Lab publié/disponible
            $table->integer('view_count')->default(0)->after('is_published'); // Nombre de vues
            $table->integer('reservation_count')->default(0)->after('view_count'); // Nombre de réservations
            $table->decimal('rating', 3, 2)->nullable()->after('reservation_count'); // Note moyenne (0-5)
            $table->integer('rating_count')->default(0)->after('rating'); // Nombre de notes
            $table->json('requirements')->nullable()->after('rating_count'); // Prérequis (équipements, connaissances)
            $table->json('learning_objectives')->nullable()->after('requirements'); // Objectifs pédagogiques
            $table->json('metadata')->nullable()->after('learning_objectives'); // Métadonnées supplémentaires (JSON flexible)
            
            // Index pour les recherches et filtres
            $table->index('is_published');
            $table->index('is_featured');
            $table->index('difficulty_level');
            $table->index('price_cents');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('labs', function (Blueprint $table) {
            $table->dropColumn([
                'price_cents',
                'currency',
                'readme',
                'short_description',
                'tags',
                'categories',
                'difficulty_level',
                'estimated_duration_minutes',
                'is_featured',
                'is_published',
                'view_count',
                'reservation_count',
                'rating',
                'rating_count',
                'requirements',
                'learning_objectives',
                'metadata',
            ]);
        });
    }
};
