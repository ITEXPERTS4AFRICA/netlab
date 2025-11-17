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
        Schema::table('users', function (Blueprint $table) {
            // Rôle et permissions
            $table->enum('role', ['admin', 'instructor', 'student', 'user'])->default('user')->after('email');
            $table->boolean('is_active')->default(true)->after('role');
            
            // Informations de profil
            $table->string('avatar')->nullable()->after('is_active');
            $table->text('bio')->nullable()->after('avatar');
            $table->string('phone')->nullable()->after('bio');
            $table->string('organization')->nullable()->after('phone');
            $table->string('department')->nullable()->after('organization');
            $table->string('position')->nullable()->after('department');
            
            // Informations de formation
            $table->json('skills')->nullable()->after('position');
            $table->json('certifications')->nullable()->after('skills');
            $table->json('education')->nullable()->after('certifications');
            
            // Statistiques
            $table->integer('total_reservations')->default(0)->after('education');
            $table->integer('total_labs_completed')->default(0)->after('total_reservations');
            $table->timestamp('last_activity_at')->nullable()->after('total_labs_completed');
            
            // Métadonnées
            $table->json('metadata')->nullable()->after('last_activity_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'role',
                'is_active',
                'avatar',
                'bio',
                'phone',
                'organization',
                'department',
                'position',
                'skills',
                'certifications',
                'education',
                'total_reservations',
                'total_labs_completed',
                'last_activity_at',
                'metadata',
            ]);
        });
    }
};
