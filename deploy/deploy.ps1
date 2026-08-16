<#
.SYNOPSIS
    Build locale + upload via FTP (versione Windows/PowerShell di deploy.sh).

    Non esegue nulla lato server: l'estrazione e le migration vengono
    applicate dal comando Artisan `app:apply-deploy`
    (app/Console/Commands/ApplyDeploy.php), schedulato in routes/console.php
    e lanciato dal cron `php artisan schedule:run` configurato una tantum sul
    pannello di hosting.
#>

$ErrorActionPreference = 'Stop'

$Root = Split-Path -Parent $PSScriptRoot
$DeployDir = $PSScriptRoot
$Stage = Join-Path $DeployDir '.stage'
$StageArchive = Join-Path $DeployDir '.stage.tar'
$Archive = Join-Path $DeployDir 'release.zip'
$ConfigFile = Join-Path $DeployDir '.env.deploy'

if (-not (Test-Path $ConfigFile)) {
    Write-Error "Manca $ConfigFile. Copia deploy\.env.deploy.example in deploy\.env.deploy e compilalo."
}

# --- lettura config (stesso file .env.deploy usato da deploy.sh) ---
$Config = @{}
Get-Content $ConfigFile | ForEach-Object {
    $line = $_.Trim()
    if ($line -eq '' -or $line.StartsWith('#')) { return }
    if ($line -match '^([A-Za-z_][A-Za-z0-9_]*)=(.*)$') {
        $value = $Matches[2].Trim()
        if ($value -match '^"(.*)"$' -or $value -match "^'(.*)'$") { $value = $Matches[1] }
        $Config[$Matches[1]] = $value
    }
}

foreach ($key in 'FTP_HOST', 'FTP_USER', 'FTP_PASS') {
    if (-not $Config.ContainsKey($key) -or [string]::IsNullOrWhiteSpace($Config[$key])) {
        Write-Error "$key mancante in deploy\.env.deploy"
    }
}
if (-not $Config.ContainsKey('FTP_REMOTE_DIR')) {
    Write-Error "FTP_REMOTE_DIR mancante in deploy\.env.deploy (puo' essere vuoto se l'FTP e' gia' chrootato nella root dell'app)"
}

$FtpSecure = if ($Config.ContainsKey('FTP_SECURE')) { $Config['FTP_SECURE'] } else { 'true' }
$CurlFtpOpts = @()
if ($FtpSecure -eq 'true') {
    $FtpUrl = "ftps://$($Config['FTP_HOST'])"
    $CurlFtpOpts += '--ssl-reqd'
} else {
    $FtpUrl = "ftp://$($Config['FTP_HOST'])"
}
$FtpUser = $Config['FTP_USER']
$FtpPass = $Config['FTP_PASS']
$FtpRemoteDir = $Config['FTP_REMOTE_DIR']

Write-Host '==> Pulizia build precedente'
if (Test-Path $Stage) { Remove-Item $Stage -Recurse -Force }
if (Test-Path $StageArchive) { Remove-Item $StageArchive -Force }
if (Test-Path $Archive) { Remove-Item $Archive -Force }
New-Item -ItemType Directory -Path $Stage | Out-Null

Write-Host '==> Copia sorgenti (esclusi file di sviluppo/segreti)'
# Usa tar.exe (bsdtar, incluso in Windows 10+) via file intermedio, invece di
# una pipe tra due processi nativi (più affidabile su Windows PowerShell).
& tar.exe `
    --exclude='.git' `
    --exclude='node_modules' `
    --exclude='deploy' `
    --exclude='.env' `
    --exclude='.env.*' `
    --exclude='database/database.sqlite' `
    --exclude='storage/app/deploy' `
    --exclude='storage/logs' `
    --exclude='storage/framework/cache/data' `
    --exclude='storage/framework/sessions' `
    --exclude='storage/framework/views' `
    --exclude='tests' `
    -cf $StageArchive -C $Root .
if ($LASTEXITCODE -ne 0) { throw 'Creazione archivio sorgenti fallita' }

& tar.exe -xf $StageArchive -C $Stage
if ($LASTEXITCODE -ne 0) { throw 'Estrazione archivio sorgenti fallita' }
Remove-Item $StageArchive -Force

Write-Host '==> Build asset frontend'
Push-Location $Root
try {
    & npm run build
    if ($LASTEXITCODE -ne 0) { throw 'npm run build fallito' }
} finally {
    Pop-Location
}
New-Item -ItemType Directory -Path (Join-Path $Stage 'public') -Force | Out-Null
Copy-Item (Join-Path $Root 'public\build') (Join-Path $Stage 'public\build') -Recurse -Force

Write-Host '==> Installazione dipendenze PHP (solo produzione, non tocca il vendor locale)'
Push-Location $Stage
try {
    & composer install --no-dev --optimize-autoloader --no-interaction
    if ($LASTEXITCODE -ne 0) { throw 'composer install fallito' }
} finally {
    Pop-Location
}

Write-Host '==> Creazione archivio'
& php (Join-Path $DeployDir 'build-zip.php') $Stage $Archive
if ($LASTEXITCODE -ne 0) { throw 'Creazione release.zip fallita' }

Write-Host '==> Upload archivio via FTP'
& curl.exe -sS --ftp-create-dirs @CurlFtpOpts `
    -u "${FtpUser}:${FtpPass}" `
    -T $Archive `
    "${FtpUrl}${FtpRemoteDir}/storage/app/deploy/release.zip"
if ($LASTEXITCODE -ne 0) { throw 'Upload di release.zip fallito' }

Write-Host '==> Upload trigger (fa partire l''estrazione al prossimo giro dello scheduler Laravel)'
$TriggerFile = Join-Path $DeployDir '.trigger.tmp'
New-Item -ItemType File -Path $TriggerFile -Force | Out-Null
& curl.exe -sS --ftp-create-dirs @CurlFtpOpts `
    -u "${FtpUser}:${FtpPass}" `
    -T $TriggerFile `
    "${FtpUrl}${FtpRemoteDir}/storage/app/deploy/deploy.trigger"
if ($LASTEXITCODE -ne 0) { throw 'Upload del trigger fallito' }
Remove-Item $TriggerFile -Force

Write-Host "==> Fatto. Verra' applicato entro 5 minuti da 'php artisan schedule:run' (controlla storage/logs sul server)."
