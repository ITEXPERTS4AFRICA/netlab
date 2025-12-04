#!/bin/bash

# Script de nettoyage des fichiers temporaires de test

echo "🧹 Nettoyage des fichiers temporaires..."

# Créer le dossier pour les tests si nécessaire
mkdir -p scripts/tests

# Déplacer les fichiers de test vers scripts/tests
echo "📦 Déplacement des fichiers de test..."
for file in test-*.php check-*.php fix-*.php mark-*.php; do
    if [ -f "$file" ]; then
        echo "  → Déplacement de $file"
        mv "$file" scripts/tests/ 2>/dev/null || true
    fi
done

# Supprimer les fichiers temporaires
echo "🗑️  Suppression des fichiers temporaires..."
rm -f trouvés
rm -f *.backup *.bak *.tmp 2>/dev/null || true

echo "✅ Nettoyage terminé!"


