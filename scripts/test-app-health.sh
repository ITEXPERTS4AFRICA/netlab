#!/bin/bash

# Script de test de santé de l'application NetLab
# Vérifie que tous les services sont opérationnels

set -e

BASE_URL="${BASE_URL:-http://localhost:8000}"
API_URL="${API_URL:-http://localhost:8000/api}"

echo "🔍 Test de santé de NetLab"
echo "================================"
echo "URL de base: $BASE_URL"
echo ""

# Couleurs pour les résultats
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Compteurs
PASSED=0
FAILED=0

# Fonction pour tester un endpoint
test_endpoint() {
    local method=$1
    local url=$2
    local expected_status=$3
    local description=$4
    local data=$5
    
    echo -n "Test: $description ... "
    
    if [ "$method" = "GET" ]; then
        response=$(curl -s -w "\n%{http_code}" -X GET "$url" -H "Accept: application/json" 2>/dev/null || echo -e "\n000")
    elif [ "$method" = "POST" ]; then
        response=$(curl -s -w "\n%{http_code}" -X POST "$url" \
            -H "Content-Type: application/json" \
            -H "Accept: application/json" \
            -d "$data" 2>/dev/null || echo -e "\n000")
    else
        response=$(curl -s -w "\n%{http_code}" -X "$method" "$url" \
            -H "Accept: application/json" 2>/dev/null || echo -e "\n000")
    fi
    
    http_code=$(echo "$response" | tail -n1)
    body=$(echo "$response" | sed '$d')
    
    if [ "$http_code" = "$expected_status" ] || [ "$http_code" = "200" ] || [ "$http_code" = "302" ]; then
        echo -e "${GREEN}✓ OK${NC} (HTTP $http_code)"
        ((PASSED++))
        return 0
    else
        echo -e "${RED}✗ ÉCHEC${NC} (HTTP $http_code)"
        if [ -n "$body" ]; then
            echo "  Réponse: $(echo "$body" | head -c 100)"
        fi
        ((FAILED++))
        return 1
    fi
}

# 1. Test de la page d'accueil
echo "📄 Tests des pages web"
echo "----------------------"
test_endpoint "GET" "$BASE_URL" "200" "Page d'accueil"

# 2. Test de la route de santé Laravel
test_endpoint "GET" "$BASE_URL/up" "200" "Route de santé Laravel (/up)"

# 3. Test de la page de connexion
test_endpoint "GET" "$BASE_URL/login" "200" "Page de connexion"

# 4. Test de la page d'inscription
test_endpoint "GET" "$BASE_URL/register" "200" "Page d'inscription"

# 5. Test des routes API (sans authentification)
echo ""
echo "🔌 Tests des routes API publiques"
echo "----------------------------------"
test_endpoint "GET" "$API_URL/console/ping" "200" "API Console Ping" || echo "  ⚠️  Route console supprimée (normal)"

# 6. Test de la connexion à la base de données (via artisan)
echo ""
echo "🗄️  Tests de la base de données"
echo "-------------------------------"
echo -n "Test: Connexion à la base de données ... "
if docker-compose exec -T app php artisan db:monitor 2>/dev/null | grep -q "Connection: OK"; then
    echo -e "${GREEN}✓ OK${NC}"
    ((PASSED++))
else
    # Alternative: tester via tinker
    db_test=$(docker-compose exec -T app php artisan tinker --execute="echo DB::connection()->getPdo() ? 'OK' : 'FAIL';" 2>/dev/null | grep -o "OK\|FAIL" || echo "UNKNOWN")
    if [ "$db_test" = "OK" ]; then
        echo -e "${GREEN}✓ OK${NC}"
        ((PASSED++))
    else
        echo -e "${YELLOW}⚠ INCONNU${NC} (vérifiez manuellement)"
        ((FAILED++))
    fi
fi

# 7. Test de Redis
echo ""
echo "💾 Tests de Redis"
echo "-----------------"
echo -n "Test: Connexion à Redis ... "
if docker-compose exec -T redis redis-cli ping 2>/dev/null | grep -q "PONG"; then
    echo -e "${GREEN}✓ OK${NC}"
    ((PASSED++))
else
    echo -e "${RED}✗ ÉCHEC${NC}"
    ((FAILED++))
fi

# 8. Test des services Docker
echo ""
echo "🐳 Tests des services Docker"
echo "----------------------------"
services=("netlab_app" "netlab_postgres" "netlab_redis" "netlab_node" "netlab_queue" "netlab_scheduler")

for service in "${services[@]}"; do
    echo -n "Test: Service $service ... "
    if docker ps --format "{{.Names}}" | grep -q "^${service}$"; then
        status=$(docker inspect --format='{{.State.Status}}' "$service" 2>/dev/null || echo "unknown")
        if [ "$status" = "running" ]; then
            echo -e "${GREEN}✓ OK${NC} (running)"
            ((PASSED++))
        else
            echo -e "${YELLOW}⚠ $status${NC}"
            ((FAILED++))
        fi
    else
        echo -e "${RED}✗ ARRÊTÉ${NC}"
        ((FAILED++))
    fi
done

# 9. Test des migrations
echo ""
echo "🔄 Tests des migrations"
echo "----------------------"
echo -n "Test: État des migrations ... "
migration_status=$(docker-compose exec -T app php artisan migrate:status 2>/dev/null | tail -n +2 | wc -l || echo "0")
if [ "$migration_status" -gt "0" ]; then
    echo -e "${GREEN}✓ OK${NC} ($migration_status migrations trouvées)"
    ((PASSED++))
else
    echo -e "${YELLOW}⚠ Aucune migration trouvée${NC}"
    ((FAILED++))
fi

# 10. Test de Vite (si disponible)
echo ""
echo "⚡ Tests de Vite"
echo "---------------"
echo -n "Test: Serveur Vite ... "
if curl -s -f "http://localhost:5173" > /dev/null 2>&1; then
    echo -e "${GREEN}✓ OK${NC}"
    ((PASSED++))
else
    echo -e "${YELLOW}⚠ Non disponible${NC} (normal si pas en dev)"
fi

# Résumé
echo ""
echo "================================"
echo "📊 Résumé des tests"
echo "================================"
echo -e "${GREEN}Tests réussis: $PASSED${NC}"
echo -e "${RED}Tests échoués: $FAILED${NC}"
echo ""

if [ $FAILED -eq 0 ]; then
    echo -e "${GREEN}✅ Tous les tests sont passés!${NC}"
    exit 0
else
    echo -e "${YELLOW}⚠️  Certains tests ont échoué. Vérifiez les détails ci-dessus.${NC}"
    exit 1
fi


