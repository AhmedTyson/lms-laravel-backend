> Index: [tasks/todo.md](../todo.md) -- AI: load this phase file only, not the whole list.

## Phase 15 — Performance

## Task 15.1: Query + queue audit

**Description:** Pagination/search/sort on all list endpoints (per NFR); N+1 sweep (`Model::preventLazyLoading` in tests to catch); slow-query review of reports; staging queue on Redis; cache where spec allows.

**Acceptance criteria:**
- [ ] No unbounded list endpoint; report queries use indexes (§5); queue jobs process on Redis staging

**Verification:**
- [ ] Pest with lazy-loading prevention green; staging timing notes in PR

**Dependencies:** Phase 14

**Estimated scope:** Medium (3-5 files)

### Checkpoint: After Phase 15 — perf notes approved; sign-off before Phase 16
