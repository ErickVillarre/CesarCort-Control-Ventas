param()

$ErrorActionPreference = 'Stop'
$project = (Resolve-Path -LiteralPath $PSScriptRoot).Path
$backend = Join-Path $project 'backend'
$frontend = Join-Path $project 'frontend'
$php = 'C:\xampp\php\php.exe'
$mysqlStart = 'C:\xampp\mysql_start.bat'
$chrome = 'C:\Program Files\Google\Chrome\Application\chrome.exe'
$vite = Join-Path $frontend 'node_modules\vite\bin\vite.js'
$node = (Get-Command node.exe -ErrorAction Stop).Source
$logs = Join-Path $env:LOCALAPPDATA 'CesarControl\exposicion\logs'

function Test-TcpPort([int]$port) {
    $client = New-Object System.Net.Sockets.TcpClient
    try {
        $pending = $client.BeginConnect('127.0.0.1', $port, $null, $null)
        if (-not $pending.AsyncWaitHandle.WaitOne(1000)) { return $false }
        $client.EndConnect($pending)
        return $true
    } catch {
        return $false
    } finally {
        $client.Dispose()
    }
}

function Wait-TcpPort([int]$port, [int]$seconds) {
    for ($attempt = 0; $attempt -lt $seconds; $attempt++) {
        if (Test-TcpPort $port) { return }
        Start-Sleep -Seconds 1
    }
    throw "El puerto $port no respondio. Revisa los registros de $logs."
}

function Wait-PortClosed([int]$port, [int]$seconds) {
    for ($attempt = 0; $attempt -lt $seconds; $attempt++) {
        if (-not (Test-TcpPort $port)) { return }
        Start-Sleep -Seconds 1
    }
    throw "El puerto $port siguio ocupado y no se inicio otro servidor."
}

function Get-ProjectListener([int]$port, [string]$expectedPath) {
    $listener = Get-NetTCPConnection -State Listen -LocalPort $port -ErrorAction SilentlyContinue | Select-Object -First 1
    if ($null -eq $listener) { return $null }
    $process = Get-CimInstance Win32_Process -Filter "ProcessId = $($listener.OwningProcess)"
    if ($null -eq $process -or $process.CommandLine -notmatch [regex]::Escape($expectedPath)) {
        throw "El puerto $port esta ocupado por otro programa. No se detuvo ningun proceso."
    }
    return $process
}

function Stop-ProjectBackend($listenerProcess) {
    $middle = Get-CimInstance Win32_Process -Filter "ProcessId = $($listenerProcess.ParentProcessId)"
    if ($null -eq $middle) {
        throw 'No se pudo verificar el proceso padre de Laravel. No se detuvo ningun proceso.'
    }
    $launcher = Get-CimInstance Win32_Process -Filter "ProcessId = $($middle.ParentProcessId)"
    $validTree = $middle.Name -eq 'cmd.exe'
    $validTree = $validTree -and $middle.CommandLine -match [regex]::Escape($backend)
    $validTree = $validTree -and $middle.CommandLine -match '-S\s+127\.0\.0\.1:8000'
    $validTree = $validTree -and $null -ne $launcher
    $validTree = $validTree -and $launcher.Name -eq 'php.exe'
    $validTree = $validTree -and $launcher.CommandLine -match 'artisan\s+serve'
    $validTree = $validTree -and $launcher.CommandLine -match '--port=8000'
    if (-not $validTree) {
        throw 'El puerto 8000 no tiene el arbol esperado de este Laravel. No se detuvo ningun proceso.'
    }

    foreach ($processId in @($launcher.ProcessId, $middle.ProcessId, $listenerProcess.ProcessId)) {
        Stop-Process -Id $processId -Force -ErrorAction SilentlyContinue
    }
    Wait-PortClosed 8000 10
}

foreach ($required in @($backend, $frontend, $php, $mysqlStart, $chrome, $vite)) {
    if (-not (Test-Path -LiteralPath $required)) {
        throw "Falta un archivo necesario: $required"
    }
}
if (Test-Path -LiteralPath (Join-Path $backend 'bootstrap\cache\config.php')) {
    throw 'Laravel tiene configuracion cacheada y podria ignorar la base demo. Ejecuta php artisan config:clear antes de iniciar.'
}
New-Item -ItemType Directory -Path $logs -Force | Out-Null

if (-not (Test-TcpPort 3306)) {
    Write-Host 'Iniciando MySQL de XAMPP...'
    Start-Process -FilePath $mysqlStart -WorkingDirectory 'C:\xampp' -WindowStyle Hidden
    Wait-TcpPort 3306 25
}
Write-Host 'MySQL: activo.'

$existingBackend = Get-ProjectListener 8000 $backend
if ($null -ne $existingBackend) {
    Write-Host 'Reiniciando Laravel del proyecto con MySQL de exposicion...'
    Stop-ProjectBackend $existingBackend
}
$settings = @{
    DB_CONNECTION = 'mysql'
    DB_HOST = '127.0.0.1'
    DB_PORT = '3306'
    DB_DATABASE = 'cesarcontrol_apf3_expo'
    DB_USERNAME = 'root'
    APP_URL = 'http://127.0.0.1:8000'
    APP_DEBUG = 'false'
    APF3_DEMO_SEED = 'false'
    CORS_ALLOWED_ORIGINS = 'http://127.0.0.1:5173,http://localhost:5173'
}
$previous = @{}
foreach ($key in $settings.Keys) {
    $previous[$key] = [Environment]::GetEnvironmentVariable($key, 'Process')
    [Environment]::SetEnvironmentVariable($key, $settings[$key], 'Process')
}
try {
    Start-Process -FilePath $php -ArgumentList @('artisan', 'serve', '--host=127.0.0.1', '--port=8000') -WorkingDirectory $backend -WindowStyle Hidden -RedirectStandardOutput (Join-Path $logs 'backend.out.log') -RedirectStandardError (Join-Path $logs 'backend.err.log') | Out-Null
} finally {
    foreach ($key in $settings.Keys) {
        [Environment]::SetEnvironmentVariable($key, $previous[$key], 'Process')
    }
}
Wait-TcpPort 8000 25

$existingFrontend = Get-ProjectListener 5173 $frontend
if ($null -eq $existingFrontend) {
    Start-Process -FilePath $node -ArgumentList @($vite, '--host', '127.0.0.1', '--port', '5173', '--strictPort') -WorkingDirectory $frontend -WindowStyle Hidden -RedirectStandardOutput (Join-Path $logs 'frontend.out.log') -RedirectStandardError (Join-Path $logs 'frontend.err.log') | Out-Null
    Wait-TcpPort 5173 25
}

$api = Invoke-WebRequest -Uri 'http://127.0.0.1:8000/api/health' -UseBasicParsing -TimeoutSec 5
$crm = Invoke-WebRequest -Uri 'http://127.0.0.1:5173/login' -UseBasicParsing -TimeoutSec 5
if ($api.StatusCode -ne 200 -or $crm.StatusCode -ne 200) {
    throw "La API o el CRM no respondieron correctamente. Revisa $logs."
}

Write-Host 'API y CRM: activos. Abriendo login en Chrome...'
& $chrome --incognito --new-window 'http://127.0.0.1:5173/login'
Write-Host 'Exposicion lista: http://127.0.0.1:5173/login'
