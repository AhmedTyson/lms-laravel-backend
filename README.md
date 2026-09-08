# LMS Project — Laravel Backend

Learning Management System backend: instructors author structured courses (lessons, assignments, quizzes), students enroll and progress with reliable tracking, admins retain platform oversight — plus a general-purpose Access Management subsystem (hierarchical permission delegation, groups). All exposed through a versioned REST API.

> Status: **Phase 1 complete** (project init, env, tooling, CI). Business modules land in later phases per `tasks/plan.md`. No auth endpoints, grants, or course tables exist yet — by design.

Spec: `docs/LMS_Laravel_BRD_PRD_v2_1_full.md` (v2.1, authoritative) · Architecture baseline: `docs/Architecture Initialization + Phase Replanning Prompt.md`

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
| DB (dev) | SQLite | — |
| DB (prod) | MySQL | — |
| Queue/Cache | sync (dev) → Redis (staging/prod) | — |

## Prerequisites

- PHP ^8.3, Composer 2.x
- No local MySQL/Redis needed for dev (SQLite + sync queue)

## Quickstart

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan jwt:secret
php artisan migrate --seed
php artisan serve
```

Health: `GET /api/health` → `{"ok": true}` · API docs: `/docs/api`

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
Modules/            # Phase 2+: Auth, AccessManagement, Courses, Enrollment,
                    # Assignments, Quizzes, Progress, Notifications, Reporting
app/ config/ routes/ database/   # standard Laravel (thin until modules land)
docs/               # BRD/PRD spec, architecture baseline, ADRs (Phase 2), conventions
tasks/              # plan.md + todo.md (phase tracker)
```

Planned module internals: `Domain/` `Application/` `Infrastructure/` `Http/` `Database/` `Tests/` — only the layers a module actually needs (no empty-folder scaffolding).

## Roadmap

- [x] Phase 1 — scaffold, env, packages, tooling, CI
- [ ] Phase 2 — nine module shells + DB init kit for developers
- [ ] Later — schema → Auth → AccessManagement → teaching modules → reviews (code/arch/patterns)

## Contributing

Branch per phase/slice, keep CI green, run Pint before push. Decisions that add packages or patterns go in `docs/adr/` (Decision / Reason / Alternatives / Impact / Status).
