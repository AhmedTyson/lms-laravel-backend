> Index: [tasks/todo.md](../todo.md) -- AI: load this phase file only, not the whole list.

## Phase 22 — Course Structure Hierarchy (BACKLOG-007, gated)

> Gate: real grouped-modules or cross-course reuse requirement + ADR. Flat Course → Lesson (RULE-014) stays until then.

## Task 22.1: Nested structure (Section → Subsection → Unit → Component)

**Description:** Replace flat lessons with Open edX-style nesting. Ordering moves from per-course lesson order to per-parent order. `component_completions` morph targets extend to new levels.

**Acceptance criteria:**
- [ ] Existing lesson order migrates losslessly (each lesson becomes a unit under one default section)
- [ ] Unique order scoped to parent, not course
- [ ] Progress engine resolves new component types without type-switch (ADR-010 pattern)

**Verification:**
- [ ] Migration round-trip test (flat → nested → same completion state)
- [ ] Full suite green, RefinementTest untouched

**Dependencies:** Global gate; B-014 ordered after this

**Files likely touched:**
- `docs/adr/NNNN-course-hierarchy.md` (first)
- `Modules/Courses/database/migrations/*.php` (post-ADR)

**Estimated scope:** Large (5-8 files post-ADR)

### Checkpoint: After Phase 22
- [ ] Migration reversibility proven on seed-scale data
- [ ] Human sign-off before Phase 23
