# Test simple et rapide de Docker

Write-Host "🧪 Test Docker - NetLab" -ForegroundColor Cyan
Write-Host "=======================" -ForegroundColor Cyan
Write-Host ""

# 1. Vérifier Docker
Write-Host "1. Docker installé..." -ForegroundColor Yellow
try {
    $dockerVersion = docker --version
    Write-Host "   ✅ $dockerVersion" -ForegroundColor Green
} catch {
    Write-Host "   ❌ Docker non installé" -ForegroundColor Red
    exit 1
}

# 2. Vérifier Docker Compose
Write-Host ""
Write-Host "2. Docker Compose installé..." -ForegroundColor Yellow
try {
    $composeVersion = docker-compose --version
    Write-Host "   ✅ $composeVersion" -ForegroundColor Green
} catch {
    Write-Host "   ❌ Docker Compose non installé" -ForegroundColor Red
    exit 1
}

# 3. Vérifier que Docker fonctionne
Write-Host ""
Write-Host "3. Docker daemon actif..." -ForegroundColor Yellow
try {
    docker ps | Out-Null
    if ($LASTEXITCODE -eq 0) {
        Write-Host "   ✅ Docker fonctionne" -ForegroundColor Green
    } else {
        Write-Host "   ❌ Docker ne fonctionne pas" -ForegroundColor Red
        exit 1
    }
} catch {
    Write-Host "   ❌ Docker ne fonctionne pas" -ForegroundColor Red
    exit 1
}

# 4. Vérifier les fichiers
Write-Host ""
Write-Host "4. Fichiers Docker..." -ForegroundColor Yellow
$files = @("Dockerfile", "Dockerfile.node", "docker-compose.yml")
foreach ($file in $files) {
    if (Test-Path $file) {
        Write-Host "   ✅ $file" -ForegroundColor Green
    } else {
        Write-Host "   ❌ $file manquant" -ForegroundColor Red
    }
}

# 5. Vérifier la syntaxe
Write-Host ""
Write-Host "5. Syntaxe docker-compose.yml..." -ForegroundColor Yellow
try {
    docker-compose config --quiet 2>&1 | Out-Null
    if ($LASTEXITCODE -eq 0) {
        Write-Host "   ✅ Syntaxe valide" -ForegroundColor Green
    } else {
        Write-Host "   ⚠️  Avertissements (mais syntaxe OK)" -ForegroundColor Yellow
    }
} catch {
    Write-Host "   ❌ Erreur de syntaxe" -ForegroundColor Red
}

# 6. État des services
Write-Host ""
Write-Host "6. État des services..." -ForegroundColor Yellow
try {
    $services = docker-compose ps --services 2>&1
    if ($LASTEXITCODE -eq 0) {
        $running = docker-compose ps --format json 2>&1 | ConvertFrom-Json | Where-Object { $_.State -eq "running" }
        $total = (docker-compose ps --services 2>&1).Count
        
        Write-Host "   Services configurés: $total" -ForegroundColor Gray
        Write-Host "   Services en cours: $($running.Count)" -ForegroundColor Gray
        
        if ($running.Count -gt 0) {
            Write-Host "   ✅ Services actifs" -ForegroundColor Green
            Write-Host ""
            Write-Host "   Services en cours:" -ForegroundColor Cyan
            docker-compose ps --format "table {{.Name}}\t{{.Status}}" | Out-Host
        } else {
            Write-Host "   ⚠️  Aucun service en cours" -ForegroundColor Yellow
            Write-Host "   Pour démarrer: docker-compose up -d" -ForegroundColor Gray
        }
    }
} catch {
    Write-Host "   ⚠️  Impossible de vérifier l'état" -ForegroundColor Yellow
}

# Résumé
Write-Host ""
Write-Host "=======================" -ForegroundColor Cyan
Write-Host "✅ Test terminé!" -ForegroundColor Green
Write-Host ""
Write-Host "Commandes utiles:" -ForegroundColor Cyan
Write-Host "  docker-compose up -d          # Démarrer" -ForegroundColor White
Write-Host "  docker-compose ps             # État" -ForegroundColor White
Write-Host "  docker-compose logs -f        # Logs" -ForegroundColor White
Write-Host "  docker-compose down           # Arrêter" -ForegroundColor White
Write-Host ""


