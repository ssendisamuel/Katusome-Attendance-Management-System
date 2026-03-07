# MUBS Attendance Management System

A Laravel-based application for managing student attendance at Makerere University Business School. It supports schedule generation, attendance recording within time windows, lecturer and course management, and reporting.

**Production URL:** https://attendance.mubs.ac.ug

## Getting Started

- Copy `.env.example` to `.env` and configure database and `APP_TIMEZONE`.
- Install dependencies: `composer install` and `npm install`.
- Generate key: `php artisan key:generate`.
- Run migrations and seeders: `php artisan migrate --seed`.
- Build assets: `npm run build` or `npm run dev`.
- Start the server: `php artisan serve`.

## Notes

- Attendance button activates only within schedule `start_at`–`end_at` per app timezone.
- Clear caches after config changes: `php artisan optimize:clear`.

## API Endpoints (Student)

These endpoints are protected by `auth:sanctum` and `can:student` middleware.

### Authentication

- `POST /api/login`: Login for students.
- `GET /api/user`: Get current user.

### Attendance

- `GET /api/student/schedules/today`: Get today's schedules and attendance status.
- `POST /api/student/attendance`: Check-in (requires `schedule_id`, `lat`, `lng`).
- `POST /api/student/clock-out`: Clock-out (requires `schedule_id`, `lat`, `lng`).

### Dashboard & Courses

- `GET /api/student/courses`: Get list of enrolled courses.
- `GET /api/student/history`: Get detailed attendance history/track.

### Profile

- `GET /api/student/profile`: Get profile details.
- `POST /api/student/profile`: Update profile (email/phone).
- `POST /api/student/change-password`: Change password.
