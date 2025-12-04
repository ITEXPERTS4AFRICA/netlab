# Test rapide de santé de l'application NetLab
# Utilise Invoke-WebRequest (PowerShell) au lieu de curl

$BaseUrl = "http://localhost:8000"
$Passed = 0
$Failed = 0

Write-Host "🔍 Test rapide de santé de NetLab" -ForegroundColor Cyan
Write-Host "=================================" -ForegroundColor Cyan
Write-Host ""

# Test 1: Page d'accueil
Write-Host -NoNewline "Test 1: Page d'accueil ($BaseUrl) ... "
try {
    $response = Invoke-WebRequest -Uri $BaseUrl -UseBasicParsing -TimeoutSec 5 -ErrorAction Stop
    if ($response.StatusCode -eq 200) {
        Write-Host "✓ OK" -ForegroundColor Green -NoNewline
        Write-Host " (HTTP $($response.StatusCode))"
        $Passed++
    } else {
        Write-Host "✗ ÉCHEC" -ForegroundColor Red -NoNewline
        Write-Host " (HTTP $($response.StatusCode))"
        $Failed++
    }
} catch {
    Write-Host "✗ ERREUR" -ForegroundColor Red -NoNewline
    Write-Host " ($($_.Exception.Message))"
    $Failed++
}

# Test 2: Route de santé Laravel
Write-Host -NoNewline "Test 2: Route de santé (/up) ... "
try {
    $response = Invoke-WebRequest -Uri "$BaseUrl/up" -UseBasicParsing -TimeoutSec 5 -ErrorAction Stop
    if ($response.StatusCode -eq 200) {
        Write-Host "✓ OK" -ForegroundColor Green -NoNewline
        Write-Host " (HTTP $($response.StatusCode))"
        $Passed++
    } else {
        Write-Host "✗ ÉCHEC" -ForegroundColor Red -NoNewline
        Write-Host " (HTTP $($response.StatusCode))"
        $Failed++
    }
} catch {
    Write-Host "✗ ERREUR" -ForegroundColor Red -NoNewline
    Write-Host " ($($_.Exception.Message))"
    $Failed++
}

# Test 3: Page de connexion
Write-Host -NoNewline "Test 3: Page de connexion (/login) ... "
try {
    $response = Invoke-WebRequest -Uri "$BaseUrl/login" -UseBasicParsing -TimeoutSec 5 -ErrorAction Stop
    if ($response.StatusCode -eq 200) {
        Write-Host "✓ OK" -ForegroundColor Green -NoNewline
        Write-Host " (HTTP $($response.StatusCode))"
        $Passed++
    } else {
        Write-Host "✗ ÉCHEC" -ForegroundColor Red -NoNewline
        Write-Host " (HTTP $($response.StatusCode))"
        $Failed++
    }
} catch {
    Write-Host "✗ ERREUR" -ForegroundColor Red -NoNewline
    Write-Host " ($($_.Exception.Message))"
    $Failed++
}

# Test 4: Services Docker
Write-Host ""
Write-Host "🐳 Vérification des services Docker:" -ForegroundColor Cyan
$services = @("netlab_app", "netlab_postgres", "netgres", "netlab_redis")
foreach ($service in $services) {
    Write-Host -NoNewline "  - $service ... "
    $container = docker ps --format "{{.Names}}" | Select-String "^${service}$"
    if ($container) {
        $status = docker inspect --format='{{.State.Status}}' $service 2>&1
        if ($status -match "running") {
            Write-Host "✓ Running" -ForegroundColor Green
            $Passed++
        } else {
            Write-Host "⚠ $status" -ForegroundColor Yellow
            $Failed++
        }
    } else {
        Write-Host "✗ Arrêté" -ForegroundColor Red
        $Failed++
    }
}

# Test 5: Redis
Write-Host ""
Write-Host -NoNewline "Test 5: Redis ... "
try {
    $redisTest = docker-compose exec -T redis redis-cli ping 2>&1
    if ($redisTest -match "PONG") {
        Write-Host "✓ OK" -ForegroundColor Green
        $Passed++
    } else {
        Write-Host "✗ ÉCHEC" -ForegroundColor Red
        $Failed++
    }
} catch {
    Write-Host "✗ ERREUR" -ForegroundColor Red
    $Failed++
}

# Résumé
Write-Host ""
Write-Host "================================" -ForegroundColor Cyan
Write-Host "📊 Résumé" -ForegroundColor Cyan
Write-Host "================================" -ForegroundColor Cyan
Write-Host "Tests réussis: " -NoNewline
Write-Host $Passed -ForegroundColor Green
Write-Host "Tests échoués: " -NoNewline
Write-Host $Failed -ForegroundColor Red
Write-Host ""

if ($Failed -eq 0) {
    Write-Host "✅ Application opérationnelle!" -ForegroundColor Green
} else {
    Write-Host "⚠️  Certains tests ont échoué." -ForegroundColor Yellow
}


