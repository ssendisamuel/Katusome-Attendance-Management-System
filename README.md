# Katusome Attendance Management System

A Laravel-based application for managing student attendance. It supports schedule generation, attendance recording within time windows, lecturer and course management, and reporting.

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
