# Deploy to Google Cloud Run (with Cloud SQL MySQL)

This guide deploys the current PHP project from GitHub/local source to Cloud Run.

## 1) Prerequisites

- Google Cloud project created
- Billing enabled
- `gcloud` CLI installed and logged in
- Cloud SQL (MySQL) instance created

## 2) Required values

Set these in your shell before deploy:

```bash
PROJECT_ID="your-gcp-project-id"
REGION="asia-east1"
SERVICE_NAME="um-rental-web"
INSTANCE_CONNECTION_NAME="your-project:asia-east1:um-rental-mysql"
DB_NAME="um_rental_system"
DB_USER="um_app"
DB_PASS="your-strong-password"
```

## 3) Enable required APIs

```bash
gcloud services enable run.googleapis.com cloudbuild.googleapis.com sqladmin.googleapis.com --project "$PROJECT_ID"
```

## 4) Build and deploy

From project root (where `Dockerfile` is):

```bash
gcloud run deploy "$SERVICE_NAME" \
  --source . \
  --project "$PROJECT_ID" \
  --region "$REGION" \
  --allow-unauthenticated \
  --add-cloudsql-instances "$INSTANCE_CONNECTION_NAME" \
  --set-env-vars "DB_SOCKET=/cloudsql/$INSTANCE_CONNECTION_NAME,DB_NAME=$DB_NAME,DB_USER=$DB_USER,DB_PASS=$DB_PASS"
```

## 5) Initialize database schema

Use Cloud SQL query editor or local mysql client to import:

1. `database/schema.sql`
2. `database/migration_mvp_phase1.sql`
3. `database/seed_demo_data.sql` (optional for demo)

## 6) Verify

- Open Cloud Run service URL
- Test:
  - `/index.php`
  - `/login.php`
  - `/dashboard.php`

## Notes

- App now supports Cloud SQL Unix socket via `DB_SOCKET`.
- Local development keeps working with existing defaults/fallbacks.
- For production, use Secret Manager for `DB_PASS` instead of plain env vars.
