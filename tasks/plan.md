# LMS Laravel — Init Plan (Phases 1–2)

Sources: `docs/LMS_Laravel_BRD_PRD_v2_1_full.md` v2.1 + `docs/Architecture Initialization + Phase Replanning Prompt.md`.
Rule: init only. Zero business logic. DB + shells prepared FOR developer. Reviews (code/arch/patterns) = later phases.

## A. Phase Audit

| Incoming Task | Status | Problem | Action |
|---|---|---|---|
| Scaffold + packages (L11/12) | Stale | Baseline mandates Laravel 13, PHP 8.3+, nWidart, Larastan | REPLACE |
| DB/env/tooling baseline | Incomplete | Missing Redis/queue/JWT/Teams/CI/git conventions | SPLIT + ADD |
| Business migrations (users/groups/grants) | Early | §13 forbids infra-then-schema-first; schema = Phase 9 | REMOVE → Phase 9 |
| Admin seeder + cycle service | Early | Business logic | REMOVE → Phases 12–13 (contract stub only) |
| JWT endpoints / approval / grants / groups / E2E | Early | Auth = step 12, AccessMgmt = 13 | MOVE out of init |
| Module shells, Larastan, CI, ADRs, conventions | Missing | Baseline §13–14 required | ADD |

## B. Architecture Impact

| Area | Impact | Change |
|---|---|---|
| Modules | 9 shells, pruned | `module:make` ×9, delete empty layers |
| Layers | Convention only | Doc, no premature abstractions |
| Database | Config + conventions, 0 business tables | SQLite dev / MySQL prod, Spatie publishes, stubs |
| Auth | JWT installed + verified, no endpoints | `jwt:secret` local, ADR logged |
| Authorization | Teams flag on, no grant logic | `permission.php teams=true` |
| Packages | nWidart v13 verified (L13, PHP ^8.3, merge-plugin) | Install now (see G) |
| Events/Queue | Sync dev, Redis staging | Documented, not wired to business |
| API | `/api/health` + Scramble shell | No business routes |
| Testing | Smoke only (1/module max) | Pest green |

## C. Replanned Phase

```text
Phase 1 — Env init
 ├── 1.1 Scaffold Laravel 13 + git + env
 ├── 1.2 Core packages + verify
 └── 1.3 Tooling (Pest/Pint/PHPStan/Scramble) + CI
Phase 2 — Skeleton for developer
 ├── 2.1 Nine module shells (pruned)
 ├── 2.2 DB init kit (config/conventions/publishes/stubs)
 └── 2.3 Shared foundation (health/providers/ADRs/deps doc)
Later: Phase 9 DB → 12 Auth → 13 AccessMgmt → … → code/arch/pattern reviews
```

## D. New Tasks
nWidart install + shells + prune; Larastan + CI; ADR log; dependency-rules doc; DB conventions doc.

## E. Removed From Init
Migrations, seed data, JWT endpoints, approval, grant/revoke, groups CRUD, E2E slice. All moved, none deleted from project.

## F. Moved
```text
Old schema work → Phase 9 Database Implementation
Old seeder/service/auth/grants/groups → Phases 12–13
Old E2E/docs → per-module tests + Phase 22 API Docs
```

## G. Packages

Install now: `nwidart/laravel-modules:^13.0` (sole structurer; v13.0.0 = L13/PHP^8.3, merge-plugin `Modules/*/composer.json`), `tymon/jwt-auth` (verify L13 at install, ADR result), `spatie/laravel-permission` (Teams=true), `spatie/laravel-activitylog` (publish only), `dedoc/scramble`, `pestphp/pest`, `laravel/pint`, `larastan/larastan`, `predis/predis` only if native Redis client missing.
Later: `spatie/laravel-data` only if DTO need proven in Auth phase.
Never: Architex / Easy Modules / DDD Generator / `alizharb/laravel-modular` (overlapping generators, §4 forbids); enum/CQRS/event-sourcing packages (native PHP/Laravel covers); BaseService/BaseRepository abstractions.

## H. Execution Order
1.1 → 1.2 → 1.3 → checkpoint → 2.1 → 2.2 → 2.3 → final checkpoint. Strictly sequential. Human gate before Phase 9.

## Risks
Tymon lags L13 → verify + ADR. Empty-folder bloat → prune. SQLite/MySQL drift → portable-only conventions. merge-plugin trust prompt → answer `y`, document `allow-plugins`.

## Done = migrate:fresh clean, Pest + Pint + PHPStan green, Scramble renders, CI mirrors local, zero business code.
