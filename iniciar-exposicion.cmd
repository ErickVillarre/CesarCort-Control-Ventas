@echo off
powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%~dp0iniciar-exposicion.ps1"
if errorlevel 1 (
    echo.
    echo No se pudo iniciar la exposicion. Comunica el error mostrado arriba.
    pause
)
