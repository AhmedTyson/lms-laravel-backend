> Index: [tasks/todo.md](../todo.md) -- AI: load this phase file only, not the whole list.

## Phase 33 — Read Scaling (future, needs read-pressure evidence)

> Gate: >80% read mix with replica-lag-tolerant reads, or connection pressure >200. Sticky reads for read-your-write paths.

## Task 33.1: Read replicas + connection pooling

**Description:** Read/write splitting (writes + fresh-read paths sticky to primary), PgBouncer past 200 connections. MySQL replica lag budget documented; reporting reads go to replica first.

**Acceptance criteria:**
- [ ] No stale-read bugs on enrollment/grade flows (sticky paths listed + tested)
- [ ] Pool config env-driven, sized from measured connections
- [ ] Failover story documented (promote path, RPO impact)

**Verification:**
- [ ] Pest: sticky-read tests (write → immediate read sees write)
- [ ] Staging lag measurement committed

**Dependencies:** Phases 12 (reporting = replica-first consumer), 16

**Files likely touched:**
- `config/database.php` (connections, sticky flag)
- `.env.example` (documented vars only)

**Estimated scope:** Small (config + docs + tests)

### Checkpoint: After Phase 33
- [ ] Sticky-path list + lag budget committed
- [ ] Human sign-off before Phase 34
