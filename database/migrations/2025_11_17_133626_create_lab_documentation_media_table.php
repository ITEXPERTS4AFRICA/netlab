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
        Schema::create('lab_documentation_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lab_id')->constrained('labs')->cascadeOnDelete();
            $table->string('type'); // image, video, link, document, etc.
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->string('file_path')->nullable(); // Pour les fichiers uploadés (images, vidéos, PDFs)
            $table->string('file_url')->nullable(); // URL externe ou URL du fichier stocké
            $table->string('mime_type')->nullable(); // image/png, video/mp4, etc.
            $table->unsignedBigInteger('file_size')->nullable(); // Taille en bytes
            $table->string('thumbnail_path')->nullable(); // Pour les vidéos/images
            $table->integer('order')->default(0); // Ordre d'affichage
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable(); // Métadonnées supplémentaires
            $table->timestamps();

            $table->index('lab_id');
            $table->index('type');
            $table->index('is_active');
            $table->index(['lab_id', 'order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lab_documentation_media');
    }
};
