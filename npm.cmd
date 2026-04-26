@echo off
setlocal

if /I "%~1"=="install" exit /b 0

if /I "%~1"=="run" (
    if /I "%~2"=="build" exit /b 0
)

echo Project-local npm shim only supports install and run build during tenant updates.
exit /b 1
