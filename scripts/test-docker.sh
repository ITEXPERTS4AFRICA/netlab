#!/bin/bash

# Script de test pour la configuration Docker

echo "🧪 Test de la configuration Docker pour NetLab"
echo "=============================================="
echo ""

# Couleurs
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Fonction pour afficher les résultats
check() {
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✅ $1${NC}"
        return 0
    else
        echo -e "${RED}❌ $1${NC}"
        return 1
    fi
}

# Vérifier Docker
echo "1. Vérification de Docker..."
docker --version > /dev/null 2>&1
check "Docker est installé"

docker-compose --version > /dev/null 2>&1
check "Docker Compose est installé"

# Vérifier les fichiers Docker
echo ""
echo "2. Vérification des fichiers Docker..."

[ -f "Dockerfile" ] && check "Dockerfile existe" || echo -e "${RED}❌ Dockerfile manquant${NC}"
[ -f "Dockerfile.node" ] && check "Dockerfile.node existe" || echo -e "${YELLOW}⚠️  Dockerfile.node manquant (optionnel)${NC}"
[ -f "docker-compose.yml" ] && check "docker-compose.yml existe" || echo -e "${RED}❌ docker-compose.yml manquant${NC}"
[ -f ".dockerignore" ] && check ".dockerignore existe" || echo -e "${YELLOW}⚠️  .dockerignore manquant${NC}"

# Vérifier les fichiers de configuration
echo ""
echo "3. Vérification des fichiers de configuration..."

[ -f "docker/nginx/default.conf" ] && check "Configuration Nginx existe" || echo -e "${RED}❌ Configuration Nginx manquante${NC}"
[ -f "docker/php/php.ini" ] && check "Configuration PHP existe" || echo -e "${RED}❌ Configuration PHP manquante${NC}"
[ -f "docker/php/www.conf" ] && check "Configuration PHP-FPM existe" || echo -e "${RED}❌ Configuration PHP-FPM manquante${NC}"
[ -f "docker/supervisor/supervisord.conf" ] && check "Configuration Supervisor existe" || echo -e "${RED}❌ Configuration Supervisor manquante${NC}"
[ -f "docker/entrypoint.sh" ] && check "Script entrypoint existe" || echo -e "${RED}❌ Script entrypoint manquant${NC}"

# Vérifier la syntaxe du docker-compose.yml
echo ""
echo "4. Vérification de la syntaxe docker-compose.yml..."
docker-compose config > /dev/null 2>&1
check "Syntaxe docker-compose.yml valide"

# Test de build (optionnel, peut être long)
echo ""
read -p "5. Voulez-vous tester le build des images? (cela peut prendre plusieurs minutes) [y/N] " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    echo "🔨 Construction de l'image app..."
    docker-compose build app > /dev/null 2>&1
    check "Build de l'image app réussi"
    
    echo "🔨 Construction de l'image node..."
    docker-compose build node > /dev/null 2>&1
    check "Build de l'image node réussi"
fi

# Résumé
echo ""
echo "=============================================="
echo "✅ Tests terminés!"
echo ""
echo "Pour démarrer NetLab avec Docker:"
echo "  docker-compose up -d"
echo ""
echo "Pour voir les logs:"
echo "  docker-compose logs -f"
echo ""


