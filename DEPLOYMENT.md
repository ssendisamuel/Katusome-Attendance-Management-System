# Deployment Checklist: katusome.ssendi.dev

This guide prepares the project for production deployment on a CWP server.

## Server Setup
- Set the vhost DocumentRoot to `.../Ssendi_Attendance/public`.
- Enable `.htaccess` and symlink following:
  - In Apache vhost: `AllowOverride All` and ensure `Options +FollowSymLinks`.
  - In `public/.htaccess`: confirm Laravel defaults (already present). If symlinks are blocked, add:
    - `Options +FollowSymLinks`
    - `RewriteEngine On`
- Ensure PHP extensions: `pdo_mysql`, `mbstring`, `openssl`, `curl`, `fileinfo`, `gd`, `xml`, `zip`, `bcmath`.
- Set correct PHP version to match your composer platform (PHP ≥ 8.0 recommended).

## Application Setup
1. Upload code to the server (keep `public` as web root).
2. Run Composer:
   - `composer install --no-dev --optimize-autoloader`
3. Configure environment:
   - Copy `.env.production.example` to `.env`
   - Set `APP_KEY` using `php artisan key:generate`
   - Fill DB credentials and `APP_URL=https://katusome.ssendi.dev`
   - Set `FILESYSTEM_DISK=public`
   - Set `QUEUE_CONNECTION=database` (recommended) or `sync` temporarily
   - Keep `MAIL_MAILER=log` until SMTP is configured
4. Permissions:
   - `chown -R <webuser>:<webgroup> storage bootstrap/cache`
   - `chmod -R ug+rw storage bootstrap/cache`
5. Database:
   - `php artisan migrate --force`
6. Storage symlink:
   - `php artisan storage:link`
   - Verify: `ls -l public | grep storage` and ensure it targets `storage/app/public`
7. Build assets:
   - Locally: `npm ci && npm run build`
   - Upload `public/build/` to the server
   - Or build on server with Node installed
8. Optimize caches:
   - `php artisan config:cache`
   - `php artisan route:cache` (ensure no route closures)
   - `php artisan view:cache`

## Queue Worker (emails and async jobs)
- Ensure `QUEUE_CONNECTION=database`.
- Start a worker with Supervisor (recommended):

```
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/app/artisan queue:work database --sleep=3 --tries=3 --timeout=90
autostart=true
autorestart=true
numprocs=1
redirect_stderr=true
stdout_logfile=/var/log/laravel-worker.log
stopwaitsecs=3600
```

- Alternatively, run: `php artisan queue:work` in a screen/tmux session.

## Broken Images (avatars/selfies)
- Files are stored under `storage/app/public/avatars` and `storage/app/public/selfies`.
- URLs are generated via the `public` disk (`/storage/...`).
- Fixes:
  - Recreate symlink: `php artisan storage:link`
  - Ensure web server follows symlinks
  - Check `APP_URL` matches the site
  - Test direct file: `curl -I https://katusome.ssendi.dev/storage/selfies/<file>`

## 500 Error Triage
- Check logs: `tail -n 200 storage/logs/laravel.log`
- Common causes and fixes:
  - Missing `APP_KEY`: run `php artisan key:generate`
  - No `vendor/`: run `composer install`
  - DB connection failure (used by cache/session/queue): verify `.env` DB settings
  - Cache store set to `database` but DB not ready: set `CACHE_STORE=file` temporarily
  - DocumentRoot not `public`: fix vhost path
  - Permissions: ensure `storage` and `bootstrap/cache` writable
  - Stale caches: `php artisan config:clear && php artisan route:clear && php artisan view:clear`

## Post-Deploy Validation
- Visit `https://katusome.ssendi.dev/` and log in.
- Verify avatars render on the navbar and profile page.
- Perform a full Google OAuth sign-in flow.
- Mark attendance with a selfie and confirm the image renders in summary.
- Check that queued emails appear in `jobs` table and are processed by worker.