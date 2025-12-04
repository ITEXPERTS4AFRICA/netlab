# Script PowerShell de réorganisation du code NetLab

Write-Host "🔄 Réorganisation du code NetLab..." -ForegroundColor Cyan
Write-Host "======================================" -ForegroundColor Cyan
Write-Host ""

# Créer les dossiers nécessaires
Write-Host "📁 Création des dossiers..." -ForegroundColor Yellow
$directories = @(
    "scripts/tests",
    "scripts/maintenance",
    "docs/root-docs",
    "scripts/utilities"
)

foreach ($dir in $directories) {
    if (-not (Test-Path $dir)) {
        New-Item -ItemType Directory -Path $dir -Force | Out-Null
        Write-Host "  ✓ Créé: $dir" -ForegroundColor Gray
    }
}

# 1. Déplacer les fichiers de test temporaires
Write-Host ""
Write-Host "1. Déplacement des fichiers de test..." -ForegroundColor Yellow
$testFiles = Get-ChildItem -Path . -Filter "test-*.php" -File
foreach ($file in $testFiles) {
    Write-Host "  → $($file.Name) → scripts/tests/" -ForegroundColor Gray
    Move-Item -Path $file.FullName -Destination "scripts/tests/" -Force -ErrorAction SilentlyContinue
}

$checkFiles = Get-ChildItem -Path . -Filter "check-*.php" -File
foreach ($file in $checkFiles) {
    Write-Host "  → $($file.Name) → scripts/tests/" -ForegroundColor Gray
    Move-Item -Path $file.FullName -Destination "scripts/tests/" -Force -ErrorAction SilentlyContinue
}

$fixFiles = Get-ChildItem -Path . -Filter "fix-*.php" -File
foreach ($file in $fixFiles) {
    Write-Host "  → $($file.Name) → scripts/maintenance/" -ForegroundColor Gray
    Move-Item -Path $file.FullName -Destination "scripts/maintenance/" -Force -ErrorAction SilentlyContinue
}

$markFiles = Get-ChildItem -Path . -Filter "mark-*.php" -File
foreach ($file in $markFiles) {
    Write-Host "  → $($file.Name) → scripts/maintenance/" -ForegroundColor Gray
    Move-Item -Path $file.FullName -Destination "scripts/maintenance/" -Force -ErrorAction SilentlyContinue
}

# 2. Déplacer les fichiers de documentation de la racine
Write-Host ""
Write-Host "2. Déplacement de la documentation..." -ForegroundColor Yellow
$excludedDocs = @("README.md", "DOCKER.md", "TEST-DOCKER.md")
$docFiles = Get-ChildItem -Path . -Filter "*.md" -File | Where-Object { $excludedDocs -notcontains $_.Name }

foreach ($file in $docFiles) {
    Write-Host "  → $($file.Name) → docs/root-docs/" -ForegroundColor Gray
    Move-Item -Path $file.FullName -Destination "docs/root-docs/" -Force -ErrorAction SilentlyContinue
}

# 3. Supprimer les fichiers temporaires
Write-Host ""
Write-Host "3. Suppression des fichiers temporaires..." -ForegroundColor Yellow
$tempFiles = @("trouvés", "trouves")
foreach ($tempFile in $tempFiles) {
    if (Test-Path $tempFile) {
        Remove-Item $tempFile -Force -ErrorAction SilentlyContinue
        Write-Host "  ✓ Supprimé: $tempFile" -ForegroundColor Gray
    }
}

Get-ChildItem -Path . -Filter "*.backup" | Remove-Item -Force -ErrorAction SilentlyContinue
Get-ChildItem -Path . -Filter "*.bak" | Remove-Item -Force -ErrorAction SilentlyContinue
Get-ChildItem -Path . -Filter "*.tmp" | Remove-Item -Force -ErrorAction SilentlyContinue

# 4. Déplacer cinetpay-php-sdk-master vers vendor ou scripts si nécessaire
Write-Host ""
Write-Host "4. Vérification des dépendances externes..." -ForegroundColor Yellow
if (Test-Path "cinetpay-php-sdk-master") {
    Write-Host "  ⚠️  cinetpay-php-sdk-master trouvé (à intégrer dans composer si possible)" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "✅ Réorganisation terminée!" -ForegroundColor Green
Write-Host ""
Write-Host "Structure organisée:" -ForegroundColor Cyan
Write-Host '  - scripts/tests/          : Fichiers de test temporaires' -ForegroundColor White
Write-Host '  - scripts/maintenance/    : Scripts de maintenance' -ForegroundColor White
Write-Host '  - docs/root-docs/         : Documentation de la racine' -ForegroundColor White
Write-Host ''

