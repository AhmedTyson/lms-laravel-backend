> Index: [tasks/todo.md](../todo.md) -- AI: load this phase file only, not the whole list.

## Phase 34 — Partitioning & Archival (future, needs table-size evidence)

> Gate: hot tables (attempts, completions, activity) measurably large with query pain. Never premature.

## Task 34.1: Hot-table partitioning + cold archival

**Description:** Time-range partitioning on append-heavy tables (attempts, attempt_answers, completions, activity_log). Cold partitions archive to cheap storage with documented retrieval path. Reporting queries stay correct across the seam.

**Acceptance criteria:**
- [ ] Partition scheme + retention per table committed before migration
- [ ] Reporting/gradebook math verified identical pre/post (fixture comparison test)
- [ ] Archive retrieval path tested (request → restore → read)

**Verification:**
- [ ] Pest: cross-seam aggregate correctness, archive round-trip

**Dependencies:** Phases 12, 21 (readers of these tables)

**Files likely touched:**
- `Modules/*/database/migrations/*.php` (online-change discipline per backend-architect)
- Archive job + command

**Estimated scope:** Large (migrations + jobs + tests)

### Checkpoint: After Phase 34
- [ ] Partition/retention map committed; aggregates proven identical
- [ ] Human sign-off before Phase 35
