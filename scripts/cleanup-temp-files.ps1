# Script PowerShell de nettoyage des fichiers temporaires de test

Write-Host "🧹 Nettoyage des fichiers temporaires..." -ForegroundColor Cyan

# Créer le dossier pour les tests si nécessaire
if (-not (Test-Path "scripts/tests")) {
    New-Item -ItemType Directory -Path "scripts/tests" -Force | Out-Null
}

# Déplacer les fichiers de test vers scripts/tests
Write-Host "📦 Déplacement des fichiers de test..." -ForegroundColor Yellow
Get-ChildItem -Path . -Filter "test-*.php" | ForEach-Object {
    Write-Host "  → Déplacement de $($_.Name)" -ForegroundColor Gray
    Move-Item -Path $_.FullName -Destination "scripts/tests/" -Force -ErrorAction SilentlyContinue
}

Get-ChildItem -Path . -Filter "check-*.php" | ForEach-Object {
    Write-Host "  → Déplacement de $($_.Name)" -ForegroundColor Gray
    Move-Item -Path $_.FullName -Destination "scripts/tests/" -Force -ErrorAction SilentlyContinue
}

Get-ChildItem -Path . -Filter "fix-*.php" | ForEach-Object {
    Write-Host "  → Déplacement de $($_.Name)" -ForegroundColor Gray
    Move-Item -Path $_.FullName -Destination "scripts/tests/" -Force -ErrorAction SilentlyContinue
}

Get-ChildItem -Path . -Filter "mark-*.php" | ForEach-Object {
    Write-Host "  → Déplacement de $($_.Name)" -ForegroundColor Gray
    Move-Item -Path $_.FullName -Destination "scripts/tests/" -Force -ErrorAction SilentlyContinue
}

# Supprimer les fichiers temporaires
Write-Host "🗑️  Suppression des fichiers temporaires..." -ForegroundColor Yellow
if (Test-Path "trouvés") {
    Remove-Item "trouvés" -Force -ErrorAction SilentlyContinue
}

Get-ChildItem -Path . -Filter "*.backup" | Remove-Item -Force -ErrorAction SilentlyContinue
Get-ChildItem -Path . -Filter "*.bak" | Remove-Item -Force -ErrorAction SilentlyContinue
Get-ChildItem -Path . -Filter "*.tmp" | Remove-Item -Force -ErrorAction SilentlyContinue

Write-Host "✅ Nettoyage terminé!" -ForegroundColor Green


