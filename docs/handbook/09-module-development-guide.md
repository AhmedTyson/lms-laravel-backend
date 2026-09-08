# 09 — Module Development Guide (guide)

How to build inside `Modules/`, what root `app/` is for, and how migrations/tests/commands work across directories. Read this before your first module slice.

## 1. Mental model: Modules own behavior, root owns kernel

| Location | Status | Purpose |
|---|---|---|
| `Modules/<Name>/app/Providers/` | **Live** | Module registration (`<Name>ServiceProvider` in `module.json`), routes, events. Never delete. |
| `Modules/<Name>/config/config.php` | **Live skeleton** | Module config. ⚠️ merges under the lowercased module name — for `Auth` that key collides with root `config/auth.php`. Verify with `tinker(config('…'))`; prefer distinct filenames or root config for shared keys. |
| `Modules/<Name>/database/{migrations,factories,seeders}/` | **Live skeletons** | Await Phase 3+. `.gitkeep` + `*DatabaseSeeder.php` stubs stay. |
| `Modules/<Name>/routes/api.php`, `routes/web.php` | **Live, intentionally empty** | Loaded by the module `RouteServiceProvider`. API routes go in `api.php`; `web.php` stays a stub (no LMS web UI). |
| `Modules/<Name>/tests/` | **Live skeleton** | Wire into root `tests/Pest.php` (see §5) once the module gains tests. |
| `Modules/<Name>/module.json`, `composer.json` | **Live** | Registration + autoload (`Modules\<Name>\` → `app/`). Never edit by hand; use `module:` commands. |
| `app/Models/User.php` | **Live, shared** | Single identity for JWT API + Filament. Implements `JWTSubject`. Extended (not replaced) by Auth-phase columns. |
| `app/Providers/` (App, Filament, Telescope) | **Live** | Kernel-level providers only. Feature providers belong in modules. |
| `routes/api.php` (`/api/health`) | **Live** | Only root API route, ever. Feature routes live in module `routes/api.php`. |
| `database/migrations/0001_*` | **Live** | Base users/cache/jobs tables + Spatie publishes. Module migrations extend, never duplicate these. |
| `database/seeders/DatabaseSeeder.php` | **Live** | Admin-first contract; calls module seeders in later phases. |
| Deleted at init (do not recreate) | **Dead by design** | Module stub controllers, `resources/` views/assets, per-module `package.json`/`vite.config.js`. API + Filament need none of these. |

Rule of thumb: if it serves HTTP, authorizes, or models the domain → module. If it boots the framework → root.

## 2. Migrations: one command runs everything

**Yes — plain `php artisan migrate` runs root AND all `Modules/*/database/migrations` in one pass.** nWidart registers each enabled module's migration path at boot. Proven in this repo by a probe migration (created → migrated → rolled back → removed, table appeared and disappeared).

Practical consequences:

- **Single global timeline.** Root and module migrations execute ordered by filename timestamp, not by directory. A module migration needing another table must use a *later timestamp* than that table's migration — plan timestamps when authoring cross-module FKs (e.g. `enrollments` after `users` + `courses`).
- **Per-module ops exist** for focused work: `module:migrate`, `module:migrate-rollback`, `module:migrate-refresh`, `module:migrate-fresh`, `module:migrate-status`, `module:seed`, `module:make-migration`, `module:make-seed`. CI and fresh setups always use root `migrate:fresh --seed` (covers modules too).
- Never `module:publish-migration` (copies migrations to root) — it defeats module ownership. Keep migrations where the module lives.
- After `module:make`, run `composer dump-autoload` if providers don't resolve (merge-plugin registers `Modules/*/composer.json`).

## 3. Creating module classes (always via `module:make-*`)

Never hand-create with root `make:` commands — namespaces and paths won't match (`Modules\<Name>\` maps to `Modules/<Name>/app/`).

```powershell
php artisan module:make-model Course Courses
php artisan module:make-controller CourseController Courses
php artisan module:make-request StoreCourseRequest Courses
php artisan module:make-resource CourseResource Courses
php artisan module:make-policy CoursePolicy Courses
php artisan module:make-migration create_courses_table Courses
php artisan module:make-factory Course Courses
php artisan module:make-seed Courses Courses
php artisan module:make-event CoursePublished Courses
php artisan module:make-listener SendCoursePublishedNotification Courses
php artisan module:make-job GradeSubmission Assignments
php artisan module:make-command RecomputeProgress Progress
php artisan module:make-test CourseLifecycle Courses
php artisan module:make-service GrantService AccessManagement
php artisan module:make-enum ApprovalStatus Auth
php artisan module:make-exception CircularManagerAssignment AccessManagement
php artisan module:make-middleware EnsureInstructor Courses
php artisan module:make-provider ReportingService Reporting
php artisan module:make-notification AssignmentGraded Notifications
php artisan module:make-observer CourseObserver Courses
php artisan module:make-rule FutureDueDate Assignments
php artisan module:make-cast PointsCast Quizzes
php artisan module:make-scope Published Courses
php artisan module:make-trait HasOwner Courses
```

Resulting namespaces (example): `Modules\Courses\Http\Controllers\CourseController`, `Modules\Courses\Database\Factories\…`, `Modules\Courses\Tests\…`. Helpers: `module_path('Courses', '…')` for file paths. Register policies/events in the module's own `AuthServiceProvider`/`EventServiceProvider` — never in root providers.

After generating: register anything the stub doesn't (policies, event→listener maps), write the Pest test first (handbook 03), run Pint.

## 4. Controllers, requests, resources pattern

Per handbook 02: thin controllers in `Modules/<Name>/app/Http/Controllers/`, validation in `Http/Requests/`, output in `Http/Resources/`, orchestration in `Application/` services or actions. Routes appended to the module's `routes/api.php` (already wrapped in `api` middleware + `api/` prefix by the module `RouteServiceProvider` — do not re-prefix).

## 5. Testing module code

Root `tests/Pest.php` currently binds `->in('Feature')` only, so `Modules/*/tests/` are **not auto-discovered**. When a module gains tests, extend the binding:

```php
pest()->extend(TestCase::class)->in('Feature', '../Modules/Auth/tests/Feature', /* … */);
```

Cross-module journeys live in root `tests/Feature/Journeys/`. Targeted run: `vendor/bin/pest --filter="Name"`.

## 6. Filament + Telescope placement

- Filament Resources for a module live under that module (e.g. `Modules/Courses/app/Filament/Resources/`), registered from the module — never dumped in root `app/`.
- Telescope (`laravel/telescope` ^5.24, `/telescope`) is **local-only** (provider registers in `local`, gate closed otherwise). Use it in dev to watch queries (N+1 hunt, handbook Phase 15), queued jobs, mail, and requests. Telescope tables migrate everywhere but stay empty outside local — entries pruned by schedule.
