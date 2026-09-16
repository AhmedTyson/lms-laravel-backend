> Index: [tasks/todo.md](../todo.md) -- AI: load this phase file only, not the whole list.

## Phase 12 — Reporting (spec 6.8, ADR-008)

## Task 12.1: Read-only reports, explicit team context

**Description:** Instructor-scoped (own courses) + Admin platform-wide endpoints; every permission evaluation passes explicit group context (multi-group loops never touch ambient `setPermissionsTeamId`).

**Acceptance criteria:**
- [ ] Instructor sees only own courses; cross-group report returns correct per-group rows (stale-context test)

**Verification:**
- [ ] `vendor/bin/pest --filter="ReportingTest"` green

**Dependencies:** Phases 6–10

**Files likely touched:**
- `Modules/Reporting/*`

**Estimated scope:** Medium (3-5 files)

### Checkpoint: After Phase 12
- [ ] Reports correct across groups; review gate; human sign-off before Phase 13
