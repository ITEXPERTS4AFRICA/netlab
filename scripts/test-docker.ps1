# Script PowerShell de test pour la configuration Docker

Write-Host "🧪 Test de la configuration Docker pour NetLab" -ForegroundColor Cyan
Write-Host "==============================================" -ForegroundColor Cyan
Write-Host ""

$allTestsPassed = $true

# Fonction pour vérifier
function Test-Check {
    param([string]$Name, [bool]$Result)
    
    if ($Result) {
        Write-Host "✅ $Name" -ForegroundColor Green
        return $true
    } else {
        Write-Host "❌ $Name" -ForegroundColor Red
        return $false
    }
}

# 1. Vérifier Docker
Write-Host "1. Vérification de Docker..." -ForegroundColor Yellow
try {
    $dockerVersion = docker --version 2>&1
    if ($LASTEXITCODE -eq 0) {
        Test-Check "Docker est installé" $true | Out-Null
        Write-Host "   Version: $dockerVersion" -ForegroundColor Gray
    } else {
        $allTestsPassed = $false
        Test-Check "Docker est installé" $false | Out-Null
    }
} catch {
    $allTestsPassed = $false
    Test-Check "Docker est installé" $false | Out-Null
}

try {
    $composeVersion = docker-compose --version 2>&1
    if ($LASTEXITCODE -eq 0) {
        Test-Check "Docker Compose est installé" $true | Out-Null
        Write-Host "   Version: $composeVersion" -ForegroundColor Gray
    } else {
        $allTestsPassed = $false
        Test-Check "Docker Compose est installé" $false | Out-Null
    }
} catch {
    $allTestsPassed = $false
    Test-Check "Docker Compose est installé" $false | Out-Null
}

# 2. Vérifier les fichiers Docker
Write-Host ""
Write-Host "2. Vérification des fichiers Docker..." -ForegroundColor Yellow

$files = @(
    @{Path="Dockerfile"; Required=$true},
    @{Path="Dockerfile.node"; Required=$false},
    @{Path="docker-compose.yml"; Required=$true},
    @{Path=".dockerignore"; Required=$false}
)

foreach ($file in $files) {
    if (Test-Path $file.Path) {
        Test-Check "$($file.Path) existe" $true | Out-Null
    } else {
        if ($file.Required) {
            $allTestsPassed = $false
            Test-Check "$($file.Path) existe" $false | Out-Null
        } else {
            Write-Host "⚠️  $($file.Path) manquant (optionnel)" -ForegroundColor Yellow
        }
    }
}

# 3. Vérifier les fichiers de configuration
Write-Host ""
Write-Host "3. Vérification des fichiers de configuration..." -ForegroundColor Yellow

$configFiles = @(
    "docker/nginx/default.conf",
    "docker/php/php.ini",
    "docker/php/www.conf",
    "docker/supervisor/supervisord.conf",
    "docker/entrypoint.sh"
)

foreach ($configFile in $configFiles) {
    if (Test-Path $configFile) {
        Test-Check "$configFile existe" $true | Out-Null
    } else {
        $allTestsPassed = $false
        Test-Check "$configFile existe" $false | Out-Null
    }
}

# 4. Vérifier la syntaxe du docker-compose.yml
Write-Host ""
Write-Host "4. Vérification de la syntaxe docker-compose.yml..." -ForegroundColor Yellow
try {
    docker-compose config | Out-Null
    if ($LASTEXITCODE -eq 0) {
        Test-Check "Syntaxe docker-compose.yml valide" $true | Out-Null
    } else {
        $allTestsPassed = $false
        Test-Check "Syntaxe docker-compose.yml valide" $false | Out-Null
    }
} catch {
    $allTestsPassed = $false
    Test-Check "Syntaxe docker-compose.yml valide" $false | Out-Null
}

# 5. Test de build (optionnel)
Write-Host ""
$buildTest = Read-Host "5. Voulez-vous tester le build des images? (cela peut prendre plusieurs minutes) [y/N]"
if ($buildTest -eq "y" -or $buildTest -eq "Y") {
    Write-Host "🔨 Construction de l'image app..." -ForegroundColor Yellow
    docker-compose build app 2>&1 | Out-Null
    if ($LASTEXITCODE -eq 0) {
        Test-Check "Build de l'image app réussi" $true | Out-Null
    } else {
        $allTestsPassed = $false
        Test-Check "Build de l'image app réussi" $false | Out-Null
    }
    
    Write-Host "🔨 Construction de l'image node..." -ForegroundColor Yellow
    docker-compose build node 2>&1 | Out-Null
    if ($LASTEXITCODE -eq 0) {
        Test-Check "Build de l'image node réussi" $true | Out-Null
    } else {
        $allTestsPassed = $false
        Test-Check "Build de l'image node réussi" $false | Out-Null
    }
}

# Résumé
Write-Host ""
Write-Host "==============================================" -ForegroundColor Cyan
if ($allTestsPassed) {
    Write-Host "✅ Tous les tests sont passés!" -ForegroundColor Green
} else {
    Write-Host "⚠️  Certains tests ont échoué. Vérifiez les erreurs ci-dessus." -ForegroundColor Yellow
}
Write-Host ""
Write-Host 'Pour démarrer NetLab avec Docker:' -ForegroundColor Cyan
Write-Host '  docker-compose up -d' -ForegroundColor White
Write-Host ''
Write-Host 'Pour voir les logs:' -ForegroundColor Cyan
Write-Host '  docker-compose logs -f' -ForegroundColor White
Write-Host ''

