#!/bin/bash

echo "🔄 Réinitialisation de la base de données et exécution des migrations"
echo ""

# Demander confirmation
read -p "⚠️  Cette action va supprimer toutes les tables. Continuer ? (o/N): " confirm
if [[ ! $confirm =~ ^[Oo]$ ]]; then
    echo "❌ Opération annulée"
    exit 1
fi

echo ""
echo "🗑️  Suppression de toutes les tables..."
php artisan migrate:fresh

if [ $? -eq 0 ]; then
    echo ""
    echo "✅ Base de données réinitialisée et migrations exécutées avec succès !"
else
    echo ""
    echo "❌ Erreur lors de la réinitialisation"
    exit 1
fi

