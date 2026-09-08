# 08 — Build & Release Runbook (guide)

## 1. Local build (from scratch)

```powershell
composer install
cp .env.example .env
php artisan key:generate
php artisan jwt:secret
php artisan migrate --seed
php artisan make:filament-user   # /admin login
php artisan serve
```

Verify: `GET /api/health` → `{"ok": true}` · `/docs/api` renders · `/admin` login works.

## 2. Environment matrix

| Var | Local | Staging | Prod |
|---|---|---|---|
| `APP_ENV` / `APP_DEBUG` | local / true | staging / false | production / false |
| `DB_CONNECTION` | sqlite | mysql | mysql |
| `QUEUE_CONNECTION` | sync | redis | redis |
| `CACHE_STORE` | database | redis | redis |
| `MAIL_MAILER` | log | smtp | smtp |
| `JWT_SECRET` | generated local | vault | vault (unique per env) |
| `ADMIN_SEED_*` | dev values | vault | vault (rotate after first login) |

## 3. Deploy steps (staging/prod)

1. `composer install --no-dev --optimize-autoloader`
2. `php artisan config:cache && php artisan route:cache && php artisan view:cache`
3. `php artisan migrate --force` (never `migrate:fresh` outside local)
4. `php artisan db:seed --class=DatabaseSeeder --force` (first deploy only; idempotent)
5. `php artisan filament:upgrade` (after Filament updates)
6. Smoke script: health → admin login → API login → enroll → submit → grade round-trip.

## 4. Rollback

- Code: redeploy previous tag. Migrations: `migrate:rollback --step=N` only if the deploy's migrations haven't been built on (data-loss risk) — otherwise forward-fix. Record the decision in the release thread.
- Audit tables (`permission_grants`, `activity_log`) are never rolled back or truncated.

## 5. Ops notes

- Queues: `queue:work --tries=3` supervised (systemd/Horizon per platform); failed jobs reviewed, never silently flushed.
- Backups: daily DB dump + `storage/` snapshot before every release; restore rehearsed quarterly.
- Logs: structured app log + queue failures to platform collector; alert on 5xx spike and queue backlog (spec risks §1.12).
