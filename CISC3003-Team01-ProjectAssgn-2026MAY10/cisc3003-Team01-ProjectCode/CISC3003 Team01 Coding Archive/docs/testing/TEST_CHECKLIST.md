# Test Checklist

## Functional Tests

- Login with student/staff/admin accounts routes to correct dashboard.
- `start_rental` creates active order and updates vehicle status to `rented`.
- `end_rental` closes active order, computes fee, updates wallet and vehicle station.
- Dashboard map renders station markers with current language labels.
- Staff bicycle add/remove flows update list immediately.
- Staff student add/update/remove and CSV import/export run successfully.
- Admin force-close order updates order status and vehicle status.

## Exception Tests

- Start rental with invalid `vehicleID` returns 422.
- Start rental with active order returns error key `errUserHasActiveOrder`.
- End rental without active order returns error key `errNoActiveOrder`.
- Return to inactive/full station returns translated popup message.
- Staff add bicycle with duplicate serial number returns 409.
- CSV import with malformed rows reports row-level errors and continues import.

## Permission Tests

- Unauthenticated request to protected action returns auth error.
- Student cannot access any `staff_*` or `admin_*` action.
- Staff can access `staff_*` actions but not `admin_*`.
- Admin can access `admin_*` actions only through admin role.
- CSRF validation blocks mutating requests without valid token.
- Rate limit blocks abusive repeated action calls.
