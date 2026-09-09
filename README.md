# LMS Project — Laravel Backend

Learning Management System backend: instructors author structured courses (lessons, assignments, quizzes), students enroll and progress with reliable tracking, admins retain platform oversight — plus a general-purpose Access Management subsystem (hierarchical permission delegation, groups). All exposed through a versioned REST API.

> Status: **Phases 1–2 complete** (init, env, tooling, CI, nine module shells, DB kit). Business logic lands in later phases per `tasks/plan.md`. No auth endpoints, grants, or course tables exist yet — by design.

Spec: `docs/LMS_Laravel_BRD_PRD_v2_1_full.md` (v2.1, authoritative) · Architecture baseline: `docs/Architecture Initialization + Phase Replanning Prompt.md` · Database docs: `docs/DATABASE.md` (Mermaid ERD, renders on GitHub) · `docs/schema.dbml` (paste into dbdiagram.io) · `docs/erd.html` (offline interactive explorer — crow's-foot, click tables for stories + rules)

## Stack

| Concern | Technology | Version |
|---|---|---|
| Framework | Laravel | 13.x (PHP ^8.3) |
| Auth | Tymon JWT | ^2.3 |
| Authorization | Spatie Permission (**Teams mode ON**) | ^8.3 |
| Audit | Spatie Activitylog | ^5.1 |
| Modules | nWidart Laravel Modules | ^13.0 |
| API docs | Scramble (`/docs/api`) | ^0.13 |
| Tests | Pest | ^4.7 |
| Style | Pint | ^1.31 |
| Static analysis | Larastan (level 5) | ^3.11 |
| Admin panel | Filament (`/admin`, session auth) | ^5 |
| Debug | Telescope (`/telescope`, local only) | ^5.24 |
| DB (dev) | SQLite | — |
| DB (prod) | MySQL | — |
| Queue/Cache | sync (dev) → Redis (staging/prod) | — |

## Prerequisites

- PHP ^8.4 with `ext-intl` enabled, Composer 2.x (locked deps need >= 8.4; CI runs 8.4)
- No local MySQL/Redis needed for dev (SQLite + sync queue)

## Quickstart

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan jwt:secret
php artisan migrate --seed
php artisan make:filament-user   # panel login at /admin (session auth)
php artisan serve
```

Health: `GET /api/health` → `{"ok": true}` · API docs: `/docs/api` · Admin panel: `/admin`

## Environment

| Var | Purpose |
|---|---|
| `ADMIN_SEED_EMAIL` / `ADMIN_SEED_PASSWORD` | Root admin created first by `DatabaseSeeder` (never hardcoded) |
| `JWT_SECRET` / `JWT_ALGO` | Tymon JWT signing key (`php artisan jwt:secret`) |
| `DB_CONNECTION=sqlite` | Local dev (MySQL in staging/prod) |
| `QUEUE_CONNECTION=sync` | Local dev (`redis` on staging/prod) |

`.env` is git-ignored and contains a real local JWT secret — never commit it.

## Verify

```bash
vendor/bin/pest --colors=never   # tests (phpunit.xml forces sqlite :memory:)
./vendor/bin/pint --test         # style (must pass before every commit)
./vendor/bin/phpstan analyse --no-progress --memory-limit=1G
php artisan migrate:fresh --force
```

CI (`.github/workflows/ci.yml`) runs the same four checks on push/PR.

## Layout

```text
Modules/            # Auth, AccessManagement, Courses, Enrollment, Assignments,
                    # Quizzes, Progress, Notifications, Reporting (shells: providers
                    # + config + database stubs + empty routes + tests)
app/ config/ routes/ database/   # standard Laravel (thin; logic lives in modules)
docs/               # BRD/PRD spec, architecture baseline, ADRs, conventions
tasks/              # plan.md + todo.md (phase tracker)
```

Planned module internals: `Domain/` `Application/` `Infrastructure/` `Http/` `Database/` `Tests/` — only the layers a module actually needs (no empty-folder scaffolding).

## Roadmap

- [x] Phase 1 — scaffold, env, packages, tooling, CI
- [x] Phase 2 — nine module shells + DB init kit + JWT api guard + Filament
- [ ] Later — schema → Auth → AccessManagement → teaching modules → reviews (code/arch/patterns)

## Contributing

Developer policies (mandatory): `docs/handbook/` — workflow, coding standards, testing, code review, security, architecture governance, definition of done, build & release runbook, **module development guide (09)**. Branch per phase/slice, keep CI green, run Pint before push. Decisions that add packages or patterns go in `docs/adr/` (Decision / Reason / Alternatives / Impact / Status).
