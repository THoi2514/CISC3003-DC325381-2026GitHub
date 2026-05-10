# Documentation Index

Central entry for **product documentation**, **operations**, and **engineering references**.  
Use this page to find what you need before diving into code.

---

## How documentation is organized

| Area | Folder | Purpose |
|------|--------|---------|
| Project & requirements | `docs/project/` | Course briefs, PRM, formal reports |
| Local development | `docs/development/` | Frontend modules (Flutter + React), multi-repo startup |
| Specifications | `docs/specs/` | API contracts, behavior matrices |
| Security | `docs/security/` | Auth, secrets, rate limits, sensitive files |
| Testing | `docs/testing/` | Manual QA checklists, regression scope |
| Deployment | `docs/deployment/` | Cloud / production runbooks |
| Database | `database/README.md` | Schema modes, seeds, demo reset (stays with SQL) |
| Session log | `log.txt` (repo root) | Implementation / session notes |

---

## Project & requirements

- `docs/project/PRM.md` — Product requirements: scope, team, goals, stack (Web Programming course context).
- `docs/project/CISC3026_PROJECT_REPORT.md` — Formal design document (requirements, UML, test cases; large report).

## Development (local)

- `docs/development/A_B_SETUP.md` — **A** Flutter app + **B** React admin dashboard: install, run, API base URLs, QR format.

## Specifications

- `docs/specs/API_OVERVIEW.md` — API action matrix (`action`, `method`, `permission`, `return`).

## Security

- `docs/security/SECURITY_POLICY.md` — Passwords, rate limiting, roles, handling of sensitive paths.

## Testing

- `docs/testing/TEST_CHECKLIST.md` — Functional, exception, and permission tests.

## Setup & deployment

- `database/README.md` — DB modes: `full`, `minimal`, `prod`; demo reset workflow.
- `docs/deployment/DEPLOY_GCP.md` — Google Cloud Run + Cloud SQL deploy guide.
- `docs/deployment/INFINITYFREE.md` — Free PHP + MySQL hosting (InfinityFree): FTP, phpMyAdmin, `schema_shared_hosting.sql`, env vars.

---

## Repository layout (application code)

High-level map of **where code lives** (no doc moves required—this is the canonical layout).

| Path | Role |
|------|------|
| `api/` | PHP JSON/REST endpoints consumed by web, admin SPA, and Flutter |
| `includes/` | Shared PHP: DB, auth, headers/footers, OAuth config |
| `config/`, `config.php` | Connection and app configuration |
| `assets/` | Shared CSS/JS for PHP-rendered pages |
| Root `*.php` | Public site: landing, login, dashboard, account, services, etc. |
| `admin/` | Admin PHP portal (`admin_dashboard.php`, guards) |
| `staff/` | Staff PHP portal |
| `admin_dashboard/` | React (Vite) operations UI |
| `frontend_flutter/` | Flutter mobile client (scan, map) |
| `database/` | `schema.sql`, migrations, seeds, procedures |

Supporting files at repo root: `.htaccess`, `Dockerfile`, `.env.example`, `setup_database.bat`, batch helpers.

---

## Suggested reading order

1. Root `README.md` — quick start and env vars  
2. This file (`docs/README.md`)  
3. `database/README.md` — choose DB mode  
4. `docs/development/A_B_SETUP.md` — if you work on Flutter or React admin  
5. `docs/specs/API_OVERVIEW.md` — integrate or extend APIs  
6. `docs/testing/TEST_CHECKLIST.md` — before release or demo  
7. `docs/security/SECURITY_POLICY.md` — before exposing new endpoints  
