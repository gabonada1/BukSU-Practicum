@echo off
setlocal

if defined npm_node_execpath if exist "%npm_node_execpath%" set "NODE_EXE=%npm_node_execpath%"

if defined NODE_EXE goto :found_node

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

if defined npm_execpath set "NPM_CLI=%npm_execpath%"

if defined NPM_CLI goto :found_npm

for /f "delims=" %%I in ('where npm.cmd 2^>nul') do (
    set "NPM_CMD=%%I"
    goto :found_npm
)

if exist "%ProgramFiles%\nodejs\npm.cmd" set "NPM_CMD=%ProgramFiles%\nodejs\npm.cmd"
if not defined NPM_CMD if exist "%ProgramFiles(x86)%\nodejs\npm.cmd" set "NPM_CMD=%ProgramFiles(x86)%\nodejs\npm.cmd"
if not defined NPM_CMD if exist "%LocalAppData%\Programs\nodejs\npm.cmd" set "NPM_CMD=%LocalAppData%\Programs\nodejs\npm.cmd"

:found_npm
if not defined NPM_CLI if not defined NPM_CMD (
    echo npm could not be found. Install Node.js or add npm to PATH.
    exit /b 1
)

if not exist "%~dp0node_modules\vite\bin\vite.js" (
    if defined NPM_CLI (
        call "%NODE_EXE%" "%NPM_CLI%" install --production=false
    ) else (
        call "%NPM_CMD%" install --production=false
    )
    if errorlevel 1 exit /b %errorlevel%
)

call "%NODE_EXE%" "%~dp0node_modules\vite\bin\vite.js" %*
exit /b %errorlevel%
