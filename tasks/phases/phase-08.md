> Index: [tasks/todo.md](../todo.md) -- AI: load this phase file only, not the whole list.

## Phase 8 — Assignments (spec 6.4, RULE-015/016)

## Task 8.1: Assignment authoring

**Description:** CRUD with `max_score`, `passing_threshold` (%), required future `due_date` (app validation), `resubmission_allowed`; submission-format expectations live in free-text `description` (no type column).

**Acceptance criteria:**
- [ ] Past `due_date` at creation → 422; thresholds stored as percentages

**Verification:**
- [ ] `vendor/bin/pest --filter="AssignmentTest"` green

**Dependencies:** Phase 7

**Files likely touched:**
- `Modules/Assignments/*`

**Estimated scope:** Medium (3-5 files)

## Task 8.2: Submissions + grading

**Description:** Submit (file/text/both, `attempt_number` auto-increment per pair; blocked when resubmission disallowed), `is_late` per attempt, instructor grading; all attempts visible, no grade-of-record.

**Acceptance criteria:**
- [ ] Second attempt with flag off → 422; late flag correct around boundary; grades independent per attempt

**Verification:**
- [ ] `vendor/bin/pest --filter="SubmissionTest"` green

**Dependencies:** Task 8.1

**Files likely touched:**
- `Modules/Assignments/*`

**Estimated scope:** Medium (3-5 files)

### Checkpoint: After Phase 8
- [ ] Submit → grade E2E; review gate; human sign-off before Phase 9
