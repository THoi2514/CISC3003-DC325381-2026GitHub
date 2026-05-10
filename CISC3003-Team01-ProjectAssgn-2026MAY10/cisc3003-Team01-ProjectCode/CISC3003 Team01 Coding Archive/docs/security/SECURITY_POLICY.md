# Security Policy (Project-Level)

## Password Policy

- Minimum length: 8 characters.
- Must include both letters and numbers.
- Recommended for production: 12+ characters with mixed case and symbols.
- Passwords are stored as one-way hashes (PHP `password_hash`).

## Login / Action Throttling

- Mutating APIs are protected with CSRF token validation.
- Request rate limiting is applied using `rate_limits` table.
- Rate control key includes action + user/session + client IP.
- Repeated abusive calls are blocked temporarily.

## Role Authorization Rules

- User-only flows require `requireLogin`.
- Staff endpoints require `requireStaff`.
- Admin endpoints require `requireAdmin`.
- Staff role must not execute admin actions.

## Sensitive File Access

- `.htaccess` blocks direct web access to:
  - `.env` and `.env.*`
  - `log.txt`
  - `*.sql` and `*.md`
- Keep secrets only in environment variables in production.
