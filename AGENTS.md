# AGENTS.md — LMS Laravel

Init-phase repo (Laravel 13.31, PHP 8.5, Windows pwsh). Spec: `docs/LMS_Laravel_BRD_PRD_v2_1_full.md` v2.1. Baseline: `docs/Architecture Initialization + Phase Replanning Prompt.md`. Tracker: `tasks/plan.md`, `tasks/todo.md`.

## Commands (run from repo root)

```powershell
vendor/bin/pest --colors=never           # all tests
vendor/bin/pest --filter="Health"        # single test
./vendor/bin/pint --test                 # style gate, run before every commit
./vendor/bin/phpstan analyse --no-progress --memory-limit=1G
php artisan migrate:fresh --force        # SQLite dev
php artisan module:list                  # nWidart modules (Phase 2+)
```

Order that matters: `pint -> pest -> phpstan -> migrate:fresh`. CI runs the same four.

## Verified gotchas (don't rediscover)

- Pest is **v4.7** (not v5): v5 needs PHPUnit 13, v4 needs Symfony 7 — resolved via `composer require ... -W`. Don't upgrade either without re-resolving.
- `phpstan.neon` scans only `app/`, `config/` — scaffold `routes/console.php` uses runtime-bound `$this` that PHPStan flags. API routes covered by Pest instead.
- `config/permission.php`: `'teams' => true` set **before** Spatie migration ran. If permission tables ever reset, keep it `true`.
- `wikimedia/composer-merge-plugin` already trusted (`allow-plugins`). If composer asks again, answer `y` or modules won't autoload.
- `bootstrap/app.php` registers `routes/api.php` explicitly (Laravel 13 has no api file by default). Health at `/api/health`, Scramble UI at `/docs/api`.
- `.env` holds a real local `JWT_SECRET` and is git-ignored — never commit, never copy to staging/prod.
- `QUEUE_CONNECTION=sync` local / `redis` staging. `MAIL_MAILER=log` local.
- phpunit.xml forces `sqlite :memory:` — never set `DB_CONNECTION` overrides in CI/test env.

## Architecture rules (from baseline, enforced in review)

- Only structuring package: `nwidart/laravel-modules:^13`. Never add Architex / Easy Modules / DDD Generator / `alizharb/laravel-modular`.
- Module shells: `Modules/<Name>/` with only needed layers (`Domain Application Infrastructure Http Database Tests`). No empty folders, no BaseService/BaseRepository, no repos/interfaces without concrete need.
- Native first: PHP backed enums, Laravel events/queue/validation. `spatie/laravel-data` only if a phase proves DTO need.
- AccessManagement is authorization-only (`who can do what`), never teaching domain. Auth ≠ Authorization ≠ Enrollment — separate modules.
- Later-phase invariants (don't pre-build): `permission_grants` append-only; `manager_id` cycle check = service + transaction + `CircularManagerAssignmentException`; grant/revoke + Reporting use **explicit** Spatie team param, never ambient `setPermissionsTeamId()`.
- Every new package/pattern needs `docs/adr/NNNN-name.md` (Decision / Reason / Alternatives / Impact / Status).
