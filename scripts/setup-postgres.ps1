# Script PowerShell pour configurer PostgreSQL sur Windows

Write-Host "Configuration de PostgreSQL pour Laravel NetLab" -ForegroundColor Cyan
Write-Host ""

# 1. Verifier si PostgreSQL est installe
Write-Host "1. Verification de PostgreSQL..." -ForegroundColor Yellow
$psqlPath = $null

# Chercher psql dans les emplacements courants
$possiblePaths = @(
    "C:\Program Files\PostgreSQL\*\bin\psql.exe",
    "C:\Program Files (x86)\PostgreSQL\*\bin\psql.exe",
    "$env:ProgramFiles\PostgreSQL\*\bin\psql.exe",
    "$env:ProgramFiles(x86)\PostgreSQL\*\bin\psql.exe"
)

foreach ($path in $possiblePaths) {
    $found = Get-ChildItem -Path $path -ErrorAction SilentlyContinue | Select-Object -First 1
    if ($found) {
        $psqlPath = $found.FullName
        break
    }
}

# Verifier dans le PATH
if (-not $psqlPath) {
    try {
        $null = Get-Command psql -ErrorAction Stop
        $psqlPath = "psql"
        Write-Host "   [OK] PostgreSQL trouve dans le PATH" -ForegroundColor Green
    } catch {
        Write-Host "   [ERREUR] PostgreSQL n'est pas installe ou n'est pas dans le PATH" -ForegroundColor Red
        Write-Host ""
        Write-Host "   Pour installer PostgreSQL sur Windows :" -ForegroundColor Yellow
        Write-Host "   1. Telechargez depuis : https://www.postgresql.org/download/windows/" -ForegroundColor White
        Write-Host "   2. Ou utilisez Chocolatey : choco install postgresql" -ForegroundColor White
        Write-Host "   3. Ou utilisez winget : winget install PostgreSQL.PostgreSQL" -ForegroundColor White
        Write-Host ""
        Write-Host "   Apres l'installation, relancez ce script." -ForegroundColor Yellow
        exit 1
    }
} else {
    Write-Host "   [OK] PostgreSQL trouve : $psqlPath" -ForegroundColor Green
}

Write-Host ""

# 2. Verifier l'extension PHP pgsql
Write-Host "2. Verification de l'extension PHP pgsql..." -ForegroundColor Yellow
$phpModules = php -m 2>&1
if ($phpModules -match "pdo_pgsql") {
    Write-Host "   [OK] Extension PHP pgsql disponible" -ForegroundColor Green
} else {
    Write-Host "   [ATTENTION] Extension PHP pgsql non trouvee" -ForegroundColor Yellow
    Write-Host "   Verifiez que l'extension est activee dans php.ini" -ForegroundColor White
}
Write-Host ""

# 3. Verifier si le service PostgreSQL est en cours d'execution
Write-Host "3. Verification du service PostgreSQL..." -ForegroundColor Yellow
$pgService = Get-Service -Name "*postgresql*" -ErrorAction SilentlyContinue | Select-Object -First 1
if ($pgService) {
    if ($pgService.Status -eq "Running") {
        Write-Host "   [OK] Service PostgreSQL en cours d'execution" -ForegroundColor Green
    } else {
        Write-Host "   [ATTENTION] Service PostgreSQL arrete. Demarrage..." -ForegroundColor Yellow
        try {
            Start-Service -Name $pgService.Name -ErrorAction Stop
            Write-Host "   [OK] Service PostgreSQL demarre" -ForegroundColor Green
        } catch {
            Write-Host "   [ERREUR] Impossible de demarrer le service. Verifiez les permissions." -ForegroundColor Red
            Write-Host "   Essayez de demarrer manuellement le service PostgreSQL" -ForegroundColor Yellow
        }
    }
} else {
    Write-Host "   [ATTENTION] Service PostgreSQL non trouve" -ForegroundColor Yellow
    Write-Host "   Assurez-vous que PostgreSQL est correctement installe" -ForegroundColor White
}
Write-Host ""

# 4. Definir les informations de connexion
$DB_NAME = "netlab"
$DB_USER = "netlab"
$DB_PASSWORD = "netlab"
$DB_HOST = "127.0.0.1"
$DB_PORT = "5432"

Write-Host "4. Configuration de la base de donnees..." -ForegroundColor Yellow
Write-Host "   Base de donnees : $DB_NAME" -ForegroundColor White
Write-Host "   Utilisateur : $DB_USER" -ForegroundColor White
Write-Host "   Hote : $DB_HOST" -ForegroundColor White
Write-Host "   Port : $DB_PORT" -ForegroundColor White
Write-Host ""

# 5. Creer l'utilisateur et la base de donnees
Write-Host "5. Creation de l'utilisateur et de la base de donnees..." -ForegroundColor Yellow

if ($psqlPath) {
    try {
        # Essayer de creer l'utilisateur
        $createUserCmd = "CREATE USER $DB_USER WITH PASSWORD '$DB_PASSWORD';"
        $result = & $psqlPath -U postgres -d postgres -c $createUserCmd 2>&1
        if ($LASTEXITCODE -eq 0) {
            Write-Host "   [OK] Utilisateur cree" -ForegroundColor Green
        } else {
            # L'utilisateur existe peut-etre deja
            Write-Host "   [INFO] Utilisateur existe deja ou erreur (peut etre normal)" -ForegroundColor Yellow
        }
    } catch {
        Write-Host "   [ATTENTION] Impossible de creer l'utilisateur automatiquement" -ForegroundColor Yellow
        Write-Host "   Vous devrez le creer manuellement avec psql" -ForegroundColor White
    }

    try {
        # Creer la base de donnees
        $createDbCmd = "CREATE DATABASE $DB_NAME OWNER $DB_USER;"
        $result = & $psqlPath -U postgres -d postgres -c $createDbCmd 2>&1
        if ($LASTEXITCODE -eq 0) {
            Write-Host "   [OK] Base de donnees creee" -ForegroundColor Green
        } else {
            # La base existe peut-etre deja
            $checkDb = & $psqlPath -U postgres -d postgres -t -c "SELECT 1 FROM pg_database WHERE datname='$DB_NAME';" 2>&1
            if ($checkDb -match "1") {
                Write-Host "   [OK] Base de donnees existe deja" -ForegroundColor Green
            } else {
                Write-Host "   [ATTENTION] Erreur lors de la creation de la base de donnees" -ForegroundColor Yellow
            }
        }
    } catch {
        Write-Host "   [ATTENTION] Impossible de creer la base de donnees automatiquement" -ForegroundColor Yellow
        Write-Host "   Creez-la manuellement avec :" -ForegroundColor White
        Write-Host "   psql -U postgres -c `"CREATE DATABASE $DB_NAME OWNER $DB_USER`"" -ForegroundColor Cyan
    }
} else {
    Write-Host "   [ERREUR] psql non trouve, impossible de creer la base de donnees" -ForegroundColor Red
}

Write-Host ""

# 6. Mettre a jour le fichier .env
Write-Host "6. Mise a jour du fichier .env..." -ForegroundColor Yellow

if (-not (Test-Path .env)) {
    Write-Host "   [ATTENTION] Fichier .env non trouve" -ForegroundColor Yellow
    if (Test-Path .env.example) {
        Copy-Item .env.example .env
        Write-Host "   [OK] .env cree depuis .env.example" -ForegroundColor Green
    } else {
        Write-Host "   [ERREUR] .env.example non trouve" -ForegroundColor Red
        exit 1
    }
}

# Sauvegarder le fichier .env
Copy-Item .env .env.backup -ErrorAction SilentlyContinue

# Lire le contenu du fichier .env
$envContent = Get-Content .env

# Fonction pour mettre a jour ou ajouter une variable
function Update-EnvVariable {
    param(
        [string]$Name,
        [string]$Value,
        [string[]]$Content
    )
    
    $found = $false
    $newContent = @()
    
    foreach ($line in $Content) {
        if ($line -match "^$Name=") {
            $newContent += "$Name=$Value"
            $found = $true
        } else {
            $newContent += $line
        }
    }
    
    if (-not $found) {
        $newContent += "$Name=$Value"
    }
    
    return $newContent
}

# Mettre a jour les variables
$envContent = Update-EnvVariable -Name "DB_CONNECTION" -Value "pgsql" -Content $envContent
$envContent = Update-EnvVariable -Name "DB_HOST" -Value $DB_HOST -Content $envContent
$envContent = Update-EnvVariable -Name "DB_PORT" -Value $DB_PORT -Content $envContent
$envContent = Update-EnvVariable -Name "DB_DATABASE" -Value $DB_NAME -Content $envContent
$envContent = Update-EnvVariable -Name "DB_USERNAME" -Value $DB_USER -Content $envContent
$envContent = Update-EnvVariable -Name "DB_PASSWORD" -Value $DB_PASSWORD -Content $envContent

# Ecrire le nouveau contenu
$envContent | Set-Content .env

Write-Host "   [OK] Fichier .env mis a jour" -ForegroundColor Green
Write-Host "   [INFO] Configuration sauvegardee dans .env.backup" -ForegroundColor Cyan
Write-Host ""

# 7. Tester la connexion
Write-Host "7. Test de la connexion a la base de donnees..." -ForegroundColor Yellow
php artisan config:clear | Out-Null

try {
    $testResult = php artisan db:show 2>&1
    if ($LASTEXITCODE -eq 0) {
        Write-Host "   [OK] Connexion reussie !" -ForegroundColor Green
    } else {
        Write-Host "   [ATTENTION] Impossible de tester la connexion automatiquement" -ForegroundColor Yellow
        Write-Host "   Testez manuellement avec : php artisan db:show" -ForegroundColor White
    }
} catch {
    Write-Host "   [ATTENTION] Impossible de tester la connexion" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "[OK] Configuration PostgreSQL terminee !" -ForegroundColor Green
Write-Host ""
Write-Host "Prochaines etapes :" -ForegroundColor Cyan
Write-Host "1. Executer les migrations : php artisan migrate" -ForegroundColor White
Write-Host "2. (Optionnel) Executer les seeders : php artisan db:seed" -ForegroundColor White
Write-Host ""
