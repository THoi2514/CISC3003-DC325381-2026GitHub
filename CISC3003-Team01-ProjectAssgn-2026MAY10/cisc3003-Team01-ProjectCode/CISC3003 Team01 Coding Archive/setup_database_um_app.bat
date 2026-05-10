@echo off
setlocal enabledelayedexpansion

REM =========================================================
REM Compatibility wrapper (preferred: setup_database.bat)
REM This script forwards to setup_database.bat with um_app profile.
REM =========================================================

set "ROOT_DIR=%~dp0"
set "MAIN_SCRIPT=%ROOT_DIR%setup_database.bat"

if not exist "%MAIN_SCRIPT%" (
  echo [ERROR] Missing script: %MAIN_SCRIPT%
  exit /b 1
)

set "MODE=full"
if /I "%~1"=="minimal" set "MODE=minimal"
if /I "%~1"=="full" set "MODE=full"
if /I "%~1"=="prod" set "MODE=prod"

call "%MAIN_SCRIPT%" %MODE% um_app
exit /b %errorlevel%
