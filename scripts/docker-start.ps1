# Script PowerShell de démarrage Docker pour NetLab

Write-Host "🐳 Démarrage de NetLab avec Docker..." -ForegroundColor Cyan

# Vérifier que Docker est installé
if (-not (Get-Command docker -ErrorAction SilentlyContinue)) {
    Write-Host "❌ Docker n'est pas installé. Veuillez l'installer d'abord." -ForegroundColor Red
    exit 1
}

if (-not (Get-Command docker-compose -ErrorAction SilentlyContinue)) {
    Write-Host "❌ Docker Compose n'est pas installé. Veuillez l'installer d'abord." -ForegroundColor Red
    exit 1
}

# Vérifier si le fichier .env existe
if (-not (Test-Path .env)) {
    Write-Host "⚠️  Le fichier .env n'existe pas." -ForegroundColor Yellow
    if (Test-Path .env.example) {
        Write-Host "📋 Copie de .env.example vers .env..." -ForegroundColor Yellow
        Copy-Item .env.example .env
        Write-Host "✅ Fichier .env créé. Veuillez le configurer avant de continuer." -ForegroundColor Green
        Write-Host "   Important: Configurez DB_*, APP_KEY, et les variables CML" -ForegroundColor Yellow
        exit 1
    } else {
        Write-Host "❌ Aucun fichier .env.example trouvé. Veuillez créer un fichier .env manuellement." -ForegroundColor Red
        exit 1
    }
}

# Générer la clé d'application si elle n'existe pas
$envContent = Get-Content .env -Raw
if ($envContent -notmatch "APP_KEY=base64:") {
    Write-Host "🔑 Génération de la clé d'application..." -ForegroundColor Cyan
    docker-compose run --rm app php artisan key:generate
}

# Construire et démarrer les services
Write-Host "🏗️  Construction des images Docker..." -ForegroundColor Cyan
docker-compose build

Write-Host "🚀 Démarrage des services..." -ForegroundColor Cyan
docker-compose up -d

# Attendre que les services soient prêts
Write-Host "⏳ Attente que les services soient prêts..." -ForegroundColor Yellow
Start-Sleep -Seconds 10

# Vérifier l'état des services
Write-Host "📊 État des services:" -ForegroundColor Cyan
docker-compose ps

Write-Host ""
Write-Host "✅ NetLab est en cours de démarrage!" -ForegroundColor Green
Write-Host ""
Write-Host "📝 Commandes utiles:" -ForegroundColor Cyan
Write-Host "   - Voir les logs: docker-compose logs -f"
Write-Host "   - Arrêter: docker-compose down"
Write-Host "   - Redémarrer: docker-compose restart"
Write-Host ""
Write-Host "🌐 Accès à l'application:" -ForegroundColor Cyan
Write-Host "   - Application: http://localhost:8000"
Write-Host "   - Vite Dev: http://localhost:5173"
Write-Host ""
Write-Host "🔄 Les migrations seront exécutées automatiquement au démarrage." -ForegroundColor Green


