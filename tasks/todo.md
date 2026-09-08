# LMS Laravel — todo.md (Init Phases 1–2, no business logic)

## Task 1.1: Scaffold Laravel 13 + git + env
Deps: None. Scope: Medium.
- [ ] `composer create-project laravel/laravel .` (root holds only `docs/`, `tasks/`)
- [ ] Git init; `.gitignore` covers `.env`, `vendor/`, `*.sqlite`
- [ ] `.env.example`: `DB_CONNECTION`, `ADMIN_SEED_EMAIL`, `ADMIN_SEED_PASSWORD`, `JWT_SECRET`, `QUEUE_CONNECTION=sync`, `REDIS_*`
- [ ] `README.md` setup stub (clone→install→env→jwt→migrate)
Files: `composer.json`, `.env.example`, `.gitignore`, `README.md`.
Verify: `php artisan --version` (13.x); `php artisan migrate --pretend` no fatal.

## Task 1.2: Core packages + verify
Deps: 1.1. Scope: Medium.
- [ ] `composer require nwidart/laravel-modules tymon/jwt-auth spatie/laravel-permission spatie/laravel-activitylog dedoc/scramble`
- [ ] `composer require --dev pestphp/pest laravel/pint larastan/larastan`
- [ ] Publish nWidart config; allow `wikimedia/composer-merge-plugin` (`y`); `composer dump-autoload`
- [ ] Publish Spatie permission + activitylog (untouched); set `permission.teams=true`
- [ ] `php artisan jwt:secret --force` (local); log L13 result in `docs/adr/0001-jwt-choice.md`
Files: `config/modules.php`, `config/permission.php`, `config/activitylog.php`, `docs/adr/0001-jwt-choice.md`.
Verify: `composer show nwidart/laravel-modules` (^13); `php artisan module:list` exit 0.

## Task 1.3: Tooling + CI
Deps: 1.2. Scope: Medium.
- [ ] `vendor/bin/pest --init` (if needed); keep 1 scaffold test
- [ ] `./vendor/bin/pint --test` clean; `phpstan.neon` level 5 (`app/`, `Modules/*/app`)
- [ ] Scramble route `/docs/api`; queue sync dev / redis staging documented
- [ ] `.github/workflows/ci.yml`: install → pint --test → pest → phpstan → migrate:fresh --env=testing (SQLite)
Files: `phpstan.neon`, `tests/Pest.php`, `.github/workflows/ci.yml`, `routes/web.php`.
Verify: pint, pest, `phpstan analyse --no-progress`, `migrate:fresh --env=testing` all green.

## Checkpoint: Phase 1
- [ ] Clone → install → `cp .env.example .env` → key + jwt → migrate:fresh clean (SQLite)
- [ ] Pint/Pest/PHPStan green; Scramble renders; CI present

## Task 2.1: Nine module shells (pruned)
Deps: Phase 1 checkpoint. Scope: Medium.
- [ ] `php artisan module:make Auth AccessManagement Courses Enrollment Assignments Quizzes Progress Notifications Reporting`
- [ ] Prune: keep Provider + `Tests/` smoke per module; delete empty Domain/Application/Infrastructure/Http/Database dirs
- [ ] `composer dump-autoload` clean via `Modules/*/composer.json` merge include
Files: `Modules/*/` (9 shells), `config/modules.php`.
Verify: `php artisan module:list` shows 9 enabled; `vendor/bin/pest` green; no business classes inside.

## Task 2.2: DB init kit FOR developer
Deps: 2.1. Scope: Small-Medium.
- [ ] `config/database.php`: sqlite dev default, mysql prod documented
- [ ] `docs/db-conventions.md`: auto-increment ints, native PHP enums, index/unique naming, `permission_grants` append-only, `activity_log` = Spatie publish only, no raw SQL
- [ ] Stubs: `Modules/*/Database/{Migrations,Factories,Seeders}/.gitkeep`; central `DatabaseSeeder` Admin-first contract (commented, env creds, no hardcode)
- [ ] Test `tests/Feature/InitDbConfigTest.php`: connections configured, Spatie migrations exist, zero business tables merged
Files: `docs/db-conventions.md`, `database/seeders/DatabaseSeeder.php`, stubs.
Verify: `migrate:fresh --seed` clean with 0 business tables.

## Task 2.3: Shared foundation
Deps: 2.2. Scope: Small.
- [ ] `GET /api/health` → `{ok:true}` (only route besides Scramble)
- [ ] ADRs: `0001-jwt-choice`, `0002-nwidart-only` (rejected Architex/Easy/DDD/alizharb), `0003-sqlite-dev-mysql-prod` (Decision/Reason/Alternatives/Impact/Status)
- [ ] `docs/module-dependencies.md`: Auth→AccessMgmt→Courses→Enrollment→{Assignments,Quizzes}→Progress; Notifications listener; Reporting read-only
- [ ] Test `tests/Feature/HealthTest.php` (200)
Files: `routes/api.php`, `docs/adr/*.md`, `docs/module-dependencies.md`.
Verify: `pest --filter=Health` green; pint + phpstan clean.

## Checkpoint: Phase 2 (init complete)
- [ ] 9 shells listed; migrate:fresh --seed clean; full suite + pint + phpstan green; `/api/health` 200; `/docs/api` renders
- [ ] Zero business code: `Select-String "hasPermissionTo|InstructorAppro|Course::" Modules/ app/` (excl. docs) returns empty
- [ ] Human approves before Phase 9. Later gates: code review → arch review → pattern review per slice.
