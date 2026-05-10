# PRM - UM Bicycle and Scooter Rental Management System

## 1. Project Overview
- **Course:** CISC3003 Web Programming
- **Team:** Team 01
- **Project Name:** UM's bicycle and scooter rental management system
- **Tech Stack:** HTML, CSS, JavaScript, PHP, MySQL, XAMPP
- **Purpose:** Build a secure full-stack web platform for last-mile transport across UM campus.

## 2. Team Information
| Original Pair | Student ID | Student Name | Role |
| --- | --- | --- | --- |
| 07 | dc325182 | Che Chi Hin | Member |
| 07 | dc325420 | Chang I Wai | Member |
| 09 | dc325381 | Hoi Kai Cheng | Leader |
| 09 | dc325107 | Ho Weng Hong | Member |
| 14 | dc325095 | Chung Hou Sam | Member |
| 14 | dc325837 | Lei Man Lam | Member |

## 3. Context
The UM campus is large, so walking between locations is inefficient during class change periods. Current bicycle usage is informal and unmanaged. This project introduces a structured rental system for bicycles and scooters.

## 4. Project Goals
1. Provide convenient campus last-mile transportation.
2. Offer self-service account registration/login and profile management.
3. Support complete rental lifecycle: search, rent, return, and history tracking.
4. Provide administrator tools for users, stations, vehicles, and orders.

## 5. Scope
### In Scope
- User account authentication and profile management.
- Vehicle and station browsing.
- Rental order creation and completion.
- Basic ticket/forum submission.
- Admin management dashboard.

### Out of Scope (Current Version)
- External payment gateway integration.
- Native mobile application.
- Real GPS tracking of moving vehicles.

## 6. Functional Requirements
### 6.1 User Features
- UM SSO login (or equivalent authenticated login flow).
- Browse vehicles by station and status.
- Start rental when no active rental exists.
- End rental only at valid station with available capacity.
- View personal order history and fee details.
- Submit feedback or issue tickets.

### 6.2 Administrator Features
- Admin-only login and role-based access.
- Manage user accounts and permissions.
- Manage vehicles (add, update, maintenance, retire).
- Manage station details and capacity.
- Review and adjust rental orders when needed.
- Moderate forum/feedback content.

## 7. Data Requirements
- **Users:** campus_id, full_name, role, email, phone, wallet_balance, password_hash.
- **Vehicles:** vehicle_id, type (bicycle/scooter), brand, status, station_id.
- **Stations:** station_id, station_name, latitude, longitude, max_capacity, current_count.
- **Orders:** order_no, user_id, vehicle_id, start_station_id, end_station_id, start_time, end_time, fee, status.

## 8. Business Rules
1. A user can have at most one active rental.
2. A vehicle can only be rented when status is `available`.
3. Rental return is blocked if target station is full.
4. Fee is calculated from rental duration and vehicle type policy.
5. Status transitions must be valid:
   - `available -> rented -> available`
   - `available/rented -> maintenance -> available`
   - `any -> retired` (admin only)

## 9. Non-Functional Requirements
- **Performance:** support at least 500 concurrent logged-in users.
- **Response Time:** average < 2 seconds for normal operations.
- **Reliability:** preserve committed transactions under failures.
- **Security:** password hashing, secure session handling, role-based authorization, HTTPS-ready.
- **Usability:** responsive UI for desktop/tablet/mobile.
- **Session Control:** automatic logout after 30 minutes of inactivity.

## 10. Dynamic UI/UX Requirements
- Modern landing page with strong visual hierarchy and clear call-to-action buttons.
- Animated counters for key system indicators.
- Scroll-based section reveal transitions.
- Hover interactions on key cards/panels.
- Consistent color system and reusable component styling.
- Accessibility-conscious contrast and semantic HTML structure.

## 11. Development Plan
- **Phase 1:** Planning and UI/UX wireframe finalization.
- **Phase 2:** Front-end implementation (HTML/CSS/JS).
- **Phase 3:** Back-end integration (PHP/MySQL on XAMPP).
- **Phase 4:** System testing and deployment.

## 12. Deliverables
- Running website URL with user and admin portals.
- Source code repository with clean structure and documentation.
- PRM and supporting documentation package.
- Final presentation and project proposal materials.

## 13. Acceptance Criteria
- Core modules (Signup/Login, Dashboard, Rental workflows) run without critical bugs.
- Prevent second active rental for same user.
- Prevent vehicle return at full station.
- Enforce session timeout and role-based access.
- Demonstrate complete system flow successfully.

## 14. Required Photos and Media Assets
To make the website visually complete, prepare these assets:

1. **Hero Banner Photo (1 image)**
   - UM campus transportation scene (students + bicycle/scooter).
   - Recommended: 1920x1080, JPG/WebP.

2. **Vehicle Photos (4-8 images)**
   - Bicycle and scooter close-up/product-style photos.
   - Recommended: at least 1200x800 each.

3. **Station Photos (4-6 images)**
   - Different station locations on campus.
   - Include day/night or indoor/outdoor variation if possible.

4. **Team Photos (optional, 1 group + 6 individual)**
   - For About/Team section credibility.
   - Square individual portraits: 800x800 recommended.

5. **Campus Map Image (1 image)**
   - Annotated UM map for station markers.
   - Recommended: 1600px+ wide PNG.

6. **Icons/Branding (optional)**
   - Project logo (SVG/PNG).
   - Feature icons (search, rent, return, security, admin).

## 15. References
- Chonoles, M. J., & Schardt, J. A. (2003). UML 2 for dummies. Wiley.
- Mozilla. (2025a, November 4). HTML: A good basis for accessibility. MDN.
- Mozilla. (2025b, December 19). Responsive web design. MDN.
- Seidl, M., Scholz, M., Huemer, C., & Kappel, G. (2015). UML @ Classroom. Springer.
