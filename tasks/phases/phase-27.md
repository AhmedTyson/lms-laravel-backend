> Index: [tasks/todo.md](../todo.md) -- AI: load this phase file only, not the whole list.

## Phase 27 — Prerequisites (BACKLOG-010, gated)

> Gate: PRD amended with prerequisite-gating rule + ADR. No current business rule requires it.

## Task 27.1: Prerequisite gating on enrollment

**Description:** Course→course prerequisite edges. Enrollment guard checks completion of prerequisites. Cycle-safe (reuse cycle-walk pattern from ManagerAssignmentService, adapted).

**Acceptance criteria:**
- [ ] Enroll blocked with `PREREQUISITE_UNMET` naming unmet courses
- [ ] Prerequisite edges cycle-checked at write time
- [ ] Enrollment guard spec updated alongside (same commit)

**Verification:**
- [ ] Pest: blocked enroll, cycle rejection, completed-chain enroll passes

**Dependencies:** Global gate; touches enrollment guards (Phase 7)

**Files likely touched:**
- `docs/adr/NNNN-course-prerequisites.md` (first)
- `Modules/Enrollment/*` + `Modules/Courses/database/migrations/*.php` (post-ADR)

**Estimated scope:** Medium (3-5 files post-ADR)

### Checkpoint: After Phase 27
- [ ] Guard spec + cycle tests committed
- [ ] Human sign-off before Phase 28
