> Index: [tasks/todo.md](../todo.md) -- AI: load this phase file only, not the whole list.

## Phase 3 — Database Implementation (spec §5, 19 tables) [x]

> Conventions: `docs/db-conventions.md`. Migrations live in `Modules/<Name>/database/migrations/` (shared tables in root). Portable SQL only. Factories + seeders per module.
> Done in commit `Phase 3` (24 migrations green, SchemaTest 6 tests, morph map enforced).

## Task 3.1: Users extension + groups ledger

**Description:** Extend `users` (`manager_id` self-FK + index, `approval_status` enum nullable) and create `groups`, `group_members` (unique pair), `permission_grants` (append-only, no `updated_at`, §5.3 indexes).

**Acceptance criteria:**
- [x] `migrate:fresh` clean on SQLite; all uniques/indexes present
- [x] Seeder sets live `manager_id` path (Schema guard removable once column exists)

**Verification:**
- [x] `vendor/bin/pest --filter="SchemaTest"` asserts tables + uniques
- [x] `./vendor/bin/pint --test`, PHPStan clean

**Dependencies:** Phase 2

**Files likely touched:**
- `Modules/Auth/database/migrations/*_extend_users_table.php`
- `Modules/AccessManagement/database/migrations/*.php`
- `tests/Feature/SchemaTest.php`

**Estimated scope:** Medium (3-5 files)

## Task 3.2: Courses, lessons, enrollments

**Description:** `courses` (unique instructor+title, status enum), `lessons` (unique course+order, RULE-014), `enrollments` (unique student+course — closes concurrent-enrollment race).

**Acceptance criteria:**
- [x] Double-enroll attempt violates unique index (Pest asserts 500→handled 422 at API layer later; DB-level proof now)
- [x] Factories for all three

**Verification:**
- [x] Pest schema + factory smoke green

**Dependencies:** Task 3.1

**Files likely touched:**
- `Modules/Courses/database/migrations/*.php`
- `Modules/Enrollment/database/migrations/*.php`
- `Modules/*/database/factories/*.php`

**Estimated scope:** Medium (3-5 files)

## Task 3.3: Assignments + submissions

**Description:** `assignments` (`max_score` d6,2, `passing_threshold` d5,2, required `due_date`, `resubmission_allowed`) and `submissions` (unique assignment+enrollment+attempt, nullable file/content, per-attempt `is_late`, RULE-016 no grade-of-record).

**Acceptance criteria:**
- [x] Unique attempt composite enforced; both content columns nullable

**Verification:**
- [x] Pest schema green

**Dependencies:** Task 3.2

**Files likely touched:**
- `Modules/Assignments/database/migrations/*.php`

**Estimated scope:** Small (1-2 files)

## Task 3.4: Quizzes, questions, attempts

**Description:** `quizzes` (`passing_threshold` % of dynamic question-points sum), `questions` (`points` d6,2 default 1.00, 5 types), `question_options`, `question_accepted_answers`, `attempts` (unique quiz+enrollment+attempt), `attempt_answers` (unique attempt+question).

**Acceptance criteria:**
- [x] Threshold stored as percentage, never as cached point total

**Verification:**
- [x] Pest schema green

**Dependencies:** Task 3.3

**Files likely touched:**
- `Modules/Quizzes/database/migrations/*.php`

**Estimated scope:** Medium (3-5 files)

## Task 3.5: Progress, notifications

**Description:** `progress_records` (1-to-1 enrollment), `component_completions` (unique enrollment+type+id, `is_override`, `overridden_by`) as Eloquent morph (`morphTo`/`morphMany`, ADR-010 — no cross-table FK), `notifications`.

**Acceptance criteria:**
- [x] `$completion->component` resolves Lesson/Assignment/Quiz without type-switch

**Verification:**
- [x] Pest morph-resolution test green

**Dependencies:** Task 3.4

**Files likely touched:**
- `Modules/Progress/database/migrations/*.php`
- `Modules/Notifications/database/migrations/*.php`
- `Modules/Progress/app/Models/ComponentCompletion.php`

**Estimated scope:** Medium (3-5 files)

### Checkpoint: After Phase 3 [x]
- [x] `migrate:fresh --seed` builds all 19 tables + Spatie tables; full suite green (11 passed)
- [x] **Review gate:** code review (migration quality) + arch review (table placement per module) per `docs/handbook/04-code-review-policy.md`
- [x] Human sign-off before Phase 4
