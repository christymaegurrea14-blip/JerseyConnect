# Deploying to Hostinger

Stack: Laravel 13 + Inertia/Vue 3 (Vite), MySQL, local `public` disk storage.
No queue jobs and no scheduled tasks currently exist, so this checklist skips
worker/cron setup beyond what's needed if you add them later.

## 0. Requirements
- Hostinger Business/Cloud (or VPS) plan — need SSH access + PHP 8.3.
- In hPanel → Advanced → PHP Configuration: set PHP to **8.3**.
- In hPanel → Advanced → SSH Access: enable it, note host/port/credentials.

## 1. Build locally (Hostinger has no Node)
```bash
npm ci
npm run build          # outputs public/build/
composer install --no-dev --optimize-autoloader
```
Do not upload `node_modules/`.

## 2. Upload
- Upload everything except `node_modules/`, `.git/`, `.env`.
- **Document root must point at `public/`**, not the project root. In hPanel →
  Domains, set the document root to e.g. `public_html/jerseyconnect/public`.
  Getting this wrong exposes `app/`, `storage/`, and `.env` to the web.

## 3. Database
- hPanel → Databases → MySQL Databases → create DB + user, grant all privileges.
- Copy the generated `DB_DATABASE` / `DB_USERNAME` / `DB_PASSWORD` into `.env`.

## 4. Configure `.env`
- Copy `.env.production.example` → `.env` on the server and fill in real values:
  - `APP_URL` — your real domain, `https://...`
  - `DB_*` — from step 3
  - `MAIL_*` — get the real SMTP host/port from hPanel → Emails (the example
    file guesses `smtp.hostinger.com:465`; confirm it there)
- `php artisan key:generate` on the server (don't reuse your local `APP_KEY`).

## 5. Migrate + storage
```bash
php artisan migrate --force
php artisan storage:link
chmod -R 775 storage bootstrap/cache
```
Confirm these upload subfolders exist under `storage/app/public/` (create if
missing): `design-requests/`, `gcash/`, `jersey/`, `messages/`.

## 6. Cache for production
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```
Re-run all three after every future deploy — stale cache silently ignores
`.env` changes.

## 7. SSL
- hPanel → SSL → enable free Let's Encrypt certificate.
- Force HTTPS so session cookies (`SESSION_SECURE_COOKIE=true`) and Inertia work.

## 8. Smoke test after deploy
- [ ] Login as a client and as an admin (role middleware works)
- [ ] Jersey listing images render
- [ ] Admin design request upload (logo/proof/template images)
- [ ] GCash QR upload/display
- [ ] Chat: send a message with an attachment
- [ ] Browser console clean — no 404s on `/build/assets/*` (wrong `APP_URL`
      or wrong document root is the usual cause)

## Future: if you add scheduled tasks or queued jobs
- Cron (hPanel → Advanced → Cron Jobs): `* * * * * php /path/to/artisan schedule:run >> /dev/null 2>&1`
- Switch `QUEUE_CONNECTION` from `sync` to `database` and run a queue worker
  (shared hosting can't run a long-lived `queue:work` process — you'd need a
  VPS plan or Hostinger's scheduled task feature calling `queue:work --stop-when-empty`).
