# LMS — Unified BRD/PRD/Domain/Database (Laravel Stack)

**Version 2.1 — Full Standalone Document, Pre-Implementation Close-Out**
**Status:** All pre-implementation blockers resolved — cleared for Laravel migration authoring and Phase 7 (API Contracts)
**Stack:** Laravel (PHP), MySQL
**Prepared for:** Delegate Team
**Supersedes:** v2.0
**Sibling document:** `LMS_DotNet_BRD_PRD_v2.0.md` (still at v2.0 — this round of decisions is Laravel-specific; back-port review pending)

---

## Document Control

### Change Log

| Version | Change |
|---|---|
| 1.0 | Initial separate BRD and PRD |
| 1.1 | Merged doc; added Business Opportunity, Actors, Business Processes, Risks, Future Scope; resolved SCOPE-001, RULE-001 |
| 1.2 | Added Access Management module (SCOPE-002/003), Instructor approval workflow (SCOPE-004), full delegation rule set (RULE-002–010), Module Boundaries (Phase 5) |
| 2.0 | Added full Phase 6 Database Design (18 tables, ERD, all invariants/indexes/risks); resolved RULE-011–019, SCOPE-005; split into Laravel-specific and .NET-specific sibling documents |
| **2.1** | **Pre-implementation gate closed.** Variable question/assignment scoring (RULE-018 revised, new columns on `assignments`/`quizzes`/`questions`); `activity_log` formalized as table #19 (ADR-006); `manager_id` cycle prevention as a service-layer transactional check (ADR-007); Spatie Teams context strategy split by module (ADR-008); Admin seeder policy (ADR-009); `component_completions` moved to native Eloquent polymorphic relations (ADR-010) |

### How to Read This Document

Part 1 = BRD (WHY). Part 2 = PRD (WHAT). Part 3 = Domain Model. Part 4 = Module Boundaries. Part 5 = Database Design (Laravel/MySQL-specific). Part 6 = Pre-Implementation Checklist. Appendix A = full chronological Decision Log.

**Gate statement:** As of v2.1, every item that was blocking migration authoring has a recorded decision. Phase 7 (API Contracts) and Laravel migration/model authoring may both proceed. Remaining open items (Appendix A) are deferred by design, not by oversight.

---

## Part 1 — Business Requirements (BRD)

### 1.1 Executive Summary

The LMS is an online Learning Management System giving instructors full control over course creation and student management. Students enroll, work through lessons, submit assignments, take quizzes, and track progress, all exposed through a Laravel REST API. This phase excludes video hosting and SCORM/xAPI compatibility.

The system also includes a general-purpose **Access Management subsystem** — a hierarchical permission-delegation model (Admin → Managers → Subordinates) that is not part of the teaching domain, but governs who can grant/revoke platform capabilities to whom, including scoped access to **Groups**.

### 1.2 Business Problem

Educational platforms built without a solid backend architecture fail in predictable ways: scattered data, unreliable progress tracking, assignments/quizzes as afterthoughts, inconsistent reporting, fragile edge-case handling.

### 1.3 Business Opportunity

A correctly modeled LMS backend becomes a reusable reference architecture; portfolio-worthy ownership of a non-trivial domain; a foundation for Future Scope items (payments) without a rewrite.

### 1.4 Product Vision

A Laravel-based LMS backend where instructors author and manage structured courses (lessons, assignments, quizzes), students progress through them with reliable tracking, and admins retain full platform oversight — all through a consistent, well-documented, RESTful API, with a delegated organizational permission model.

### 1.5 Business Objectives

- Deliver a well-architected backend that models a real LMS
- Give every team member end-to-end ownership of at least one module
- Practice translating a PRD into a Laravel API, not just writing code
- Build something portfolio-worthy — API docs, ERD, and tests

### 1.6 Stakeholders

| Stakeholder | Responsibility |
|---|---|
| Product Owner | Defines requirements, approves scope |
| Admin | Platform-wide oversight; root of the authority tree; approves Instructor registrations; grants/revokes permissions |
| Manager | Any user with subordinates in the authority tree — includes Admin and any user delegated authority over others |
| Instructor | Creates and manages courses, lessons, assignments, quizzes; may hold elevated permissions |
| Student | Enrolls, learns, submits work, tracks progress; not part of the authority tree |
| Backend Team | Designs and implements the API; owns modules |

### 1.7 Actors

**Human:** Student · Instructor (Pending Approval) · Instructor (Approved) · Manager/Subadmin · Admin
**Technical/External:** Email Verification Service · MySQL · Notification Queue Worker · Redis

### 1.8 Business Processes

Account Registration & Verification · Instructor Approval Workflow · Course Authoring & Publishing · Enrollment · Lesson Consumption & Progress Tracking · Assignment Submission & Grading · Quiz Attempt & Scoring · Notification Dispatch · Reporting · Permission Grant/Revoke (audited) · Group Creation & Membership · Manager Reassignment · Group Ownership Succession

### 1.9 Business Rules (Consolidated — Full List, v2.1)

- Accounts must verify email before enrolling or publishing content.
- Instructor accounts additionally require Admin approval before creating/publishing courses; may log in and browse while pending (SCOPE-004).
- Every sensitive action must be tied to an authenticated, authorized user.
- Only instructors can create courses; only the owning instructor or Admin can edit/archive.
- Published courses cannot be hard-deleted while they have active enrollments.
- A student may enroll in a course only once (DB-enforced).
- Course lifecycle is strictly linear: Draft → Published → Archived only (RULE-013).
- Removing a lesson from, or adding new content to, an already-published course requires an elevated permission not held by Instructors by default (RULE-001).
- Lesson `order` is unique per course, DB-enforced (RULE-014).
- A user has exactly one manager (`manager_id`), forming a strict tree rooted at Admin (RULE-002).
- A user may grant/revoke a permission only to/from a subordinate (direct or indirect) (RULE-003).
- A user may only grant a permission they personally hold themselves — the "ceiling rule" — for both global and group-scoped permissions (RULE-004).
- Every grant and revoke action is recorded in a permanent, append-only audit ledger (RULE-005).
- Reassigning a subordinate to a new manager does not retroactively affect permissions already granted by the old manager (RULE-006).
- A permission may be global or scoped to a specific Group (RULE-007/008).
- Group membership does not itself grant any permission (RULE-009).
- A new Instructor's `manager_id` is set to Admin automatically at approval time, not registration time (RULE-010).
- Group names have no uniqueness constraint (RULE-011).
- If a Group's owner is removed, ownership transfers to that owner's manager, or to any Admin if the owner had no manager (RULE-012).
- Students may submit an assignment multiple times only if `resubmission_allowed` is set; all attempts are preserved and independently gradable — there is no single "grade of record" (RULE-015, RULE-016).
- **[REVISED v2.1] An assignment or quiz counts as "completed" for Progress purposes only if the best attempt's score meets a passing threshold expressed as a percentage of that assignment/quiz's own total possible points — not a fixed absolute score — UNLESS an Instructor/Admin manually overrides completion (RULE-018 revised, RULE-019).** Total possible points are not uniform across components: each question carries its own point value (e.g. a 50-question quiz may total 150 points, with individual questions worth 0.5, 2, or 5 points), and each assignment carries an instructor-defined maximum score, not constrained to a 0–100 scale. This mirrors free-form scoring systems (e.g. Google Forms) by design.
- Quiz question types supported: single-choice, multiple-choice, true/false, short-answer (multi-variant accepted answers), numeric (SCOPE-005, RULE-017).
- Submission content may include a file, free text, or both, per submission; the instructor communicates what is actually expected via the assignment's free-text description field — no structured "submission type" is enforced at the schema level.
- An assignment's `due_date` is required and must be in the future at creation time (application-enforced, not database-enforced).

### 1.10 Business Constraints

- Tech stack: Laravel API, RESTful, MySQL, Git workflow
- SCORM/xAPI, video conferencing, live streaming, mobile apps, AI tutor: out of scope
- Access Management is a general-purpose subsystem, not tied to Courses/Students (SCOPE-002)
- **Accepted technical constraint (ARCH-CONSTRAINT-002):** Tymon JWT, Spatie laravel-permission (Teams mode), Redis, spatie/laravel-activitylog, dedoc/scramble, Pest/PHPUnit, Laravel Pint

### 1.11 Assumptions

- All users have reliable internet access
- Email verification is available as a dependency
- Course videos, if used, are hosted externally
- A student may enroll in many courses; a course holds many students
- SCORM/xAPI deferred
- ~~Assignment grades are on a 0–100 comparable scale~~ — **retired in v2.1**; scores are now free-form per-component with percentage-based thresholds (see RULE-018 revised)

### 1.12 Risks

| Risk | Source | Impact | Status |
|---|---|---|---|
| Duplicate/concurrent enrollment | Edge Cases | Data integrity | **Closed** — DB unique index (§5.8) |
| Course deleted with active students | Edge Cases | Broken progress | Mitigated — archive blocked if active enrollments exist |
| Late assignment submission | Edge Cases | Grading disputes | Mitigated — `is_late` computed per-attempt |
| Quiz attempted after window closes | Edge Cases | Assessment integrity | Mitigated — window check at attempt-start |
| Lesson removed after completion | Edge Cases | Progress miscalculation | Mitigated — `component_completions` references `component_id`, not position |
| Email verification unavailable | Assumptions | Platform-wide block | Open — no fallback defined |
| Notification queue backlog | Feature 6.7 | Missed notifications | Open — monitoring deferred to Phase 11/12 |
| Published content edited without permission check enforced server-side | RULE-001 | Unauthorized changes | Mitigated — Policy-layer check required |
| Concurrent duplicate submissions/attempts when resubmission disallowed | New | Data integrity | **Closed** — `attempt_number` unique composite index (RULE-015) |
| Deep `manager_id` tree traversal performance at scale | Domain Model | Performance | Deferred — not needed at current scale |
| Cycles in `manager_id` chain | §6.2 | Data corruption | **Closed in v2.1** — service-layer transactional check (ADR-007) |
| Undocumented package-owned table in production | New, v2.1 | Audit/schema drift | **Closed in v2.1** — `activity_log` formalized (ADR-006) |
| Stale Spatie Teams context in multi-group operations (e.g. Reporting) | New, v2.1 | Incorrect permission evaluation | **Closed in v2.1** — explicit-context mandate for AccessManagement/Reporting (ADR-008) |

### 1.13 Success Metrics

- A student can register, verify, enroll, and complete a course end-to-end
- Assignment and quiz submissions are never lost or double-counted
- Core endpoints respond within acceptable targets under load
- The full happy path works reliably in staging

### 1.14 Scope

| In Scope (Now) | Out of Scope | Future Scope |
|---|---|---|
| Authentication & authorization | Video conferencing | Payment processing |
| Access Management (delegation, Groups) | SCORM / xAPI | Bulk import (flagged, undecided) |
| Course management | Live streaming | Cache administration endpoints (flagged, undecided) |
| Lessons | Mobile apps | |
| Assignments | AI tutor | |
| Quizzes | | |
| Progress tracking | | |
| Email notifications | | |
| Reporting | | |
| Instructor approval workflow | | |

---

## Part 2 — Product Requirements (PRD)

### 2.1–2.5 Product Overview, Target Users, Personas, User Problems, User Journeys

Student/Instructor/Admin personas as defined in v1.1/v1.2. Primary Student journey: Guest → Register → Verify → Login → Browse → Enroll → Complete → Submit → Quiz → Certificate. Secondary Instructor journey includes the approval gate: Register → Verify → **[Pending: browse-only]** → Admin Approves → Create Course → Publish → ...

**Student**
- *Goals:* Learn at their own pace, track progress across courses, get fast feedback on quizzes.
- *Pain points:* Confusing navigation, missed deadlines with no warning, progress that resets.

**Instructor**
- *Goals:* Build courses quickly, grade assignments with ease, see student progress.
- *Pain points:* No visibility into who's stuck, editing published content is risky, grading is manual and easy to lose.

**Admin**
- *Goals:* Oversee the platform at a glance, manage users and roles, trust reporting.
- *Pain points:* No single source of truth, inconsistent audit trail.

### 2.6 Features (Full List)

| # | Feature | Key Business Rules |
|---|---|---|
| 6.1 | Authentication & Authorization | Email verification required; Instructor approval gate (SCOPE-004); role-protected endpoints |
| 6.2 | Course Management | Linear lifecycle (RULE-013); duplicate names rejected per-instructor; elevated permission for published-content edits (RULE-001) |
| 6.3 | Lessons | Unique order per course (RULE-014); reordering doesn't break progress |
| 6.4 | Assignments | Due date required and future-dated (app-enforced); resubmission flag; all attempts preserved (RULE-015/016); instructor-defined `max_score` and `passing_threshold` (v2.1) |
| 6.5 | Quizzes | Open/close window; configurable max attempts; 5 auto-scorable question types (SCOPE-005); per-question `points` and quiz-level `passing_threshold` as a percentage of total (v2.1) |
| 6.6 | Progress Tracking | Per-enrollment; percentage-threshold-based completion with override (RULE-018 revised/019) |
| 6.7 | Email Notifications | Queued, not synchronous; read/unread tracked |
| 6.8 | Reporting | Instructor scoped to own courses; Admin platform-wide; always uses explicit Spatie Teams context (ADR-008) |
| 6.9 | Access Management | Subordinate-only, ceiling-enforced delegation (RULE-002–010); manager reassignment cycle-checked (ADR-007) |
| 6.10 | Group Management | Membership ≠ permission; ownership succession on removal (RULE-012) |

### 2.7 User Journeys (Detail)

**Student:** register → verify email → enroll in course → progress through lessons in order → submit assignment (file/text/both, per instructor instructions) → take quiz (auto-scored against per-question points) → progress record updates against percentage threshold → course marked complete (or instructor override applied).

**Instructor:** register → verify email → await Admin approval (browse-only until approved) → create course (draft) → add lessons in order → publish → add assignments (set `max_score`, `passing_threshold`, `due_date`, free-text submission instructions) → add quizzes (add questions with individual `points`, set quiz `passing_threshold`) → grade submissions → optionally override completion status.

**Admin:** approve pending instructors → oversee all courses/groups → grant/revoke permissions to subordinates within the ceiling rule → resolve group ownership succession when triggered → platform-wide reporting.

### 2.8 Edge Cases (Full List)

Enrollment double-submit · Course deletion with active students · Late assignment · Expired quiz window · Lesson removal after completion · Concurrent enrollment · Manager grants to non-subordinate · Manager grants unheld permission · Manager reassignment mid-grant-history · Pending Instructor attempts to publish · **Manager reassignment would create a cycle in the authority tree (v2.1 — rejected via ADR-007)** · **Reporting operation spans multiple Groups in a single request (v2.1 — handled via ADR-008 explicit context)**

### 2.9 Non-Functional Requirements

| Category | Requirements |
|---|---|
| Security | Auth, authorization, validation, audit logs |
| Performance | Pagination, search, filtering, sorting |
| Scalability | Queued jobs for notifications, caching |
| Reliability | Structured logging, consistent error handling, backups |
| Maintainability | SOLID principles, clean architecture, API docs |

### 2.10 Deliverables

- Source code
- API documentation
- End-to-end web application
- Final presentation

### 2.11 Evaluation Criteria

| Area | Criteria |
|---|---|
| Business logic | Rules correctly enforced, edge cases handled |
| Security | Auth, validation, access control (OWASP top 10) |
| API design | Consistent, RESTful, well-documented |
| Database | Sound schema, correct relationships, indexing |

---

## Part 3 — Domain Model (Access Management)

### 3.1 Entities

```
User { manager_id (self-FK, nullable), approval_status (Instructor-only) }
Group { owner_id }
GroupMember (pivot)
PermissionGrant { granter_id, grantee_id, group_id (nullable), permission_name, action, timestamps }
```

### 3.2 Full Domain Rule Set

RULE-002 (strict tree) · RULE-003 (subordinate-only) · RULE-004 (ceiling) · RULE-005 (audit both directions) · RULE-006 (reassignment non-retroactive) · RULE-007/008 (global vs. group-scoped) · RULE-009 (membership ≠ permission) · RULE-010 (manager_id set at approval) · RULE-011 (no name uniqueness) · RULE-012 (ownership succession, with Admin fallback)

### 3.3 Authorization Check Flow (v2.1 — Team-Context Aware)

```
grant(permission=P, to=B, group=G?)
  → A authenticated & holds users.manage_roles? No → 403
  → B is subordinate of A? No → 403
  → A holds P themselves, checked EXPLICITLY against group=G
    (never against an ambient/global team context)? No → 403
  → Spatie: assign P to B, team_id explicitly set to G for this call
  → PermissionGrant record created
  → PermissionGranted event → activitylog + notification
```

The explicit-context requirement is deliberate — see ADR-008 (Part 6.2) for why grant/revoke and Reporting never rely on `setPermissionsTeamId()` ambient state, while course/lesson/assignment/quiz operations safely do.

### 3.4 State Machine — Permission Grant

`grant → [Active] → revoke → [Revoked]` (terminal, immutable ledger)

### 3.5 Manager Reassignment — Cycle Safety (New in v2.1, see ADR-007)

```
reassign(user=U, new_manager=M)
  → BEGIN TRANSACTION
  → SELECT ... FOR UPDATE on U's row
  → walk M.manager_id chain upward to root (Admin)
  → if U appears anywhere in that chain → ROLLBACK, throw CircularManagerAssignmentException
  → else → U.manager_id = M, COMMIT
```

---

## Part 4 — Module Boundaries

### 4.1 Modules

```
Auth · AccessManagement · Courses · Enrollment · Assignments · Quizzes · Progress · Notifications · Reporting
```

### 4.2 Dependency Graph

```
Auth → AccessManagement → Courses → Enrollment → {Assignments, Quizzes} → Progress → Notifications
                              ↑___________________________________________|
Reporting (read-only) → Courses, Enrollment, Progress, Assignments, Quizzes
```

### 4.3 Module Summary

| Module | Owns | Key Dependency | Publishes |
|---|---|---|---|
| Auth | User identity, approval_status | — | UserRegistered, UserVerified, InstructorApproved |
| AccessManagement | manager_id, PermissionGrant, Group, GroupMember | Auth | PermissionGranted, PermissionRevoked, ManagerReassigned |
| Courses | Course, Lesson | Auth, AccessManagement | CoursePublished, CourseArchived, LessonAdded, LessonRemoved |
| Enrollment | Enrollment | Auth, Courses | StudentEnrolled |
| Assignments | Assignment, Submission | Courses, Enrollment | AssignmentSubmitted, AssignmentGraded |
| Quizzes | Quiz, Question, Attempt | Courses, Enrollment | QuizAttempted |
| Progress | ProgressRecord, ComponentCompletion | Enrollment, Courses, Assignments, Quizzes | CourseCompleted |
| Notifications | Notification | (listens to many) | — |
| Reporting | — (read-only) | Courses, Enrollment, Progress, Assignments, Quizzes | — |

### 4.4 Module-to-Spatie-Teams-Strategy Map (New in v2.1, see ADR-008)

| Module | Team Context Strategy |
|---|---|
| Courses, Enrollment, Assignments, Quizzes, Progress | Ambient middleware (`setPermissionsTeamId()`) — single Group per request lifecycle |
| AccessManagement | Explicit per-call — mandatory, ceiling-rule sensitive |
| Reporting | Explicit per-call — mandatory, multi-Group per request |
| Auth, Notifications | Not Group-scoped — N/A |

---

## Part 5 — Database Design (Laravel / MySQL)

### 5.1 ID Strategy

**ARCH-005**: Auto-increment integers for ALL tables, no exceptions. Trade-off accepted: enumeration is possible on exposed resources; mitigation (rate limiting / public slugs) deferred to Phase 10.

### 5.2 `users`

| Column | Type | Null | Notes |
|---|---|---|---|
| id | bigint unsigned, AI | No | PK |
| name | varchar(255) | No | |
| email | varchar(255) | No | Unique |
| email_verified_at | timestamp | Yes | |
| password | varchar(255) | No | |
| manager_id | bigint unsigned | Yes | Self-FK → users.id |
| approval_status | enum(pending,approved) | Yes | Instructor-only |
| created_at / updated_at | timestamp | — | |

**Indexes:** unique(email); index(manager_id)
**Invariants:** Student.manager_id always NULL; Instructor.manager_id NULL until approved; no cycles in manager_id chain — **enforced at the service layer, not the database, per ADR-007 (Part 6.1)**

### 5.3 `permission_grants`

| Column | Type | Null | Notes |
|---|---|---|---|
| id | bigint unsigned, AI | No | PK |
| granter_id / grantee_id | bigint unsigned | No | FK → users.id |
| group_id | bigint unsigned | Yes | FK → groups.id; NULL = global |
| permission_name | varchar(255) | No | |
| action | enum(granted,revoked) | No | |
| granted_at / revoked_at | timestamp | Yes | |
| created_at | timestamp | — | Append-only, no updated_at |

**Indexes:** index(grantee_id); composite(granter_id, grantee_id); index(group_id)
**Invariants:** append-only ledger; every row satisfies RULE-003 + RULE-004 at write time, evaluated with explicit Group context (ADR-008)

### 5.4 `groups`

| Column | Type | Null | Notes |
|---|---|---|---|
| id | bigint unsigned, AI | No | PK; also Spatie team_id |
| owner_id | bigint unsigned | No | FK → users.id |
| name | varchar(255) | No | No uniqueness (RULE-011) |
| created_at / updated_at | timestamp | — | |

**Indexes:** index(owner_id)
**Invariants:** ownership succession per RULE-012 on owner removal

### 5.5 `group_members`

| Column | Type | Null | Notes |
|---|---|---|---|
| id | bigint unsigned, AI | No | PK |
| group_id / user_id | bigint unsigned | No | FK |
| joined_at | timestamp | No | |

**Indexes:** unique(group_id, user_id); index(user_id)

### 5.6 `courses`

| Column | Type | Null | Notes |
|---|---|---|---|
| id | bigint unsigned, AI | No | PK |
| instructor_id | bigint unsigned | No | FK → users.id |
| title | varchar(255) | No | |
| category | varchar(255) | No | |
| description | text | Yes | |
| status | enum(draft,published,archived) | No | Default draft |
| published_at / archived_at | timestamp | Yes | |
| created_at / updated_at | timestamp | — | |

**Indexes:** unique(instructor_id, title); index(status); index(instructor_id)
**Invariants:** RULE-013 linear lifecycle; cannot archive with active enrollments; elevated permission required for published-content structural edits (RULE-001)

### 5.7 `lessons`

| Column | Type | Null | Notes |
|---|---|---|---|
| id | bigint unsigned, AI | No | PK |
| course_id | bigint unsigned | No | FK |
| title | varchar(255) | No | |
| content_reference | text | Yes | External content pointer |
| order | unsigned int | No | |
| created_at / updated_at | timestamp | — | |

**Indexes:** unique(course_id, order) — RULE-014; index(course_id)

### 5.8 `enrollments`

| Column | Type | Null | Notes |
|---|---|---|---|
| id | bigint unsigned, AI | No | PK |
| student_id | bigint unsigned | No | FK |
| course_id | bigint unsigned | No | FK |
| status | enum(active,completed) | No | Default active |
| enrolled_at / completed_at | timestamp | Yes/No | |

**Indexes:** **unique(student_id, course_id)** — closes concurrent-enrollment risk; index(course_id); index(student_id)

### 5.9 `assignments` (amended in v2.1)

| Column | Type | Null | Notes |
|---|---|---|---|
| id | bigint unsigned, AI | No | PK |
| course_id | bigint unsigned | No | FK |
| title | varchar(255) | No | |
| description | text | Yes | Also carries free-form submission instructions (file vs. text vs. both) set by the instructor — no separate structured column |
| due_date | timestamp | **No** | Required; app-level validation enforces future-dated at creation time (not DB-enforced) |
| resubmission_allowed | boolean | No | Default false |
| **max_score** | **decimal(6,2)** | **No** | **New in v2.1.** Instructor-defined ceiling; not constrained to 0–100 |
| **passing_threshold** | **decimal(5,2)** | **No** | **New in v2.1.** Percentage of `max_score`, e.g. `60.00` = 60% |
| created_at / updated_at | timestamp | — | |

**Indexes:** index(course_id); index(due_date)

### 5.10 `submissions`

| Column | Type | Null | Notes |
|---|---|---|---|
| id | bigint unsigned, AI | No | PK |
| assignment_id / enrollment_id | bigint unsigned | No | FK |
| attempt_number | unsigned int | No | RULE-015 |
| file_path | varchar(255) | Yes | Both `file_path` and `content` remain nullable — either, both, or neither per submission, per instructor instructions in `assignments.description` (confirmed v2.1, no schema change) |
| content | text | Yes | |
| submitted_at | timestamp | No | |
| is_late | boolean | No | Per-attempt |
| grade | decimal(5,2) | Yes | |
| graded_by / graded_at | bigint unsigned / timestamp | Yes | |
| status | enum(submitted,graded) | No | |

**Indexes:** **unique(assignment_id, enrollment_id, attempt_number)** — RULE-015; index(assignment_id); index(enrollment_id)
**Invariants:** RULE-016 — no single grade-of-record; all attempts independently visible

### 5.11 `quizzes` (amended in v2.1)

| Column | Type | Null | Notes |
|---|---|---|---|
| id | bigint unsigned, AI | No | PK |
| course_id | bigint unsigned | No | FK |
| title | varchar(255) | No | |
| opens_at / closes_at | timestamp | No | |
| max_attempts | unsigned int | No | Default TBD |
| **passing_threshold** | **decimal(5,2)** | **No** | **New in v2.1.** Percentage of `SUM(questions.points)` for this quiz — computed dynamically at evaluation time, never cached, so re-authoring questions never silently invalidates the threshold |
| created_at / updated_at | timestamp | — | |

**Indexes:** index(course_id); index(opens_at, closes_at)

### 5.12 `questions` (amended in v2.1)

| Column | Type | Null | Notes |
|---|---|---|---|
| id | bigint unsigned, AI | No | PK |
| quiz_id | bigint unsigned | No | FK |
| prompt | text | No | |
| type | enum(single_choice,multiple_choice,true_false,short_answer,numeric) | No | SCOPE-005 |
| order | unsigned int | No | |
| **points** | **decimal(6,2)** | **No** | **New in v2.1.** Default `1.00`. Per-question weight — enables mixed scoring (e.g. 0.5, 2, 5) within one quiz |
| correct_boolean | boolean | Yes | true_false only |
| correct_number | decimal(10,4) | Yes | numeric only |
| numeric_tolerance | decimal(10,4) | Yes | numeric only, default 0 |

**Indexes:** index(quiz_id); composite(quiz_id, order)

### 5.13 `question_options`

| Column | Type | Null | Notes |
|---|---|---|---|
| id | bigint unsigned, AI | No | PK |
| question_id | bigint unsigned | No | FK |
| label | varchar(255) | No | |
| is_correct | boolean | No | |
| order | unsigned int | No | |

**Indexes:** index(question_id)

### 5.14 `question_accepted_answers`

| Column | Type | Null | Notes |
|---|---|---|---|
| id | bigint unsigned, AI | No | PK |
| question_id | bigint unsigned | No | FK, short_answer only |
| answer_text | varchar(255) | No | RULE-017 |

**Indexes:** index(question_id)

### 5.15 `attempts`

| Column | Type | Null | Notes |
|---|---|---|---|
| id | bigint unsigned, AI | No | PK |
| quiz_id / enrollment_id | bigint unsigned | No | FK |
| attempt_number | unsigned int | No | |
| score | decimal(5,2) | Yes | Sufficient precision even at high point totals (e.g. 150) — no change needed in v2.1 |
| started_at | timestamp | No | |
| submitted_at | timestamp | Yes | |

**Indexes:** **unique(quiz_id, enrollment_id, attempt_number)**; index(enrollment_id)

### 5.16 `attempt_answers`

| Column | Type | Null | Notes |
|---|---|---|---|
| id | bigint unsigned, AI | No | PK |
| attempt_id / question_id | bigint unsigned | No | FK |
| selected_option_ids | json | Yes | |
| answer_boolean | boolean | Yes | |
| answer_text | varchar(255) | Yes | |
| answer_number | decimal(10,4) | Yes | |
| is_correct | boolean | No | |

**Indexes:** index(attempt_id); unique(attempt_id, question_id)

### 5.17 `progress_records`

| Column | Type | Null | Notes |
|---|---|---|---|
| id | bigint unsigned, AI | No | PK |
| enrollment_id | bigint unsigned | No | FK, 1-to-1 |
| percent_complete | decimal(5,2) | No | Default 0 |
| completed_at | timestamp | Yes | |
| updated_at | timestamp | — | |

### 5.18 `component_completions` (amended in v2.1 — ADR-010)

| Column | Type | Null | Notes |
|---|---|---|---|
| id | bigint unsigned, AI | No | PK |
| enrollment_id | bigint unsigned | No | FK |
| component_type | enum(lesson,assignment,quiz) | No | Polymorphic discriminator |
| component_id | bigint unsigned | No | Polymorphic target — disambiguated by `component_type` |
| completed_at | timestamp | Yes | |
| is_override | boolean | No | Default false — RULE-019 |
| overridden_by | bigint unsigned | Yes | FK → users.id |

**Indexes:** unique(enrollment_id, component_type, component_id); index(enrollment_id)
**Invariants:** RULE-018 revised (percentage-threshold-based completion) + RULE-019 (instructor override)

**v2.1 model-layer change (ADR-010):** columns unchanged, but this relation is now implemented as a native Eloquent polymorphic relation (`morphTo()` on `ComponentCompletion`, `morphMany()` on `Lesson`/`Assignment`/`Quiz`) rather than an app-enforced manual lookup. This does **not** add database-level referential integrity across the three target tables — that remains application-enforced regardless of ORM sugar — it only removes boilerplate type-switch code. Phase 7 API contracts should reference `$completion->component`, not manual type-switch lookups.

### 5.19 `notifications`

| Column | Type | Null | Notes |
|---|---|---|---|
| id | bigint unsigned, AI | No | PK |
| user_id | bigint unsigned | No | FK |
| type | varchar(255) | No | Maps to domain events |
| data | json | No | |
| read_at | timestamp | Yes | |
| created_at | timestamp | — | |

**Indexes:** index(user_id, read_at)

### 5.20 `activity_log` (new in v2.1 — ADR-006)

| Column | Type | Null | Notes |
|---|---|---|---|
| id | bigint unsigned, AI | No | PK |
| log_name | varchar(255) | Yes | Package default |
| description | text | No | |
| subject_type / subject_id | varchar(255) / bigint unsigned | Yes | Polymorphic — the entity the activity happened to |
| causer_type / causer_id | varchar(255) / bigint unsigned | Yes | Polymorphic — who/what performed it |
| event | varchar(255) | Yes | created/updated/deleted, or custom |
| properties | json | Yes | Before/after diff or custom payload |
| batch_uuid | uuid | Yes | Groups related log entries |
| created_at / updated_at | timestamp | — | |

**Status:** Package-owned (`spatie/laravel-activitylog`) — schema generated by the package's own migration, reproduced here for documentation completeness so it isn't mistaken for an undocumented table in production.
**Invariant:** satisfies RULE-005 (audit ledger) jointly with `permission_grants` — `permission_grants` is the authoritative, queryable ledger for grant/revoke specifically (RULE-004-checkable); `activity_log` is the general-purpose supplementary trail across all models.

### 5.21 Full Table List (19 — revised in v2.1)

```
users, permission_grants, groups, group_members,
courses, lessons, enrollments,
assignments, submissions,
quizzes, questions, question_options, question_accepted_answers,
attempts, attempt_answers,
progress_records, component_completions,
notifications,
activity_log
```

---

## Part 6 — Pre-Implementation Checklist

This section exists so no engineer starts `php artisan make:migration` against a document with silent gaps. Each item below was an open risk in v2.0 and is now closed in v2.1.

### 6.1 Cycle Prevention on `users.manager_id` — ADR-007

**Not implemented as a database constraint.** MySQL cannot enforce acyclicity on a self-referencing tree via FK/CHECK constraints without recursive logic the engine doesn't natively support at write time.

**Implementation (service layer, not model layer):**

1. Any operation that changes `manager_id` (initial assignment at approval, or later reassignment) runs inside a DB transaction.
2. `SELECT ... FOR UPDATE` locks the target user's row for the duration of the check, preventing a race between two concurrent reassignments.
3. Walk the proposed new manager's `manager_id` chain upward to the root (Admin).
4. If the user being reassigned appears anywhere in that chain, abort the transaction and throw a dedicated `CircularManagerAssignmentException` — never a silent no-op, never a generic validation error.
5. This lives in an `AccessManagement` service class (e.g. `ManagerAssignmentService`), not in an Eloquent model event, so it's explicitly invoked and testable in isolation.

### 6.2 Spatie Teams Context Strategy — ADR-008

Two mechanisms exist to tell Spatie "which Group are we operating in": ambient middleware-set context (`setPermissionsTeamId()`) versus explicit per-call context. Both are used, split by module:

| Module | Strategy | Rationale |
|---|---|---|
| Courses, Lessons, Assignments, Quizzes (student/instructor-facing reads & writes) | **Ambient middleware** | Every request in these modules is scoped to exactly one Group for its entire lifecycle (derivable from the route) — safe to set once |
| AccessManagement (grant/revoke) | **Explicit per-call** | Ceiling-rule checks (RULE-004) must never be evaluated against an assumed context; the Group being granted into is passed as an explicit parameter to every permission check, no exceptions |
| Reporting | **Explicit per-call, mandatory** | Reports routinely iterate multiple Groups in one request; ambient context would require reassignment mid-loop, which is the exact failure mode (silent stale-context reads) this split is designed to prevent |

**Enforcement note:** code review for AccessManagement and Reporting modules must reject any `hasPermissionTo()` call that does not pass an explicit team parameter — this is a lint-able convention, not just a guideline, and should be called out in the Phase 7 API contract review checklist.

### 6.3 Admin Seeder — ADR-009

`DatabaseSeeder` creates exactly one root Admin before any other seeder runs. Credentials sourced from `.env` (`ADMIN_SEED_EMAIL`, `ADMIN_SEED_PASSWORD`), never hardcoded. This Admin's `manager_id` is `NULL` by definition — it is the root of the authority tree referenced throughout RULE-002/010/012. All environment setup (local, staging) must run this seeder first; documented as a hard prerequisite in the eventual README/runbook, not assumed knowledge.

### 6.4 Confirmed Items, No Schema Impact

- `due_date` — required, future-dated at creation (app-level validation, not a DB constraint; MySQL has no notion of "now" at write time worth trusting for this).
- Submission content shape — both `file_path` and `content` remain nullable; instructor communicates expected format via free-text `assignments.description`. No structured "submission type" column introduced — deliberately kept out of scope to avoid over-modeling a decision the instructor should make in prose per-assignment.

### 6.5 Variable Scoring Model — Schema Impact Summary

| Table | New Column | Purpose |
|---|---|---|
| `questions` | `points` (decimal 6,2, default 1.00) | Per-question weight |
| `quizzes` | `passing_threshold` (decimal 5,2) | Percentage of dynamically-summed question points |
| `assignments` | `max_score` (decimal 6,2) | Instructor-defined ceiling, unconstrained |
| `assignments` | `passing_threshold` (decimal 5,2) | Percentage of `max_score` |

No change required to `attempts.score` or `submissions.grade` (`decimal(5,2)` already accommodates realistic point totals).

---

## Appendix A — Decision Log (Chronological, Full)

| ID | Decision |
|---|---|
| SCOPE-001 | Payment Processing → Future Scope |
| RULE-001 | Published-content editing requires elevated permission |
| ARCH-NOTE-001 | Closed — superseded by ARCH-CONSTRAINT-002 |
| ARCH-CONSTRAINT-002 | Adopted external Laravel stack (Tymon, Spatie, Redis, Activitylog, Scramble) |
| SCOPE-002 | Access Management as separate module |
| RULE-002–006 | Tree structure, subordinate-only grants, symmetric audit, reassignment non-retroactive |
| SCOPE-003 | Group as first-class scoped entity |
| ARCH-004 | Spatie Teams for group-scoped permissions |
| MOD-001 | Assignments/Quizzes remain separate modules |
| RULE-004 | Ceiling rule — resolved, Ceiling Enforced |
| SCOPE-004 | Instructor approval workflow |
| RULE-010 | manager_id set at approval, not registration |
| ARCH-005 | Auto-increment PKs, everywhere |
| RULE-011 | Group name — no uniqueness |
| RULE-012 | Group ownership succession (manager, or any Admin as fallback) |
| RULE-013 | Course lifecycle strictly linear |
| RULE-014 | Lesson order unique per course, DB-enforced |
| RULE-015 | Submission race-condition prevention via attempt_number |
| RULE-016 | No single grade-of-record; all attempts independently visible |
| SCOPE-005 | Full auto-scorable question type set (5 types) |
| RULE-017 | Short-answer supports multiple accepted answers |
| RULE-018 (original, superseded) | Completion requires passing threshold on best attempt (0–100 scale assumed) |
| RULE-019 | Instructor override for component completion |
| **RULE-018 (revised, v2.1)** | **Completion threshold is a percentage of variable, per-component total points — not a fixed 0–100 scale. `questions.points`, `quizzes.passing_threshold`, `assignments.max_score`, `assignments.passing_threshold` added.** |
| **ADR-006** | **`activity_log` formalized as table #19, package-owned (Spatie), schema reproduced for documentation completeness.** |
| **ADR-007** | **`manager_id` cycle prevention implemented as a service-layer transactional check (`SELECT...FOR UPDATE` + upward walk + `CircularManagerAssignmentException`), not a DB constraint.** |
| **ADR-008** | **Spatie Teams context: ambient middleware for single-Group request lifecycles (Courses/Lessons/Assignments/Quizzes); mandatory explicit per-call context for AccessManagement and Reporting.** |
| **ADR-009** | **Admin seeder runs first, credentials from `.env`, `manager_id` NULL by definition.** |
| **ADR-010** | **`component_completions` implemented via native Eloquent `morphTo()`/`morphMany()`; no DB-level referential integrity change — code-layer only.** |

### Open Items (Not Yet Decided — Genuinely Deferred, Not Blockers)

- Bulk import (users/enrollment)
- Cache administration endpoints
- Password reset flow
- Archived → Published (republishing) — never confirmed either way
- Group ownership "any Admin" tie-break rule — deferred to Phase 8
- Whether `timestamp with time zone` (as adopted in the .NET sibling doc) should be back-ported to this MySQL schema — flagged, not decided
- Whether v2.1's variable-scoring model should be back-ported to the .NET sibling document — pending, since the .NET doc is still at v2.0

---

*End of Document — v2.1 (Laravel) — Cleared for Phase 7 (API Contracts) and migration authoring.*
