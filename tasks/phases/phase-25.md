> Index: [tasks/todo.md](../todo.md) -- AI: load this phase file only, not the whole list.

## Phase 25 — Co-Instructors (BACKLOG-009, gated)

> Gate: real co-teaching/TA requirement + ADR. Single `courses.instructor_id` stays until then.

## Task 25.1: Course instructors join table

**Description:** Replace single-owner FK with course↔user roles (owner, co-instructor, TA, reviewer). Ownership transfer path preserved (exactly one owner). Spatie Teams interplay decided in ADR (course team vs join table).

**Acceptance criteria:**
- [ ] Exactly-one-owner invariant enforced (DB or app-checked, tested)
- [ ] Existing `instructor_id` migrates to owner row losslessly
- [ ] TA/reviewer capabilities scoped (no publish/archive unless granted)

**Verification:**
- [ ] Pest: owner uniqueness, migration fidelity, capability matrix

**Dependencies:** Global gate (relates to users, Spatie Teams)

**Files likely touched:**
- `docs/adr/NNNN-course-instructors.md` (first)
- `Modules/Courses/database/migrations/*.php` (post-ADR)

**Estimated scope:** Medium (3-5 files post-ADR)

### Checkpoint: After Phase 25
- [ ] Capability matrix committed; single-owner invariant tested
- [ ] Human sign-off before Phase 26
