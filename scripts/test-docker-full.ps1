# Script complet de test Docker pour NetLab

Write-Host "🧪 Test complet de la configuration Docker" -ForegroundColor Cyan
Write-Host "=========================================" -ForegroundColor Cyan
Write-Host ""

$allTestsPassed = $true
$errors = @()

# Fonction pour tester
function Test-Check {
    param([string]$Name, [scriptblock]$Test, [bool]$Required = $true)
    
    try {
        $result = & $Test
        if ($result -or -not $Required) {
            Write-Host "✅ $Name" -ForegroundColor Green
            return $true
        } else {
            Write-Host "❌ $Name" -ForegroundColor Red
            $script:allTestsPassed = $false
            $script:errors += $Name
            return $false
        }
    } catch {
        if ($Required) {
            Write-Host "❌ $Name" -ForegroundColor Red
            Write-Host "   Erreur: $($_.Exception.Message)" -ForegroundColor Yellow
            $script:allTestsPassed = $false
            $script:errors += $Name
            return $false
        } else {
            Write-Host "⚠️  $Name (optionnel)" -ForegroundColor Yellow
            return $true
        }
    }
}

# 1. Vérifier Docker
Write-Host "1. Vérification de Docker..." -ForegroundColor Yellow
$dockerVersion = Test-Check "Docker installé" {
    $version = docker --version 2>&1
    if ($LASTEXITCODE -eq 0) {
        Write-Host "   Version: $version" -ForegroundColor Gray
        return $true
    }
    return $false
}

$composeVersion = Test-Check "Docker Compose installé" {
    $version = docker-compose --version 2>&1
    if ($LASTEXITCODE -eq 0) {
        Write-Host "   Version: $version" -ForegroundColor Gray
        return $true
    }
    return $false
}

# 2. Vérifier que Docker fonctionne
Write-Host ""
Write-Host "2. Vérification du fonctionnement de Docker..." -ForegroundColor Yellow
Test-Check "Docker daemon actif" {
    docker ps 2>&1 | Out-Null
    return $LASTEXITCODE -eq 0
}

# 3. Vérifier les fichiers Docker
Write-Host ""
Write-Host "3. Vérification des fichiers Docker..." -ForegroundColor Yellow
Test-Check "Dockerfile existe" { Test-Path "Dockerfile" }
Test-Check "Dockerfile.node existe" { Test-Path "Dockerfile.node" }
Test-Check "docker-compose.yml existe" { Test-Path "docker-compose.yml" }
Test-Check ".dockerignore existe" { Test-Path ".dockerignore" }

# 4. Vérifier les fichiers de configuration
Write-Host ""
Write-Host "4. Vérification des fichiers de configuration..." -ForegroundColor Yellow
Test-Check "Configuration Nginx" { Test-Path "docker/nginx/default.conf" }
Test-Check "Configuration PHP" { Test-Path "docker/php/php.ini" }
Test-Check "Configuration PHP-FPM" { Test-Path "docker/php/www.conf" }
Test-Check "Configuration Supervisor" { Test-Path "docker/supervisor/supervisord.conf" }
Test-Check "Script entrypoint" { Test-Path "docker/entrypoint.sh" }

# 5. Vérifier la syntaxe docker-compose.yml
Write-Host ""
Write-Host "5. Vérification de la syntaxe..." -ForegroundColor Yellow
Test-Check "Syntaxe docker-compose.yml valide" {
    docker-compose config --quiet 2>&1 | Out-Null
    return $LASTEXITCODE -eq 0
}

# 6. Test de build (optionnel)
Write-Host ""
Write-Host "6. Test de construction des images..." -ForegroundColor Yellow
$buildTest = Read-Host "Voulez-vous tester le build des images? (cela peut prendre plusieurs minutes) [y/N]"
if ($buildTest -eq "y" -or $buildTest -eq "Y") {
    Write-Host ""
    Write-Host "🔨 Construction de l'image app..." -ForegroundColor Cyan
    Test-Check "Build de l'image app" {
        docker-compose build app 2>&1 | Tee-Object -Variable buildOutput
        if ($LASTEXITCODE -eq 0) {
            Write-Host "   ✅ Image app construite avec succès" -ForegroundColor Green
            return $true
        } else {
            Write-Host "   ❌ Erreur lors du build" -ForegroundColor Red
            Write-Host $buildOutput -ForegroundColor Yellow
            return $false
        }
    }
    
    Write-Host ""
    Write-Host "🔨 Construction de l'image node..." -ForegroundColor Cyan
    Test-Check "Build de l'image node" {
        docker-compose build node 2>&1 | Tee-Object -Variable buildOutput
        if ($LASTEXITCODE -eq 0) {
            Write-Host "   ✅ Image node construite avec succès" -ForegroundColor Green
            return $true
        } else {
            Write-Host "   ❌ Erreur lors du build" -ForegroundColor Red
            Write-Host $buildOutput -ForegroundColor Yellow
            return $false
        }
    }
} else {
    Write-Host "⏭️  Test de build ignoré" -ForegroundColor Gray
}

# 7. Test de démarrage (optionnel)
Write-Host ""
Write-Host "7. Test de démarrage des services..." -ForegroundColor Yellow
$startTest = Read-Host "Voulez-vous tester le démarrage des services? [y/N]"
if ($startTest -eq "y" -or $startTest -eq "Y") {
    Write-Host ""
    Write-Host "🚀 Démarrage des services..." -ForegroundColor Cyan
    
    # Arrêter d'abord s'ils sont déjà en cours
    docker-compose down 2>&1 | Out-Null
    
    # Démarrer
    Write-Host "   Démarrage en cours..." -ForegroundColor Gray
    docker-compose up -d 2>&1 | Tee-Object -Variable startOutput
    
    if ($LASTEXITCODE -eq 0) {
        Write-Host "   ✅ Services démarrés" -ForegroundColor Green
        
        # Attendre un peu
        Start-Sleep -Seconds 5
        
        # Vérifier l'état
        Write-Host ""
        Write-Host "📊 État des services:" -ForegroundColor Cyan
        docker-compose ps
        
        Write-Host ""
        Write-Host "📋 Logs des services:" -ForegroundColor Cyan
        Write-Host "   (Afficher avec: docker-compose logs -f)" -ForegroundColor Gray
        
        Write-Host ""
        $stopTest = Read-Host "Voulez-vous arrêter les services maintenant? [Y/n]"
        if ($stopTest -ne "n" -and $stopTest -ne "N") {
            Write-Host "🛑 Arrêt des services..." -ForegroundColor Cyan
            docker-compose down 2>&1 | Out-Null
            Write-Host "   ✅ Services arrêtés" -ForegroundColor Green
        } else {
            Write-Host "   ℹ️  Services toujours actifs. Utilisez 'docker-compose down' pour les arrêter." -ForegroundColor Yellow
        }
    } else {
        Write-Host "   ❌ Erreur lors du démarrage" -ForegroundColor Red
        Write-Host $startOutput -ForegroundColor Yellow
        $allTestsPassed = $false
    }
} else {
    Write-Host "⏭️  Test de démarrage ignoré" -ForegroundColor Gray
}

# Résumé
Write-Host ""
Write-Host "=========================================" -ForegroundColor Cyan
if ($allTestsPassed) {
    Write-Host "✅ Tous les tests sont passés!" -ForegroundColor Green
    Write-Host ""
    Write-Host "🚀 Pour démarrer NetLab:" -ForegroundColor Cyan
    Write-Host "   docker-compose up -d" -ForegroundColor White
    Write-Host ""
    Write-Host "📋 Pour voir les logs:" -ForegroundColor Cyan
    Write-Host "   docker-compose logs -f" -ForegroundColor White
    Write-Host ""
    Write-Host "🛑 Pour arrêter:" -ForegroundColor Cyan
    Write-Host "   docker-compose down" -ForegroundColor White
} else {
    Write-Host "⚠️  Certains tests ont échoué:" -ForegroundColor Yellow
    foreach ($error in $errors) {
        Write-Host "   - $error" -ForegroundColor Red
    }
    Write-Host ""
    Write-Host "Vérifiez les erreurs ci-dessus et corrigez-les." -ForegroundColor Yellow
}
Write-Host ""


