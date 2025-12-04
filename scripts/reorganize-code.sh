#!/bin/bash

# Script de réorganisation du code NetLab

echo "🔄 Réorganisation du code NetLab..."
echo "======================================"
echo ""

# Créer les dossiers nécessaires
echo "📁 Création des dossiers..."
mkdir -p scripts/tests
mkdir -p scripts/maintenance
mkdir -p docs/root-docs
mkdir -p scripts/utilities

# 1. Déplacer les fichiers de test temporaires
echo ""
echo "1. Déplacement des fichiers de test..."
for file in test-*.php check-*.php fix-*.php mark-*.php; do
    if [ -f "$file" ]; then
        echo "  → $file → scripts/tests/"
        mv "$file" scripts/tests/ 2>/dev/null || true
    fi
done

# 2. Déplacer les fichiers de documentation de la racine
echo ""
echo "2. Déplacement de la documentation..."
for file in *.md; do
    if [ -f "$file" ] && [ "$file" != "README.md" ] && [ "$file" != "DOCKER.md" ] && [ "$file" != "TEST-DOCKER.md" ]; then
        echo "  → $file → docs/root-docs/"
        mv "$file" docs/root-docs/ 2>/dev/null || true
    fi
done

# 3. Supprimer les fichiers temporaires
echo ""
echo "3. Suppression des fichiers temporaires..."
rm -f trouvés
rm -f *.backup *.bak *.tmp 2>/dev/null || true

# 4. Déplacer les scripts PHP de la racine
echo ""
echo "4. Déplacement des scripts PHP..."
for file in check-*.php fix-*.php mark-*.php; do
    if [ -f "$file" ]; then
        echo "  → $file → scripts/maintenance/"
        mv "$file" scripts/maintenance/ 2>/dev/null || true
    fi
done

echo ""
echo "✅ Réorganisation terminée!"
echo ""
echo "Structure organisée:"
echo "  - scripts/tests/          : Fichiers de test temporaires"
echo "  - scripts/maintenance/    : Scripts de maintenance"
echo "  - docs/root-docs/         : Documentation de la racine"
echo ""


