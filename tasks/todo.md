# LMS Laravel — Full Task List (all phases)

Source: `docs/LMS_Laravel_BRD_PRD_v2_1_full.md` v2.1. Policies: `docs/handbook/`.
Phases 1–2 are done ([x]). Work phases strictly in order; each checkpoint needs human sign-off before the next phase starts.

---

## Phase 1 — Env init [x]

- [x] **Task 1.1** Scaffold Laravel 13 + git + env (`.env.example` documents admin/JWT/queue vars).
- [x] **Task 1.2** Core packages verified L13-compatible (nWidart 13, Tymon JWT 2.3, Spatie Permission Teams ON + Activitylog, Scramble, Pest 4.7, Pint, Larastan).
- [x] **Task 1.3** Tooling + CI green (Pest/Pint/PHPStan/migrate:fresh, Scramble + `/api/health`).

### Checkpoint: Phase 1 [x]
- [x] Fresh clone → install → migrate clean; CI mirrors local four gates.

## Phase 2 — Skeleton for developers [x]

- [x] **Task 2.1** Nine pruned module shells, all enabled (`module:list`), zero business routes (gate test).
- [x] **Task 2.2** DB init kit: `docs/db-conventions.md`, Admin-first seeder (verified, idempotent), Spatie publishes.
- [x] **Task 2.3** Shared foundation: JWT `api` guard + `User implements JWTSubject`, Filament `/admin` (ADR-0004), ADRs 0001–0004, dependency doc.

### Checkpoint: Phase 2 [x]
- [x] `migrate:fresh --seed` clean, 1 admin; full suite + Pint + PHPStan green; pushed to `AhmedTyson/lms-laravel-backend`.

---

## Phase 3 — Database Implementation (spec §5, 19 tables) [x]

> Conventions: `docs/db-conventions.md`. Migrations live in `Modules/<Name>/database/migrations/` (shared tables in root). Portable SQL only. Factories + seeders per module.
> Done in commit `Phase 3` (24 migrations green, SchemaTest 6 tests, morph map enforced).

## Task 3.1: Users extension + groups ledger

**Description:** Extend `users` (`manager_id` self-FK + index, `approval_status` enum nullable) and create `groups`, `group_members` (unique pair), `permission_grants` (append-only, no `updated_at`, §5.3 indexes).

**Acceptance criteria:**
- [ ] `migrate:fresh` clean on SQLite; all uniques/indexes present
- [ ] Seeder sets live `manager_id` path (Schema guard removable once column exists)

**Verification:**
- [ ] `vendor/bin/pest --filter="SchemaTest"` asserts tables + uniques
- [ ] `./vendor/bin/pint --test`, PHPStan clean

**Dependencies:** Phase 2

**Files likely touched:**
- `Modules/Auth/database/migrations/*_extend_users_table.php`
- `Modules/AccessManagement/database/migrations/*.php`
- `tests/Feature/SchemaTest.php`

**Estimated scope:** Medium (3-5 files)

## Task 3.2: Courses, lessons, enrollments

**Description:** `courses` (unique instructor+title, status enum), `lessons` (unique course+order, RULE-014), `enrollments` (unique student+course — closes concurrent-enrollment race).

**Acceptance criteria:**
- [ ] Double-enroll attempt violates unique index (Pest asserts 500→handled 422 at API layer later; DB-level proof now)
- [ ] Factories for all three

**Verification:**
- [ ] Pest schema + factory smoke green

**Dependencies:** Task 3.1

**Files likely touched:**
- `Modules/Courses/database/migrations/*.php`
- `Modules/Enrollment/database/migrations/*.php`
- `Modules/*/database/factories/*.php`

**Estimated scope:** Medium (3-5 files)

## Task 3.3: Assignments + submissions

**Description:** `assignments` (`max_score` d6,2, `passing_threshold` d5,2, required `due_date`, `resubmission_allowed`) and `submissions` (unique assignment+enrollment+attempt, nullable file/content, per-attempt `is_late`, RULE-016 no grade-of-record).

**Acceptance criteria:**
- [ ] Unique attempt composite enforced; both content columns nullable

**Verification:**
- [ ] Pest schema green

**Dependencies:** Task 3.2

**Files likely touched:**
- `Modules/Assignments/database/migrations/*.php`

**Estimated scope:** Small (1-2 files)

## Task 3.4: Quizzes, questions, attempts

**Description:** `quizzes` (`passing_threshold` % of dynamic question-points sum), `questions` (`points` d6,2 default 1.00, 5 types), `question_options`, `question_accepted_answers`, `attempts` (unique quiz+enrollment+attempt), `attempt_answers` (unique attempt+question).

**Acceptance criteria:**
- [ ] Threshold stored as percentage, never as cached point total

**Verification:**
- [ ] Pest schema green

**Dependencies:** Task 3.3

**Files likely touched:**
- `Modules/Quizzes/database/migrations/*.php`

**Estimated scope:** Medium (3-5 files)

## Task 3.5: Progress, notifications

**Description:** `progress_records` (1-to-1 enrollment), `component_completions` (unique enrollment+type+id, `is_override`, `overridden_by`) as Eloquent morph (`morphTo`/`morphMany`, ADR-010 — no cross-table FK), `notifications`.

**Acceptance criteria:**
- [ ] `$completion->component` resolves Lesson/Assignment/Quiz without type-switch

**Verification:**
- [ ] Pest morph-resolution test green

**Dependencies:** Task 3.4

**Files likely touched:**
- `Modules/Progress/database/migrations/*.php`
- `Modules/Notifications/database/migrations/*.php`
- `Modules/Progress/app/Models/ComponentCompletion.php`

**Estimated scope:** Medium (3-5 files)

### Checkpoint: After Phase 3 [x]
- [x] `migrate:fresh --seed` builds all 19 tables + Spatie tables; full suite green (11 passed)
- [ ] **Review gate:** code review (migration quality) + arch review (table placement per module) per `docs/handbook/04-code-review-policy.md`
- [ ] Human sign-off before Phase 4

---

## Phase 4 — Auth slice (spec 6.1, SCOPE-004)

## Task 4.1: Registration + email verification

**Description:** `POST /api/register` (Student vs Instructor paths; Instructor gets `approval_status=pending`, `manager_id` NULL until approval). Verification gate blocks enroll/publish (not login/browse).

**Acceptance criteria:**
- [ ] Unverified user gets 403 on enrollment stub; verified passes gate
- [ ] Student `manager_id` always NULL

**Verification:**
- [ ] `vendor/bin/pest --filter="AuthRegistration"` green
- [ ] Manual: register → verify link → gate opens

**Dependencies:** Phase 3

**Files likely touched:**
- `Modules/Auth/app/Http/Controllers/*.php`
- `Modules/Auth/routes/api.php`
- `Modules/Auth/tests/Feature/*.php`

**Estimated scope:** Medium (3-5 files)

## Task 4.2: Login, refresh, me (JWT)

**Description:** `POST /api/login` (JWT), refresh, logout (blacklist), `GET /api/me` behind `auth:api`. No session usage in API.

**Acceptance criteria:**
- [ ] 401 without token; expired token refreshable within grace; blacklisted token rejected

**Verification:**
- [ ] `vendor/bin/pest --filter="AuthLogin"` green

**Dependencies:** Task 4.1

**Files likely touched:**
- `Modules/Auth/app/Http/Controllers/*.php`

**Estimated scope:** Small (1-2 files)

## Task 4.3: Instructor approval workflow

**Description:** Admin-only `POST /api/instructors/{id}/approve` sets `approved` + `manager_id=<admin>` via `ManagerAssignmentService`, fires `InstructorApproved`. Pending instructors 403 on authoring routes.

**Acceptance criteria:**
- [ ] Non-admin approve → 403; double-approve idempotent; RULE-010 (manager set at approval, not registration)

**Verification:**
- [ ] `vendor/bin/pest --filter="InstructorApproval"` green

**Dependencies:** Tasks 4.2, 5.1 (`ManagerAssignmentService` — build the service first if sequencing demands; approval endpoint wires after)

**Files likely touched:**
- `Modules/Auth/*`, `Modules/AccessManagement/app/Services/ManagerAssignmentService.php`

**Estimated scope:** Medium (3-5 files)

### Checkpoint: After Phase 4
- [ ] Register → verify → login → approve E2E works on SQLite
- [ ] **Review gate:** security pass on auth (hashing, tokens, gates) per handbook §05
- [ ] Human sign-off before Phase 5

---

## Phase 5 — AccessManagement (RULE-002–012, ADR-007/008)

## Task 5.1: Manager tree service + cycle guard

**Description:** `ManagerAssignmentService::assign()` — transaction + `SELECT FOR UPDATE` + upward chain walk, throws `CircularManagerAssignmentException` (never silent, never model event). Reassignment non-retroactive (RULE-006).

**Acceptance criteria:**
- [ ] Cycle (incl. self-parent) rejected with dedicated exception; history rows untouched by reassignment

**Verification:**
- [ ] `vendor/bin/pest --filter="ManagerAssignment"` incl. cycle + self-parent cases

**Dependencies:** Phase 3

**Files likely touched:**
- `Modules/AccessManagement/app/Services/ManagerAssignmentService.php`
- `Modules/AccessManagement/app/Exceptions/CircularManagerAssignmentException.php`

**Estimated scope:** Medium (3-5 files)

## Task 5.2: Grant/revoke API (explicit team context)

**Description:** `POST /api/grants`, `POST /api/revokes`: subordinate-only (RULE-003) + ceiling (RULE-004) + append-only ledger rows (RULE-005). Every permission check passes explicit group context — bare `hasPermissionTo()` rejected in review.

**Acceptance criteria:**
- [ ] Grant to non-subordinate → 403; grant of unheld permission → 403 (global + group-scoped)
- [ ] Revoke writes new `revoked` row; nothing updated/deleted

**Verification:**
- [ ] `vendor/bin/pest --filter="PermissionGrant"` incl. stale-context regression (ambient team must not leak)

**Dependencies:** Tasks 5.1, 4.2

**Files likely touched:**
- `Modules/AccessManagement/app/Http/Controllers/*.php`
- `Modules/AccessManagement/app/Services/GrantService.php`

**Estimated scope:** Medium (3-5 files)

## Task 5.3: Groups + succession

**Description:** Group CRUD, membership (≠ permission, RULE-009), ownership succession on owner removal → owner's manager, else any Admin (RULE-012; tie-break deferred per spec).

**Acceptance criteria:**
- [ ] Member without grant gets 403 on guarded action; succession chain verified by test

**Verification:**
- [ ] `vendor/bin/pest --filter="GroupTest"` green

**Dependencies:** Task 5.2

**Files likely touched:**
- `Modules/AccessManagement/*` (models, controllers, policies)

**Estimated scope:** Medium (3-5 files)

### Checkpoint: After Phase 5
- [ ] Full delegation E2E (grant → use → revoke → audit readable)
- [ ] **Review gate:** pattern review (service boundaries, no logic in models) + arch review (explicit-team rule grep-clean)
- [ ] Human sign-off before Phase 6

---

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

---

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

---

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

---

## Phase 9 — Quizzes (spec 6.5, SCOPE-005, RULE-017)

## Task 9.1: Quiz + question authoring

**Description:** Quiz CRUD (open/close window, `max_attempts`, `%` threshold of dynamic points sum), questions with per-question `points` and 5 types (choice ×2, true/false, short-answer multi-variant, numeric + tolerance).

**Acceptance criteria:**
- [ ] Mixed-points quiz totals correctly; threshold stays percentage after question edits

**Verification:**
- [ ] `vendor/bin/pest --filter="QuizAuthoring"` green

**Dependencies:** Phase 7

**Files likely touched:**
- `Modules/Quizzes/*`

**Estimated scope:** Medium (3-5 files)

## Task 9.2: Attempts + auto-scoring

**Description:** Start (window check at start), answer, submit → auto-score per type; attempt cap enforced; `score` recorded per attempt.

**Acceptance criteria:**
- [ ] Start after `closes_at` → 422; over-cap attempt → 422; each type scores correctly (incl. numeric tolerance, multi-variant text)

**Verification:**
- [ ] `vendor/bin/pest --filter="QuizAttempt"` green

**Dependencies:** Task 9.1

**Files likely touched:**
- `Modules/Quizzes/app/Services/ScoringService.php` + controllers

**Estimated scope:** Medium (3-5 files)

### Checkpoint: After Phase 9
- [ ] Full quiz round-trip E2E; review gate; human sign-off before Phase 10

---

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

---

## Phase 11 — Notifications (spec 6.7)

## Task 11.1: Event-driven queued notifications

**Description:** Listeners on domain events (`UserRegistered`, `InstructorApproved`, `CoursePublished`, `AssignmentGraded`, `PermissionGranted`, …) → queued jobs → `notifications` rows; read/unread API; mailer `log` local.

**Acceptance criteria:**
- [ ] No synchronous sends in request cycle; failed jobs retry via queue; user sees only own notifications

**Verification:**
- [ ] `vendor/bin/pest --filter="NotificationTest"` (fake queue) green

**Dependencies:** Phases 4–10 (events must exist)

**Files likely touched:**
- `Modules/Notifications/*`, listeners in owning modules

**Estimated scope:** Medium (3-5 files)

### Checkpoint: After Phase 11
- [ ] Notification received for grading event E2E; review gate; human sign-off before Phase 12

---

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

---

## Phase 13 — Integration & Security hardening

## Task 13.1: End-to-end journeys

**Description:** Pest E2E: student full journey + instructor full journey + delegation journey against SQLite; edge-case matrix from spec §2.8 all covered.

**Acceptance criteria:**
- [ ] All §2.8 edge cases have a failing-before/passing-after test record

**Verification:**
- [ ] Full suite green; manual walkthrough on staging

**Dependencies:** Phases 4–12

**Files likely touched:**
- `tests/Feature/Journeys/*.php`

**Estimated scope:** Medium (3-5 files)

## Task 13.2: OWASP + audit pass

**Description:** Per `docs/handbook/05-security-policy.md`: authz matrix audit (every route has policy/gate test), rate limits on auth endpoints, secrets scan (no keys in repo), audit-ledger completeness (every grant/revoke in both ledgers).

**Acceptance criteria:**
- [ ] Zero routes without authorization test; `git log -p | grep -i secret` clean (beyond placeholders)

**Verification:**
- [ ] Security checklist signed in PR; `pint`, suite, PHPStan green

**Dependencies:** Task 13.1

**Files likely touched:**
- `routes/*.php`, `Modules/*/routes/*.php`, `config/rate-limiting` (if added)

**Estimated scope:** Small (1-2 files + review)

### Checkpoint: After Phase 13
- [ ] **Review gate:** full code + arch + pattern review across modules; human sign-off before Phase 14

---

## Phase 14 — API Documentation

## Task 14.1: Scramble completeness

**Description:** Every endpoint annotated (auth, params, responses incl. errors); `/docs/api` reviewed page-by-page against Postman-style manual calls.

**Acceptance criteria:**
- [ ] No undocumented route (`route:list` vs Scramble output diffed); all error shapes documented

**Verification:**
- [ ] Manual docs review checklist in PR

**Dependencies:** Phase 13

**Estimated scope:** Small (annotations across controllers)

### Checkpoint: After Phase 14 — docs approved; sign-off before Phase 15

---

## Phase 15 — Performance

## Task 15.1: Query + queue audit

**Description:** Pagination/search/sort on all list endpoints (per NFR); N+1 sweep (`Model::preventLazyLoading` in tests to catch); slow-query review of reports; staging queue on Redis; cache where spec allows.

**Acceptance criteria:**
- [ ] No unbounded list endpoint; report queries use indexes (§5); queue jobs process on Redis staging

**Verification:**
- [ ] Pest with lazy-loading prevention green; staging timing notes in PR

**Dependencies:** Phase 14

**Estimated scope:** Medium (3-5 files)

### Checkpoint: After Phase 15 — perf notes approved; sign-off before Phase 16

---

## Phase 16 — Deployment

## Task 16.1: Production readiness

**Description:** Per `docs/handbook/08-build-release-runbook.md`: prod `.env` matrix, `migrate --force` + seed order runbook, Filament admin bootstrap, backup + log review, rollback plan, smoke script (health, login, enroll).

**Acceptance criteria:**
- [ ] Staging deploy rehearsed from scratch using only the runbook; smoke script passes

**Verification:**
- [ ] Rehearsal log attached to PR; tag release `v1.0.0`

**Dependencies:** Phase 15

**Estimated scope:** Small (docs + scripts)

### Checkpoint: Final
- [ ] All phase checkpoints signed; release tagged; retrospective notes filed.
