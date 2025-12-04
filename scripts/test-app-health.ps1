# Script PowerShell de test de santé de l'application NetLab
# Vérifie que tous les services sont opérationnels

param(
    [string]$BaseUrl = "http://localhost:8000",
    [string]$ApiUrl = "http://localhost:8000/api"
)

$ErrorActionPreference = "Continue"

Write-Host "🔍 Test de santé de NetLab" -ForegroundColor Cyan
Write-Host "================================" -ForegroundColor Cyan
Write-Host "URL de base: $BaseUrl"
Write-Host ""

$Passed = 0
$Failed = 0

# Fonction pour tester un endpoint
function Test-Endpoint {
    param(
        [string]$Method,
        [string]$Url,
        [int]$ExpectedStatus,
        [string]$Description,
        [string]$Data = $null
    )
    
    Write-Host -NoNewline "Test: $Description ... "
    
    try {
        $headers = @{
            "Accept" = "application/json"
        }
        
        if ($Method -eq "POST" -and $Data) {
            $headers["Content-Type"] = "application/json"
            $response = Invoke-WebRequest -Uri $Url -Method $Method -Headers $headers -Body $Data -UseBasicParsing -ErrorAction Stop
        } else {
            $response = Invoke-WebRequest -Uri $Url -Method $Method -Headers $headers -UseBasicParsing -ErrorAction Stop
        }
        
        $httpCode = $response.StatusCode
        
        if ($httpCode -eq $ExpectedStatus -or $httpCode -eq 200 -or $httpCode -eq 302) {
            Write-Host "✓ OK" -ForegroundColor Green -NoNewline
            Write-Host " (HTTP $httpCode)"
            $script:Passed++
            return $true
        } else {
            Write-Host "✗ ÉCHEC" -ForegroundColor Red -NoNewline
            Write-Host " (HTTP $httpCode)"
            $script:Failed++
            return $false
        }
    } catch {
        $statusCode = $_.Exception.Response.StatusCode.value__
        if ($statusCode -eq $ExpectedStatus -or $statusCode -eq 200 -or $statusCode -eq 302) {
            Write-Host "✓ OK" -ForegroundColor Green -NoNewline
            Write-Host " (HTTP $statusCode)"
            $script:Passed++
            return $true
        } else {
            Write-Host "✗ ÉCHEC" -ForegroundColor Red -NoNewline
            Write-Host " (HTTP $statusCode)"
            if ($_.Exception.Message) {
                Write-Host "  Erreur: $($_.Exception.Message)" -ForegroundColor Yellow
            }
            $script:Failed++
            return $false
        }
    }
}

# 1. Test de la page d'accueil
Write-Host "📄 Tests des pages web" -ForegroundColor Cyan
Write-Host "----------------------" -ForegroundColor Cyan
Test-Endpoint -Method "GET" -Url $BaseUrl -ExpectedStatus 200 -Description "Page d'accueil"

# 2. Test de la route de santé Laravel
Test-Endpoint -Method "GET" -Url "$BaseUrl/up" -ExpectedStatus 200 -Description "Route de santé Laravel (/up)"

# 3. Test de la page de connexion
Test-Endpoint -Method "GET" -Url "$BaseUrl/login" -ExpectedStatus 200 -Description "Page de connexion"

# 4. Test de la page d'inscription
Test-Endpoint -Method "GET" -Url "$BaseUrl/register" -ExpectedStatus 200 -Description "Page d'inscription"

# 5. Test des routes API (sans authentification)
Write-Host ""
Write-Host "🔌 Tests des routes API publiques" -ForegroundColor Cyan
Write-Host "----------------------------------" -ForegroundColor Cyan
try {
    Test-Endpoint -Method "GET" -Url "$ApiUrl/console/ping" -ExpectedStatus 200 -Description "API Console Ping"
} catch {
    Write-Host "  ⚠️  Route console supprimée (normal)" -ForegroundColor Yellow
}

# 6. Test de la connexion à la base de données
Write-Host ""
Write-Host "🗄️  Tests de la base de données" -ForegroundColor Cyan
Write-Host "-------------------------------" -ForegroundColor Cyan
Write-Host -NoNewline "Test: Connexion à la base de données ... "
try {
    $dbTest = docker-compose exec -T app php artisan tinker --execute="echo DB::connection()->getPdo() ? 'OK' : 'FAIL';" 2>&1
    if ($dbTest -match "OK") {
        Write-Host "✓ OK" -ForegroundColor Green
        $script:Passed++
    } else {
        Write-Host "⚠ INCONNU" -ForegroundColor Yellow -NoNewline
        Write-Host " (vérifiez manuellement)"
        $script:Failed++
    }
} catch {
    Write-Host "⚠ INCONNU" -ForegroundColor Yellow -NoNewline
    Write-Host " (vérifiez manuellement)"
    $script:Failed++
}

# 7. Test de Redis
Write-Host ""
Write-Host "💾 Tests de Redis" -ForegroundColor Cyan
Write-Host "-----------------" -ForegroundColor Cyan
Write-Host -NoNewline "Test: Connexion à Redis ... "
try {
    $redisTest = docker-compose exec -T redis redis-cli ping 2>&1
    if ($redisTest -match "PONG") {
        Write-Host "✓ OK" -ForegroundColor Green
        $script:Passed++
    } else {
        Write-Host "✗ ÉCHEC" -ForegroundColor Red
        $script:Failed++
    }
} catch {
    Write-Host "✗ ÉCHEC" -ForegroundColor Red
    $script:Failed++
}

# 8. Test des services Docker
Write-Host ""
Write-Host "🐳 Tests des services Docker" -ForegroundColor Cyan
Write-Host "----------------------------" -ForegroundColor Cyan
$services = @("netlab_app", "netlab_postgres", "netlab_redis", "netlab_node", "netlab_queue", "netlab_scheduler")

foreach ($service in $services) {
    Write-Host -NoNewline "Test: Service $service ... "
    try {
        $container = docker ps --format "{{.Names}}" | Select-String "^${service}$"
        if ($container) {
            $status = docker inspect --format='{{.State.Status}}' $service 2>&1
            if ($status -match "running") {
                Write-Host "✓ OK" -ForegroundColor Green -NoNewline
                Write-Host " (running)"
                $script:Passed++
            } else {
                Write-Host "⚠ $status" -ForegroundColor Yellow
                $script:Failed++
            }
        } else {
            Write-Host "✗ ARRÊTÉ" -ForegroundColor Red
            $script:Failed++
        }
    } catch {
        Write-Host "✗ ERREUR" -ForegroundColor Red
        $script:Failed++
    }
}

# 9. Test des migrations
Write-Host ""
Write-Host "🔄 Tests des migrations" -ForegroundColor Cyan
Write-Host "----------------------" -ForegroundColor Cyan
Write-Host -NoNewline "Test: État des migrations ... "
try {
    $migrationOutput = docker-compose exec -T app php artisan migrate:status 2>&1
    $migrationCount = ($migrationOutput | Select-String "Ran" | Measure-Object).Count
    if ($migrationCount -gt 0) {
        Write-Host "✓ OK" -ForegroundColor Green -NoNewline
        Write-Host " ($migrationCount migrations trouvées)"
        $script:Passed++
    } else {
        Write-Host "⚠ Aucune migration trouvée" -ForegroundColor Yellow
        $script:Failed++
    }
} catch {
    Write-Host "⚠ INCONNU" -ForegroundColor Yellow
    $script:Failed++
}

# 10. Test de Vite (si disponible)
Write-Host ""
Write-Host "⚡ Tests de Vite" -ForegroundColor Cyan
Write-Host "---------------" -ForegroundColor Cyan
Write-Host -NoNewline "Test: Serveur Vite ... "
try {
    $viteResponse = Invoke-WebRequest -Uri "http://localhost:5173" -UseBasicParsing -TimeoutSec 2 -ErrorAction Stop
    Write-Host "✓ OK" -ForegroundColor Green
    $script:Passed++
} catch {
    Write-Host "⚠ Non disponible" -ForegroundColor Yellow -NoNewline
    Write-Host " (normal si pas en dev)"
}

# Résumé
Write-Host ""
Write-Host "================================" -ForegroundColor Cyan
Write-Host "📊 Résumé des tests" -ForegroundColor Cyan
Write-Host "================================" -ForegroundColor Cyan
Write-Host "Tests réussis: " -NoNewline
Write-Host $Passed -ForegroundColor Green
Write-Host "Tests échoués: " -NoNewline
Write-Host $Failed -ForegroundColor Red
Write-Host ""

if ($Failed -eq 0) {
    Write-Host "✅ Tous les tests sont passés!" -ForegroundColor Green
    exit 0
} else {
    Write-Host "⚠️  Certains tests ont échoué. Vérifiez les détails ci-dessus." -ForegroundColor Yellow
    exit 1
}


