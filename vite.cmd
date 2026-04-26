@echo off
setlocal

for /f "delims=" %%I in ('where npm.cmd 2^>nul') do (
    set "NPM_DIR=%%~dpI"
    goto :found_npm
)

:found_npm
if defined NPM_DIR set "PATH=%NPM_DIR%;%PATH%"

if not exist "%~dp0node_modules\.bin\vite.cmd" (
    call npm install --production=false
    if errorlevel 1 exit /b %errorlevel%
)

call "%~dp0node_modules\.bin\vite.cmd" %*
exit /b %errorlevel%
