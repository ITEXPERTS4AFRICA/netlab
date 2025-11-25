#!/bin/bash

# Script pour résoudre les conflits lors d'un git pull sur le serveur
# Usage: ./scripts/resolve-pull-conflict.sh

echo "🔧 Résolution des conflits Git pour le pull"
echo "=============================================="
echo ""

# Vérifier l'état Git
echo "1. Vérification de l'état Git..."
git status

echo ""
echo "2. Vérification des fichiers modifiés localement..."
MODIFIED_FILES=$(git diff --name-only)
if [ -z "$MODIFIED_FILES" ]; then
    MODIFIED_FILES=$(git diff --cached --name-only)
fi

if [ -z "$MODIFIED_FILES" ]; then
    echo "   ✅ Aucun fichier modifié localement"
    echo ""
    echo "3. Tentative de pull..."
    git pull origin master
    exit $?
fi

echo "   Fichiers modifiés :"
echo "$MODIFIED_FILES"
echo ""

# Pour chaque fichier modifié, proposer de stash ou commit
for file in $MODIFIED_FILES; do
    echo "📄 Fichier: $file"
    echo "   Options:"
    echo "   1. Stash (sauvegarder temporairement)"
    echo "   2. Commit (sauvegarder définitivement)"
    echo "   3. Abandonner les modifications locales"
    echo ""
    read -p "   Votre choix (1/2/3) [1]: " choice
    choice=${choice:-1}
    
    case $choice in
        1)
            echo "   💾 Stash des modifications de $file..."
            git stash push -m "Stash avant pull: $file" -- "$file"
            ;;
        2)
            echo "   💾 Commit des modifications de $file..."
            git add "$file"
            git commit -m "Sauvegarde locale: $file"
            ;;
        3)
            echo "   ⚠️  Abandon des modifications de $file..."
            git checkout -- "$file"
            ;;
        *)
            echo "   ❌ Choix invalide, stash par défaut"
            git stash push -m "Stash avant pull: $file" -- "$file"
            ;;
    esac
    echo ""
done

echo "4. Tentative de pull..."
git pull origin master

if [ $? -eq 0 ]; then
    echo ""
    echo "✅ Pull réussi !"
    echo ""
    echo "5. Application des modifications stashées (si applicable)..."
    if git stash list | grep -q "Stash avant pull"; then
        echo "   Modifications stashées trouvées. Voulez-vous les réappliquer ? (y/n) [y]"
        read -p "   " apply_stash
        apply_stash=${apply_stash:-y}
        if [ "$apply_stash" = "y" ]; then
            git stash pop
            echo "   ✅ Modifications réappliquées"
        else
            echo "   ℹ️  Modifications conservées dans le stash (git stash list pour voir)"
        fi
    fi
else
    echo ""
    echo "❌ Erreur lors du pull. Vérifiez les conflits manuellement."
    exit 1
fi

echo ""
echo "✅ Terminé !"

