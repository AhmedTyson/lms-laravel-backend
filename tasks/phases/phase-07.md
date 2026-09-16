> Index: [tasks/todo.md](../todo.md) -- AI: load this phase file only, not the whole list.

## Phase 7 — Enrollment

## Task 7.1: Enroll, withdraw, guards

**Description:** `POST /api/courses/{id}/enroll` (verified users only, once-only via unique index → 422 on double-submit, including concurrent test), withdraw (blocks archive while active), `status` active/completed.

**Acceptance criteria:**
- [ ] Concurrent double-enroll yields exactly one row; unverified → 403

**Verification:**
- [ ] `vendor/bin/pest --filter="EnrollmentTest"` incl. concurrency case

**Dependencies:** Phase 6

**Files likely touched:**
- `Modules/Enrollment/*`

**Estimated scope:** Medium (3-5 files)

### Checkpoint: After Phase 7
- [ ] Enroll/withdraw E2E; review gate; human sign-off before Phase 8
