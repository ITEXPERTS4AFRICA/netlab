#!/bin/bash

# Script pour résoudre le problème de migration settings sur le serveur
# Usage: ./scripts/fix-migration-settings.sh

echo "🔧 Correction de la migration settings"
echo "========================================"
echo ""

# Vérifier que nous sommes dans un dépôt Laravel
if [ ! -f artisan ]; then
    echo "❌ Erreur: Ce n'est pas un projet Laravel"
    exit 1
fi

# Option 1: Marquer la migration comme exécutée (si la table existe déjà)
echo "Option 1: Marquer la migration comme exécutée..."
echo ""

# Vérifier si la table settings existe
php artisan tinker --execute="
try {
    \$exists = \Illuminate\Support\Facades\Schema::hasTable('settings');
    if (\$exists) {
        echo '✅ La table settings existe déjà\n';
        // Insérer l'enregistrement dans migrations si absent
        \$migration = '2025_11_17_114322_create_settings_table';
        \$exists = \Illuminate\Support\Facades\DB::table('migrations')
            ->where('migration', \$migration)
            ->exists();
        if (!\$exists) {
            \Illuminate\Support\Facades\DB::table('migrations')->insert([
                'migration' => \$migration,
                'batch' => \Illuminate\Support\Facades\DB::table('migrations')->max('batch') + 1
            ]);
            echo '✅ Migration marquée comme exécutée\n';
        } else {
            echo 'ℹ️  Migration déjà enregistrée\n';
        }
    } else {
        echo '❌ La table settings n\'existe pas\n';
    }
} catch (\Exception \$e) {
    echo '❌ Erreur: ' . \$e->getMessage() . '\n';
}
"

echo ""
echo "Option 2: Exécuter les migrations avec --force (si vous êtes sûr)"
echo ""

# Option 2: Exécuter les migrations avec force
read -p "Voulez-vous exécuter les migrations maintenant ? (o/N): " confirm
if [[ $confirm =~ ^[Oo]$ ]]; then
    echo ""
    echo "🔄 Exécution des migrations..."
    php artisan migrate --force
    
    if [ $? -eq 0 ]; then
        echo ""
        echo "✅ Migrations exécutées avec succès !"
    else
        echo ""
        echo "❌ Erreur lors des migrations"
        echo ""
        echo "💡 Solution alternative:"
        echo "   php artisan migrate:status"
        echo "   # Puis marquer manuellement la migration comme exécutée"
    fi
else
    echo ""
    echo "ℹ️  Opération annulée"
fi

