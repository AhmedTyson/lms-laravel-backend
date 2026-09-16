> Index: [tasks/todo.md](../todo.md) -- AI: load this phase file only, not the whole list.

## Phase 6 — Courses + Lessons (spec 6.2, 6.3)

## Task 6.1: Course lifecycle

**Description:** CRUD in `Modules/Courses`: Draft → Published → Archived linear only (RULE-013); per-instructor title unique; archive blocked with active enrollments; elevated-permission policy for published structural edits (RULE-001).

**Acceptance criteria:**
- [ ] Illegal transition (e.g. Archived → Published) rejected; archive-with-students rejected

**Verification:**
- [ ] `vendor/bin/pest --filter="CourseLifecycle"` green

**Dependencies:** Phase 5 (policies need permission checks)

**Files likely touched:**
- `Modules/Courses/*` (models, policies, controllers, requests)

**Estimated scope:** Medium (3-5 files)

## Task 6.2: Lessons + order integrity

**Description:** Lesson CRUD, `order` unique per course, reorder endpoint; progress references `component_id` so reorder/removal never corrupts completions.

**Acceptance criteria:**
- [ ] Duplicate order → 422; reorder keeps completions intact (test with seeded completion)

**Verification:**
- [ ] `vendor/bin/pest --filter="LessonTest"` green

**Dependencies:** Task 6.1

**Files likely touched:**
- `Modules/Courses/*`

**Estimated scope:** Small (1-2 files)

### Checkpoint: After Phase 6
- [ ] Instructor can draft → publish course with ordered lessons; student-side reads scoped
- [ ] **Review gate:** code + arch review; human sign-off before Phase 7
