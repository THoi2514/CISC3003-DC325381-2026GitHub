# Database Setup Guide

Use this page to choose the correct database mode.

## Mode A: Full Demo (Recommended for Development)

- Purpose: local development + feature demo
- Command:
  - `setup_database.bat full root`
  - `setup_database.bat full um_app`
  - `setup_database_um_app.bat full`
  - or `setup_database.bat` (interactive)
- SQL flow:
  1. `database/schema.sql`
  2. `database/migration_mvp_phase1.sql`
  3. `database/seed_demo_data.sql`

## Mode B: Production Baseline (No Demo Accounts)

- Purpose: production-like environment
- Command:
  - `setup_database.bat prod root`
  - `setup_database.bat prod um_app`
  - `setup_database_um_app.bat prod`
- SQL flow:
  1. `database/schema.sql`
  2. `database/migration_mvp_phase1.sql`
  3. `database/seed_production_baseline.sql`

## Mode C: Minimal Local

- Purpose: quick lightweight local run
- Command:
  - `setup_database.bat minimal root`
  - `setup_database.bat minimal um_app`
  - `setup_database_um_app.bat minimal`
- SQL flow:
  1. `database/basic_schema.sql`
  2. `database/basic_seed.sql`

## Demo Reset (Before Presentation)

- Run: `database/reset_demo_state.sql`
- Effect: clear transactional/demo runtime data and restore baseline demo state.

## Shared hosting (e.g. InfinityFree)

- Use **`database/schema_shared_hosting.sql`** in phpMyAdmin after you create a database in the control panel (no `CREATE DATABASE` in file).
- Deploy steps: **`docs/deployment/INFINITYFREE.md`**.

## Notes

- `setup_database_um_app.bat` is a compatibility wrapper.
- `setup_database_um_app.bat` supports: `full`, `minimal`, `prod`.
- `setup_database.bat` does not hardcode `C:` / `D:`. It auto-detects `mysql.exe` from:
  1. System `PATH`
  2. Relative path from project (`..\..\mysql\bin\mysql.exe`)
  3. `%XAMPP_HOME%\mysql\bin\mysql.exe` (if `XAMPP_HOME` is set)
- `database/core_schema.sql` is deprecated and should not be used.
