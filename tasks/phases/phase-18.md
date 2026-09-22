> Index: [tasks/todo.md](../todo.md) -- AI: load this phase file only, not the whole list.

## Phase 18 — Caching Layer (future, needs read-hotspot evidence)

> Gate: identical queries running >100/min (or p95 pain) before any cache. Every key gets explicit TTL + invalidation story up front.

## Task 18.1: Redis wiring + catalog cache

**Description:** Redis for staging/prod (`CACHE_STORE`), cache-aside for course catalog + public lists. Versioned keys or event-driven purge — never bare TTL-only on mutable data.

**Acceptance criteria:**
- [ ] Cache-hit instrumentation (hit rate visible; <80% = fix keys or remove)
- [ ] Write path purges affected keys in the same transaction/flow
- [ ] Local dev unchanged (database/array cache, no Redis required)

**Verification:**
- [ ] `vendor/bin/pest` green with cache store swapped to array
- [ ] Stale-read regression test (write → read shows fresh data)

**Dependencies:** Phase 17

**Files likely touched:**
- `config/cache.php`, `.env.example` (documented vars only)
- `Modules/Courses/app/Services/*` (read-through seam)

**Estimated scope:** Medium (3-5 files)

### Checkpoint: After Phase 18
- [ ] Invalidation map committed (which writes purge which keys)
- [ ] Human sign-off before Phase 19
