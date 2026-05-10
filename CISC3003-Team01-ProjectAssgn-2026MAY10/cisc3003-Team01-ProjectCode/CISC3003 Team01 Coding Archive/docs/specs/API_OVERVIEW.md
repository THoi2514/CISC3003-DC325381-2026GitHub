# API Overview

Base endpoint: `rental_action.php?action=<action_name>`

## User Actions

- `get_catalog`
  - Method: `GET`
  - Permission: `requireLogin`
  - Returns: station list + available vehicles.
- `start_rental`
  - Method: `POST`
  - Permission: `requireLogin` + CSRF + rate limit
  - Returns: created order info (`orderID`, `startTime`) or error with optional `message_key`.
- `end_rental`
  - Method: `POST`
  - Permission: `requireLogin` + CSRF + rate limit
  - Returns: completed order summary (fee/duration) or error with optional `message_key`.
- `profile_update`
  - Method: `POST`
  - Permission: `requireLogin` + CSRF + rate limit
  - Returns: success message.
- `wallet_topup`
  - Method: `POST`
  - Permission: `requireLogin` + CSRF + rate limit
  - Returns: updated wallet balance.

## Staff Actions

- `report_kpi`
  - Method: `GET`
  - Permission: `requireStaff`
  - Returns: KPI summary, by-station and by-status aggregates.
- `staff_bicycles`
  - Method: `GET`
  - Permission: `requireStaff`
  - Returns: bicycle list + brands + active stations.
- `staff_add_bicycle`
  - Method: `POST`
  - Permission: `requireStaff` + CSRF + rate limit
  - Returns: success/error message.
- `staff_remove_bicycle`
  - Method: `POST`
  - Permission: `requireStaff` + CSRF + rate limit
  - Returns: success/error message.
- `staff_students`
  - Method: `GET`
  - Permission: `requireStaff`
  - Returns: student list.
- `staff_add_student`
  - Method: `POST`
  - Permission: `requireStaff` + CSRF + rate limit
  - Returns: success/error message.
- `staff_update_student_status`
  - Method: `POST`
  - Permission: `requireStaff` + CSRF + rate limit
  - Returns: success/error message.
- `staff_remove_student`
  - Method: `POST`
  - Permission: `requireStaff` + CSRF + rate limit
  - Returns: success/error message.
- `staff_export_students`
  - Method: `GET`
  - Permission: `requireStaff`
  - Returns: CSV file download.
- `staff_export_bicycles`
  - Method: `GET`
  - Permission: `requireStaff`
  - Returns: CSV file download.
- `staff_import_students_csv`
  - Method: `POST`
  - Permission: `requireStaff` + CSRF + rate limit
  - Returns: created count + import errors.
- `staff_batch_student_status`
  - Method: `POST`
  - Permission: `requireStaff` + CSRF + rate limit
  - Returns: updated record count.

## Admin Actions

- `admin_overview`
  - Method: `GET`
  - Permission: `requireAdmin`
  - Returns: operations overview data.
- `admin_vehicles`
  - Method: `GET`
  - Permission: `requireAdmin`
  - Returns: full vehicle list for admin panel.
- `admin_update_vehicle`
  - Method: `POST`
  - Permission: `requireAdmin` + CSRF + rate limit
  - Returns: success/error message.
- `admin_assign_station`
  - Method: `POST`
  - Permission: `requireAdmin` + CSRF + rate limit
  - Returns: success/error message.
- `admin_abnormal_orders`
  - Method: `GET`
  - Permission: `requireAdmin`
  - Returns: active orders running over threshold.
- `admin_force_end`
  - Method: `POST`
  - Permission: `requireAdmin` + CSRF + rate limit
  - Returns: force-close result.
