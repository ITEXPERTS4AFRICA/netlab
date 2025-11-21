# Script pour demarrer PostgreSQL sur Windows

Write-Host "Demarrage de PostgreSQL..." -ForegroundColor Cyan
Write-Host ""

# Methode 1: Chercher et demarrer le service Windows
$services = Get-Service | Where-Object { 
    $_.DisplayName -like "*PostgreSQL*" -or 
    $_.Name -like "*postgres*" -or
    $_.Name -like "*pg_*"
}

if ($services) {
    foreach ($service in $services) {
        Write-Host "Service trouve : $($service.Name) - $($service.DisplayName)" -ForegroundColor Yellow
        if ($service.Status -ne "Running") {
            try {
                Write-Host "Demarrage du service..." -ForegroundColor Yellow
                Start-Service -Name $service.Name -ErrorAction Stop
                Write-Host "[OK] Service demarre : $($service.Name)" -ForegroundColor Green
            } catch {
                Write-Host "[ERREUR] Impossible de demarrer le service : $_" -ForegroundColor Red
                Write-Host "Essayez de demarrer manuellement avec des privileges administrateur" -ForegroundColor Yellow
            }
        } else {
            Write-Host "[OK] Service deja en cours d'execution : $($service.Name)" -ForegroundColor Green
        }
    }
} else {
    Write-Host "[ATTENTION] Aucun service PostgreSQL trouve" -ForegroundColor Yellow
    Write-Host ""
    Write-Host "Essayez de demarrer PostgreSQL manuellement :" -ForegroundColor Cyan
    Write-Host "1. Ouvrez le Gestionnaire de services Windows (services.msc)" -ForegroundColor White
    Write-Host "2. Cherchez un service nomme 'postgresql-x64-XX' ou similaire" -ForegroundColor White
    Write-Host "3. Clic droit > Demarrer" -ForegroundColor White
    Write-Host ""
    Write-Host "Ou utilisez pg_ctl si vous connaissez le repertoire de donnees :" -ForegroundColor Cyan
    Write-Host '   & "C:\Program Files\PostgreSQL\18\bin\pg_ctl.exe" start -D "C:\chemin\vers\data"' -ForegroundColor White
}

Write-Host ""
Write-Host "Verification de la connexion..." -ForegroundColor Yellow
Start-Sleep -Seconds 2

$psqlPath = "C:\Program Files\PostgreSQL\18\bin\psql.exe"
if (Test-Path $psqlPath) {
    $result = & $psqlPath -U postgres -d postgres -c "SELECT version();" 2>&1
    if ($LASTEXITCODE -eq 0) {
        Write-Host "[OK] PostgreSQL est accessible !" -ForegroundColor Green
    } else {
        Write-Host "[ERREUR] PostgreSQL n'est toujours pas accessible" -ForegroundColor Red
        Write-Host $result -ForegroundColor Yellow
    }
} else {
    Write-Host "[ATTENTION] psql.exe non trouve" -ForegroundColor Yellow
}

