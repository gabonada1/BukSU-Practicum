@echo off
setlocal

if not exist "%~dp0node_modules\.bin\vite.cmd" (
    call npm install --production=false
    if errorlevel 1 exit /b %errorlevel%
)

call "%~dp0node_modules\.bin\vite.cmd" %*
exit /b %errorlevel%
