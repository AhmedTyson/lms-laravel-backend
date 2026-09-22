> Index: [tasks/todo.md](../todo.md) -- AI: load this phase file only, not the whole list.

## Phase 17 — Query Performance (future, needs measured bottleneck)

> Gate: slow-query evidence (`pg_stat_statements` / Telescope) before any work. No speculative indexes.

## Task 17.1: Hot-path index pass

**Description:** Composite/covering indexes on proven hot queries (enrollments lookup, completions per enrollment, grants read-model). `EXPLAIN` before/after per query.

**Acceptance criteria:**
- [ ] Every added index cites a measured slow query (ms before/after in the commit message)
- [ ] No index without a query; `migrate:fresh --seed` + suite green

**Verification:**
- [ ] `vendor/bin/pest` full suite green
- [ ] Telescope query tab shows no N+1 on enrollment/progress reads

**Dependencies:** Phase 12 (read patterns stable)

**Files likely touched:**
- `Modules/*/database/migrations/*_add_*_index.php`

**Estimated scope:** Small (2-4 files)

## Task 17.2: N+1 elimination sweep

**Description:** Eager-load audit on Resources (`with()` / `loadMissing()`), Brain `queries` tab as detector.

**Acceptance criteria:**
- [ ] Zero N+1 on all list endpoints under seed-scale data
- [ ] No over-fetching introduced (fields match Resource output)

**Verification:**
- [ ] Pest + `assertQueryCount`-style guards on hot endpoints

**Dependencies:** Task 17.1

**Files likely touched:**
- `Modules/*/app/Http/Controllers/*.php`
- `Modules/*/app/Http/Resources/*.php`

**Estimated scope:** Small (3-5 files)

### Checkpoint: After Phase 17
- [ ] Index/query report committed (`docs/analysis/query-report.md`)
- [ ] Human sign-off before Phase 18
