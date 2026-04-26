@echo off
setlocal

for /f "delims=" %%I in ('where node.exe 2^>nul') do (
    set "NODE_EXE=%%I"
    goto :found_node
)

if exist "%ProgramFiles%\nodejs\node.exe" set "NODE_EXE=%ProgramFiles%\nodejs\node.exe"
if not defined NODE_EXE if exist "%ProgramFiles(x86)%\nodejs\node.exe" set "NODE_EXE=%ProgramFiles(x86)%\nodejs\node.exe"
if not defined NODE_EXE if exist "%LocalAppData%\Programs\nodejs\node.exe" set "NODE_EXE=%LocalAppData%\Programs\nodejs\node.exe"

:found_node
if not defined NODE_EXE (
    echo node.exe could not be found. Install Node.js or add node.exe to PATH.
    exit /b 1
)

for /f "delims=" %%I in ('where npm.cmd 2^>nul') do (
    set "NPM_CMD=%%I"
    goto :found_npm
)

:found_npm
if not defined NPM_CMD (
    echo npm.cmd could not be found. Install Node.js or add npm.cmd to PATH.
    exit /b 1
)

if not exist "%~dp0node_modules\vite\bin\vite.js" (
    call "%NPM_CMD%" install --production=false
    if errorlevel 1 exit /b %errorlevel%
)

call "%NODE_EXE%" "%~dp0node_modules\vite\bin\vite.js" %*
exit /b %errorlevel%
