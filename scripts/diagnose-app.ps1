# Script de diagnostic complet de l'application NetLab

Write-Host "🔍 Diagnostic complet de NetLab" -ForegroundColor Cyan
Write-Host "=================================" -ForegroundColor Cyan
Write-Host ""

# 1. Vérifier les conteneurs Docker
Write-Host "1️⃣  État des conteneurs Docker:" -ForegroundColor Yellow
Write-Host "--------------------------------"
docker ps --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}" | Select-String -Pattern "netlab|NAMES"
Write-Host ""

# 2. Vérifier les logs de l'application
Write-Host "2️⃣  Derniers logs de l'application:" -ForegroundColor Yellow
Write-Host "-----------------------------------"
docker-compose logs --tail=20 app
Write-Host ""

# 3. Vérifier la connexion à la base de données
Write-Host "3️⃣  Test de connexion à la base de données:" -ForegroundColor Yellow
Write-Host "-------------------------------------------"
try {
    $dbTest = docker-compose exec -T app php artisan tinker --execute="try { DB::connection()->getPdo(); echo 'OK'; } catch (Exception `$e) { echo 'FAIL: ' . `$e->getMessage(); }" 2>&1
    Write-Host $dbTest
} catch {
    Write-Host "Erreur: $($_.Exception.Message)" -ForegroundColor Red
}
Write-Host ""

# 4. Vérifier les migrations
Write-Host "4️⃣  État des migrations:" -ForegroundColor Yellow
Write-Host "----------------------"
docker-compose exec -T app php artisan migrate:status 2>&1 | Select-Object -First 15
Write-Host ""

# 5. Vérifier Redis
Write-Host "5️⃣  Test de Redis:" -ForegroundColor Yellow
Write-Host "----------------"
docker-compose exec -T redis redis-cli ping 2>&1
Write-Host ""

# 6. Vérifier Nginx
Write-Host "6️⃣  Test de Nginx dans le conteneur:" -ForegroundColor Yellow
Write-Host "-----------------------------------"
docker-compose exec -T app nginx -t 2>&1
Write-Host ""

# 7. Vérifier PHP-FPM
Write-Host "7️⃣  Test de PHP-FPM:" -ForegroundColor Yellow
Write-Host "------------------"
docker-compose exec -T app php-fpm -v 2>&1 | Select-Object -First 1
Write-Host ""

# 8. Tester la connexion HTTP depuis le conteneur
Write-Host "8️⃣  Test HTTP depuis le conteneur:" -ForegroundColor Yellow
Write-Host "---------------------------------"
docker-compose exec -T app wget -q -O - http://localhost/up 2>&1 | Select-Object -First 5
Write-Host ""

# 9. Vérifier les ports
Write-Host "9️⃣  Ports exposés:" -ForegroundColor Yellow
Write-Host "----------------"
netstat -an | Select-String -Pattern ":8000|:5173|:5432|:6379" | Select-Object -First 10
Write-Host ""

# 10. Vérifier les variables d'environnement
Write-Host "🔟 Variables d'environnement importantes:" -ForegroundColor Yellow
Write-Host "----------------------------------------"
docker-compose exec -T app printenv | Select-String -Pattern "APP_|DB_|REDIS_" | Select-Object -First 10
Write-Host ""

Write-Host "✅ Diagnostic terminé!" -ForegroundColor Green


