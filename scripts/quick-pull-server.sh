#!/bin/bash

# Script rapide pour résoudre le conflit et faire le pull
# Utilise stash pour sauvegarder temporairement les modifications

echo "🔄 Résolution rapide du conflit de pull"
echo "========================================"
echo ""

# Vérifier si on est dans un dépôt Git
if [ ! -d .git ]; then
    echo "❌ Erreur: Ce n'est pas un dépôt Git"
    exit 1
fi

# Afficher les fichiers modifiés
echo "📋 Fichiers modifiés localement qui bloquent le pull:"
git diff --name-only
echo ""

# Stash automatique des modifications
echo "💾 Sauvegarde temporaire des modifications locales (stash)..."
git stash push -m "Auto-stash avant pull - $(date '+%Y-%m-%d %H:%M:%S')"

if [ $? -eq 0 ]; then
    echo "   ✅ Modifications sauvegardées"
else
    echo "   ⚠️  Aucune modification à sauvegarder"
fi

echo ""
echo "🔄 Pull depuis origin/master..."
git pull origin master

if [ $? -eq 0 ]; then
    echo ""
    echo "✅ Pull réussi !"
    echo ""
    echo "💡 Pour réappliquer vos modifications locales:"
    echo "   git stash pop"
    echo ""
    echo "💡 Pour voir les modifications sauvegardées:"
    echo "   git stash list"
    echo "   git stash show -p"
else
    echo ""
    echo "❌ Erreur lors du pull"
    echo ""
    echo "💡 Pour restaurer vos modifications:"
    echo "   git stash pop"
    exit 1
fi

