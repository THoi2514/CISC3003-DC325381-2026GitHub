@echo off
setlocal enabledelayedexpansion

REM =========================================================
REM UM Rental System - Database Setup Script (Windows/XAMPP)
REM Usage:
REM   setup_database.bat                    -> interactive profile, full setup
REM   setup_database.bat full               -> interactive profile, full setup
REM   setup_database.bat prod               -> interactive profile, production baseline setup
REM   setup_database.bat minimal            -> interactive profile, minimal setup
REM   setup_database.bat full root          -> full setup via root account
REM   setup_database.bat prod root          -> production baseline via root account
REM   setup_database.bat minimal root       -> minimal setup via root account
REM   setup_database.bat full um_app        -> full setup via um_app account
REM   setup_database.bat prod um_app        -> production baseline via um_app account
REM   setup_database.bat minimal um_app     -> minimal setup via um_app account
REM =========================================================

set "ROOT_DIR=%~dp0"
set "DB_DIR=%ROOT_DIR%database"
set "MYSQL_EXE="

REM Auto-detect mysql.exe without hardcoding drive letters:
REM 1) PATH
for %%I in (mysql.exe) do if not defined MYSQL_EXE set "MYSQL_EXE=%%~$PATH:I"
REM 2) Relative to project folder: <project>\..\..\mysql\bin\mysql.exe
if not defined MYSQL_EXE (
  set "CANDIDATE_MYSQL=%ROOT_DIR%..\..\mysql\bin\mysql.exe"
  if exist "!CANDIDATE_MYSQL!" set "MYSQL_EXE=!CANDIDATE_MYSQL!"
)
REM 3) Optional env var: XAMPP_HOME\mysql\bin\mysql.exe
if not defined MYSQL_EXE if defined XAMPP_HOME (
  set "CANDIDATE_MYSQL=%XAMPP_HOME%\mysql\bin\mysql.exe"
  if exist "!CANDIDATE_MYSQL!" set "MYSQL_EXE=!CANDIDATE_MYSQL!"
)

REM Defaults aligned with local config
set "DB_HOST=127.0.0.1"
set "DB_PORT=3307"
set "DB_NAME=um_rental_system"
set "PROFILE="
set "DB_USER="
set "DB_PASS="

set "MODE=full"
if /I "%~1"=="minimal" set "MODE=minimal"
if /I "%~1"=="full" set "MODE=full"
if /I "%~1"=="prod" set "MODE=prod"
if /I "%~2"=="root" set "PROFILE=root"
if /I "%~2"=="um_app" set "PROFILE=um_app"

if not exist "%MYSQL_EXE%" (
  echo [ERROR] mysql.exe not found.
  echo Tried:
  echo   - PATH lookup for mysql.exe
  echo   - relative path: ^<project^>\..\..\mysql\bin\mysql.exe
  echo   - XAMPP_HOME\mysql\bin\mysql.exe ^(if XAMPP_HOME is set^)
  echo Please install MySQL client or add mysql.exe to PATH.
  exit /b 1
)

if not defined PROFILE (
  echo.
  echo Select database profile:
  echo   1^) root   ^(root / empty password^)
  echo   2^) um_app ^(um_app / um_app_123456^)
  set /p PROFILE_CHOICE=Enter 1 or 2 [default 1]:
  if "%PROFILE_CHOICE%"=="" set "PROFILE_CHOICE=1"
  if "%PROFILE_CHOICE%"=="1" set "PROFILE=root"
  if "%PROFILE_CHOICE%"=="2" set "PROFILE=um_app"
)

if /I "%PROFILE%"=="root" (
  set "DB_USER=root"
  set "DB_PASS="
) else if /I "%PROFILE%"=="um_app" (
  set "DB_USER=um_app"
  set "DB_PASS=um_app_123456"
) else (
  echo [ERROR] Invalid profile: %PROFILE%
  echo Use profile "root" or "um_app".
  exit /b 1
)

if /I "%MODE%"=="full" (
  set "STEP1=%DB_DIR%\schema.sql"
  set "STEP2=%DB_DIR%\migration_mvp_phase1.sql"
  set "STEP3=%DB_DIR%\seed_demo_data.sql"
) else if /I "%MODE%"=="prod" (
  set "STEP1=%DB_DIR%\schema.sql"
  set "STEP2=%DB_DIR%\migration_mvp_phase1.sql"
  set "STEP3=%DB_DIR%\seed_production_baseline.sql"
) else (
  set "STEP1=%DB_DIR%\basic_schema.sql"
  set "STEP2=%DB_DIR%\basic_seed.sql"
  set "STEP3="
)

if not exist "%STEP1%" (
  echo [ERROR] Missing SQL file: %STEP1%
  exit /b 1
)
if not exist "%STEP2%" (
  echo [ERROR] Missing SQL file: %STEP2%
  exit /b 1
)
if defined STEP3 if not exist "%STEP3%" (
  echo [ERROR] Missing SQL file: %STEP3%
  exit /b 1
)

echo.
echo ==========================================
echo Database setup mode: %MODE%
echo profile: %PROFILE%
echo mysql: %MYSQL_EXE%
echo host: %DB_HOST%:%DB_PORT%
echo user: %DB_USER%
echo ==========================================
echo.

call :run_sql "%STEP1%" || exit /b 1
call :run_sql "%STEP2%" || exit /b 1
if defined STEP3 call :run_sql "%STEP3%" || exit /b 1

echo.
echo [OK] Database setup completed successfully.
exit /b 0

:run_sql
set "SQL_FILE=%~1"
echo [RUN] %SQL_FILE%

if defined DB_PASS (
  "%MYSQL_EXE%" --protocol=tcp -h "%DB_HOST%" -P %DB_PORT% -u "%DB_USER%" -p"%DB_PASS%" "%DB_NAME%" ^< "%SQL_FILE%"
) else (
  "%MYSQL_EXE%" --protocol=tcp -h "%DB_HOST%" -P %DB_PORT% -u "%DB_USER%" "%DB_NAME%" ^< "%SQL_FILE%"
)

if errorlevel 1 (
  echo [ERROR] Failed while importing: %SQL_FILE%
  exit /b 1
)
echo [OK] Imported: %SQL_FILE%
echo.
exit /b 0
