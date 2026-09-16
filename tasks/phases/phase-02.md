> Index: [tasks/todo.md](../todo.md) -- AI: load this phase file only, not the whole list.

## Phase 2 — Skeleton for developers [x]

- [x] **Task 2.1** Nine pruned module shells, all enabled (`module:list`), zero business routes (gate test).
- [x] **Task 2.2** DB init kit: `docs/db-conventions.md`, Admin-first seeder (verified, idempotent), Spatie publishes.
- [x] **Task 2.3** Shared foundation: JWT `api` guard + `User implements JWTSubject`, Filament `/admin` (ADR-0004), ADRs 0001–0004, dependency doc.

### Checkpoint: Phase 2 [x]
- [x] `migrate:fresh --seed` clean, 1 admin; full suite + Pint + PHPStan green; pushed to `AhmedTyson/lms-laravel-backend`.
