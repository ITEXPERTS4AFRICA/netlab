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
        Schema::table('users', function (Blueprint $table) {            // Identifiants CML
            $table->string('cml_username', 32)->nullable()->unique()->after('metadata');
            $table->string('cml_user_id', 36)->nullable()->unique()->after('cml_username')
                ->comment('UUID de l\'utilisateur dans CML');

            // Permissions et statut CML
            $table->boolean('cml_admin')->default(false)->after('cml_user_id')
                ->comment('Droits administrateur dans CML (peut différer du rôle local)');

            // Groupes CML (array d'UUIDs)
            $table->json('cml_groups')->nullable()->after('cml_admin')
                ->comment('Groupes CML auxquels l\'utilisateur appartient (array d\'UUIDs)');

            // Pool de ressources
            $table->string('cml_resource_pool_id', 36)->nullable()->after('cml_groups')
                ->comment('Pool de ressources pour limiter les lancements de nœuds');

            // Authentification SSH
            $table->text('cml_pubkey')->nullable()->after('cml_resource_pool_id')
                ->comment('Clé publique SSH pour l\'authentification du serveur de console');

            // LDAP
            $table->string('cml_directory_dn', 255)->nullable()->after('cml_pubkey')
                ->comment('DN LDAP de l\'utilisateur (si authentification LDAP)');

            // Préférences utilisateur CML
            $table->boolean('cml_opt_in')->nullable()->after('cml_directory_dn')
                ->comment('Opt-in pour le formulaire de contact');
            $table->string('cml_tour_version', 128)->nullable()->after('cml_opt_in')
                ->comment('Version du tour d\'introduction vue par l\'utilisateur');

            // Token CML (temporaire, pour la session)
            $table->text('cml_token')->nullable()->after('cml_tour_version')
                ->comment('Token JWT CML (stocké temporairement pour la session)');
            $table->timestamp('cml_token_expires_at')->nullable()->after('cml_token')
                ->comment('Date d\'expiration du token CML');

            // Labs possédés par l'utilisateur dans CML (array d'UUIDs)
            $table->json('cml_owned_labs')->nullable()->after('cml_token_expires_at')
                ->comment('Labs possédés par l\'utilisateur dans CML (array d\'UUIDs)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'cml_username',
                'cml_user_id',
                'cml_admin',
                'cml_groups',
                'cml_resource_pool_id',
                'cml_pubkey',
                'cml_directory_dn',
                'cml_opt_in',
                'cml_tour_version',
                'cml_token',
                'cml_token_expires_at',
                'cml_owned_labs',
            ]);
        });
    }
};
