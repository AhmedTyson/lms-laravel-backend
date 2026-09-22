# AGENTS.md — LMS Laravel

Init-phase repo (Laravel 13, PHP ^8.4 enforced, Windows pwsh). Spec: `docs/LMS_Laravel_BRD_PRD_v2_1_full.md` v2.1. Baseline: `docs/Architecture Initialization + Phase Replanning Prompt.md`. Tracker: `tasks/plan.md`, `tasks/todo.md`. Developer policies (workflow, standards, testing, review, security, governance, DoD, runbook, module guide): `docs/handbook/01–09` — follow them, cite them in PRs.
Generate module classes only with `php artisan module:make-*` (never root `make:`); plain `php artisan migrate` covers `Modules/*/database/migrations` (verified by probe). Root `app/` = kernel only (User, providers); behavior lives in modules. Telescope `/telescope` local-only.
PHP floor is 8.4 (`composer.json` + `config.platform.php` pin): locked Symfony 8 / activitylog need >= 8.4.1. Never lower CI (`ci.yml` runs 8.4) or the lock below it.

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
- `wikimedia/composer-merge-plugin` trusted + `Modules/*/composer.json` include in root `composer.json`. After `module:make`, run `composer dump-autoload` or providers won't resolve. If composer asks trust again, answer `y`.
- PowerShell strips `^` in composer constraints — always single-quote with `*` form: `composer require 'filament/filament:5.*'`.
- PHP needs `ext-intl` (Filament). If missing: uncomment `extension=intl` in `php.ini`.
- Auth split: LMS API = `auth:api` (JWT) only; `web` sessions only for Filament `/admin`. `User implements JWTSubject`; `defaults.guard` stays `web` so panel login works.
- `bootstrap/app.php` registers `routes/api.php` explicitly (Laravel 13 has no api file by default). Health at `/api/health`, Scramble UI at `/docs/api`.
- `.env` holds a real local `JWT_SECRET` and is git-ignored — never commit, never copy to staging/prod.
- `QUEUE_CONNECTION=sync` local / `redis` staging. `MAIL_MAILER=log` local.
- phpunit.xml forces `sqlite :memory:` — never set `DB_CONNECTION` overrides in CI/test env.
- Pest `uses(Trait::class)` with an imported name fails to resolve — bind shared traits in `tests/Pest.php` (`->use(...)`), never per-file. Pint's `fully_qualified_strict_types` will otherwise fight Pest forever.
- All JSON via `App\Support\ApiResponse` (`success`/`data`/`error`/`jwt`) — never hand-built envelopes. 429s included.
- Rate limiters are module-owned (`AuthServiceProvider`, `AccessManagementServiceProvider`); core keeps `api` only. Named limiters: `auth-login/register/verify/password/oauth`, `access-grants/groups`.
- Factories resolve via `Factory::guessFactoryNamesUsing` in `AppServiceProvider` — no per-model wiring. Smoke guard: `tests/Feature/FactorySmokeTest.php`. Demo data: per-module seeders in FK order (`docs/mock-data-plan.md`).
- FormRequest per endpoint, thin controllers (validate → authorize → service → resource). Resources live in `Modules/<Name>/app/Http/Resources/` with `@mixin` model hints (Scramble JR001).
- List endpoints use `spatie/laravel-query-builder` (ADR-0006): `filter[x]`, `sort`, paginated envelope. Phone validation via `propaganistas/laravel-phone`; normalization centralized in `App\Support\PhoneNormalizer` (request pre-validate + model mutator).
- Brain scanner config (`config/laravel-brain.php`): `source_paths` includes `Modules/*/app`, `route_paths` includes `Modules/*/routes/*.php`, AST mode (auto-discover pulls package bulk).
- Route contracts: `docs/routes/<module>.md` per method (bodies + responses). Update the doc when the route changes.
- Clean-code process: `docs/handbook/10-clean-code-rules.md` (10 rules + open backlog). Findings get fix or ticket, never silence.

## Architecture rules (from baseline, enforced in review)

- Only structuring package: `nwidart/laravel-modules:^13`. Never add Architex / Easy Modules / DDD Generator / `alizharb/laravel-modular`.
- Module shells: `Modules/<Name>/` with only needed layers. Pruned at init: providers + config + database stubs + empty routes + tests. No `Http/`, `resources/`, `package.json`, `vite.config.js` until a slice needs them. No BaseService/BaseRepository, no repos/interfaces without concrete need.
- Filament accepted (ADR-0004, v5.8.1): panel at `/admin`. Resources live under their module's layer, never dumped in `app/`. Published panel assets are git-ignored.
- Gate test `tests/Feature/ModulesSmokeTest.php` fails if a module is disabled or a business `api/*` route appears before its phase — keep it green during init.
- Native first: PHP backed enums, Laravel events/queue/validation. `spatie/laravel-data` only if a phase proves DTO need.
- AccessManagement is authorization-only (`who can do what`), never teaching domain. Auth ≠ Authorization ≠ Enrollment — separate modules.
- Later-phase invariants (don't pre-build): `permission_grants` append-only; `manager_id` cycle check = service + transaction + `CircularManagerAssignmentException`; grant/revoke + Reporting use **explicit** Spatie team param, never ambient `setPermissionsTeamId()`.
- Every new package/pattern needs `docs/adr/NNNN-name.md` (Decision / Reason / Alternatives / Impact / Status).
- Approval endpoint (`instructors/{id}/approve`) lives in Auth until Phase 5 moves it to AccessManagement — do not duplicate approval logic elsewhere.
- Phase status words: `done`/`partial`/`open`/`hold`; checkpoint signed only with zero open boxes (`tasks/phases/manifest.json` mirrors `todo.md`).
