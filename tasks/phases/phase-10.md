> Index: [tasks/todo.md](../todo.md) -- AI: load this phase file only, not the whole list.

## Phase 10 — Progress (RULE-018 revised, RULE-019)

## Task 10.1: Completion engine + overrides

**Description:** Per-enrollment `percent_complete`; component "completed" iff best attempt ≥ its own % threshold; instructor/Admin manual override (`is_override` + `overridden_by`); course auto-complete when all components complete.

**Acceptance criteria:**
- [ ] Threshold math uses per-component totals (e.g. 90/150 = 60%); override flips status with audit

**Verification:**
- [ ] `vendor/bin/pest --filter="ProgressTest"` green

**Dependencies:** Phases 8, 9

**Files likely touched:**
- `Modules/Progress/*`

**Estimated scope:** Medium (3-5 files)

### Checkpoint: After Phase 10
- [ ] Student journey register → enroll → submit → quiz → complete visible; review gate; human sign-off before Phase 11
