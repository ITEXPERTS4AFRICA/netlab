# Script d'initialisation complète de NetLab avec Docker

Write-Host "🚀 Initialisation de NetLab avec Docker" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Vérifier que Docker est en cours d'exécution
Write-Host "1. Vérification de Docker..." -ForegroundColor Yellow
try {
    docker ps | Out-Null
    if ($LASTEXITCODE -ne 0) {
        Write-Host "   ❌ Docker n'est pas en cours d'exécution" -ForegroundColor Red
        Write-Host "   Démarrez Docker Desktop et réessayez" -ForegroundColor Yellow
        exit 1
    }
    Write-Host "   ✅ Docker fonctionne" -ForegroundColor Green
} catch {
    Write-Host "   ❌ Docker n'est pas disponible" -ForegroundColor Red
    exit 1
}

# Démarrer les services
Write-Host ""
Write-Host "2. Démarrage des services..." -ForegroundColor Yellow
docker-compose up -d
if ($LASTEXITCODE -ne 0) {
    Write-Host "   ❌ Erreur lors du démarrage" -ForegroundColor Red
    exit 1
}
Write-Host "   ✅ Services démarrés" -ForegroundColor Green

# Attendre que les services soient prêts
Write-Host ""
Write-Host "3. Attente que les services soient prêts..." -ForegroundColor Yellow
Start-Sleep -Seconds 10

# Vérifier le fichier .env
Write-Host ""
Write-Host "4. Vérification du fichier .env..." -ForegroundColor Yellow
if (-not (Test-Path ".env")) {
    if (Test-Path ".env.example") {
        Write-Host "   📝 Création du fichier .env depuis .env.example..." -ForegroundColor Gray
        Copy-Item ".env.example" ".env"
        Write-Host "   ✅ Fichier .env créé" -ForegroundColor Green
        Write-Host "   ⚠️  N'oubliez pas de configurer les variables dans .env" -ForegroundColor Yellow
    } else {
        Write-Host "   ⚠️  Fichier .env.example non trouvé" -ForegroundColor Yellow
    }
} else {
    Write-Host "   ✅ Fichier .env existe" -ForegroundColor Green
}

# Générer la clé d'application
Write-Host ""
Write-Host "5. Génération de la clé d'application..." -ForegroundColor Yellow
docker-compose exec -T app php artisan key:generate 2>&1 | Out-Null
if ($LASTEXITCODE -eq 0) {
    Write-Host "   ✅ Clé d'application générée" -ForegroundColor Green
} else {
    Write-Host "   ⚠️  La clé existe peut-être déjà" -ForegroundColor Yellow
}

# Exécuter les migrations
Write-Host ""
Write-Host "6. Exécution des migrations..." -ForegroundColor Yellow
docker-compose exec -T app php artisan migrate --force 2>&1 | Out-Host
if ($LASTEXITCODE -eq 0) {
    Write-Host "   ✅ Migrations exécutées" -ForegroundColor Green
} else {
    Write-Host "   ⚠️  Erreur lors des migrations (peut être normal si déjà exécutées)" -ForegroundColor Yellow
}

# Créer l'utilisateur admin
Write-Host ""
Write-Host "7. Création de l'utilisateur admin..." -ForegroundColor Yellow
docker-compose exec -T app php artisan db:seed --class=AdminUserSeeder 2>&1 | Out-Host
if ($LASTEXITCODE -eq 0) {
    Write-Host "   ✅ Utilisateur admin créé" -ForegroundColor Green
} else {
    Write-Host "   ⚠️  Erreur lors de la création (peut être normal si déjà créé)" -ForegroundColor Yellow
}

# Créer le lien symbolique
Write-Host ""
Write-Host "8. Création du lien symbolique pour le stockage..." -ForegroundColor Yellow
docker-compose exec -T app php artisan storage:link 2>&1 | Out-Null
if ($LASTEXITCODE -eq 0) {
    Write-Host "   ✅ Lien symbolique créé" -ForegroundColor Green
} else {
    Write-Host "   ⚠️  Le lien existe peut-être déjà" -ForegroundColor Yellow
}

# Résumé
Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "✅ Initialisation terminée!" -ForegroundColor Green
Write-Host ""
Write-Host "🌐 Application accessible sur:" -ForegroundColor Cyan
Write-Host "   http://localhost:8000" -ForegroundColor White
Write-Host ""
Write-Host "👤 Identifiants admin par défaut:" -ForegroundColor Cyan
Write-Host "   Email: admin@netlab.local" -ForegroundColor White
Write-Host "   Mot de passe: password" -ForegroundColor White
Write-Host ""
Write-Host "📋 Commandes utiles:" -ForegroundColor Cyan
Write-Host "   docker-compose logs -f        # Voir les logs" -ForegroundColor White
Write-Host "   docker-compose ps             # État des services" -ForegroundColor White
Write-Host "   docker-compose down           # Arrêter" -ForegroundColor White
Write-Host ""


