# LMSDB (Laravel v2.1) — 3NF / BCNF Relational Schema Mapping

## 1. Mapping Methodology & Visual Conventions

This relational mapping translates the conceptual Chen ERD (`docs/ERD/description.md`) into the exact **20-table physical schema** implemented across `database/migrations/` and `Modules/*/database/migrations/` (spec `v2.1` + `ADR-012`).

### Visual Diagram Notation ("Row-of-Cells")
- **Table Header:** Table name rendered directly above a contiguous horizontal row of column cells.
- **Primary Key (`PK`):** Solid underline beneath the column name inside its cell. All 20 LMS domain tables use auto-increment `bigint unsigned` surrogate `id` keys (`ARCH-005`).
- **Foreign Key (`FK`):** Dashed underline beneath the column name inside its cell.
- **Unique Index (`UQ` badge):** Red `UQ` badge in the top-right corner of cells belonging to single-column or composite `UNIQUE` indexes (such as `users.email`, `enrollments(student_id, course_id)`, `lessons(course_id, order)`).
  > *Note:* Generic `erd-builder` legend templates note filtered `WHERE DeletedAt IS NULL` indexes; in the current LMS Laravel v2.1 codebase (`ADR-015`), tables operate in hard-delete mode without `deleted_at` columns, so `UQ` denotes standard / composite `UNIQUE` constraints enforced in MySQL and SQLite.
- **Orthogonal Routing:** Arrows originate from the `FK` cell edge and route along collision-free horizontal tier corridors and vertical bus lanes directly into the referenced `PK` cell edge.

---

## 2. Schema Hardening & Design Audit (D1–D10 Mapping)

| Rule | Standard Principle | LMS Laravel v2.1 Implementation & Verification |
|---|---|---|
| **D1 & D2** | Explicit FK Delete / Update Actions | **Targeted Integrity (`ADR-011`):** User-owned historical/structural roots (`courses.instructor_id`, `permission_grants.granter_id`, `permission_grants.grantee_id`, `groups.owner_id`, `enrollments.student_id`) enforce `RESTRICT` (MySQL explicit / SQLite equivalent `NO ACTION`) to block accidental hard-delete wipes. Strictly owned children (`lessons`, `assignments`, `submissions`, `quizzes`, `questions`, `question_options`, `question_accepted_answers`, `attempts`, `attempt_answers`, `progress_records`, `component_completions`, `group_members`, `user_permissions`, `notifications`) use `ON DELETE CASCADE`. |
| **D3** | Optional / Nullable FK Handling | `users.manager_id`, `permission_grants.group_id`, `user_permissions.group_id`, `submissions.graded_by`, and `component_completions.overridden_by` are nullable FKs with `ON DELETE SET NULL`. |
| **D4 & D5** | Delete Semantics & Candidate Key Uniqueness | Currently hard-delete-only (`ADR-015`, no `deleted_at` columns in migrations). Race-closing and candidate keys are protected by DB-level `UNIQUE` indexes (`users.email`, `courses(instructor_id, title)`, `lessons(course_id, order)`, `enrollments(student_id, course_id)`, `group_members(group_id, user_id)`, `user_permissions(user_id, permission_name, group_id)`, `progress_records(enrollment_id)`). |
| **D6** | Multi-Attempt & Historical Preservation | `submissions` and `attempts` preserve every attempt forever (`RULE-015`, `RULE-016`) using surrogate `id` PKs plus composite unique triples `(assignment_id, enrollment_id, attempt_number)` and `(quiz_id, enrollment_id, attempt_number)`. |
| **D7** | Surrogate PKs for Weak Entities | Per `ARCH-005`, weak entities (`lessons`, `submissions`, `questions`, `question_options`, `attempts`, `component_completions`) use `id` (`bigint unsigned AI`) PKs paired with composite `UNIQUE` constraints on `(owner_id, discriminator)`. |
| **D8** | Reserved Keyword Avoidance | Table and column names follow Laravel plural snake_case (`users`, `groups`, `lessons`). Reserved column `order` in `lessons`, `questions`, and `question_options` is automatically quoted by Laravel Grammar/Eloquent. |
| **D9** | Domain / Enum Integrity | Portable SQLite/MySQL migrations (`docs/db-conventions.md`) store enum fields as `varchar(20/30)` validated via PHP 8.4 Backed Enums (`ApprovalStatus`, `LedgerAction`) and FormRequest/service checks (`approval_status`, `action`, `status`, `type`, `component_type`). |
| **D10** | Append-Only Audit Ledgers | `permission_grants` omits `updated_at` and never updates/deletes rows; revocations append new rows (`action = 'revoked'`, `RULE-005`). `activity_log` (`ADR-006`) acts as a package-owned audit trail pruned on a 90-day window via `created_at` index (`ADR-014`). |

---

## 3. Complete Inventory of Tables Across 5 Dependency Tiers

```text
TIER 1 (Roots & Platform):     [users]   [activity_log]   [notifications]
                                  │
TIER 2 (Scopes & Catalog):     [groups]  [permission_grants]  [courses]
                                  │             │                 │
TIER 3 (Membership & Core):    [group_members]  [user_permissions]  [lessons]  [enrollments]  [assignments]  [quizzes]
                                                                                   │               │             │
TIER 4 (Work, Progress, Qs):   [progress_records]  [component_completions]  [submissions]    [attempts]    [questions]
                                                                                                  │             │
TIER 5 (Details & Answers):    [question_options]  [question_accepted_answers]              [attempt_answers]
```

> **Additional Package & Framework Tables (11 tables, outside the 20 LMS domain tables):**
> - **Spatie Permission (Teams mode ON, 5 tables):** `permissions`, `roles`, `role_has_permissions`, `model_has_permissions`, `model_has_roles` (all team-scoped via `group_id` → `groups.id`).
> - **Laravel System & Telescope (6 tables):** `password_reset_tokens`, `sessions`, `cache`, `cache_locks`, `jobs`/`job_batches`/`failed_jobs`, `telescope_entries`/`telescope_entries_tags`/`telescope_monitoring`.

---

## 4. Step-by-Step Relational Schema Specification

### Tier 1 — Identity & Platform Roots

1. **`users`** *(Root + `Modules/Auth`)*
   - **Columns:** `id` (`bigint unsigned AI`, **PK**), `name` (`varchar(255)`), `email` (`varchar(255)`, **UQ** `users_email_unique`), `phone_number` (`varchar(30) NULL`), `email_verified_at` (`timestamp NULL`), `password` (`varchar(255)`), `remember_token` (`varchar(100) NULL`), `manager_id` (`bigint unsigned NULL`, **FK** → `users.id` `ON DELETE SET NULL`), `manager_depth` (`smallint unsigned DEFAULT 0`), `approval_status` (`varchar(20) NULL`), `created_at`, `updated_at`.
   - **Indexes & Invariants:** `users_manager_id_index`; cycle prevention enforced transactionally (`SELECT ... FOR UPDATE`, `ADR-007`); `manager_depth` recomputed across subtrees (`ADR-013`).

2. **`activity_log`** *(Root package migration — `spatie/laravel-activitylog`, `ADR-006`)*
   - **Columns:** `id` (`bigint unsigned AI`, **PK**), `log_name` (`varchar(255) NULL`), `description` (`text`), `subject_type` (`varchar(255) NULL`), `subject_id` (`bigint unsigned NULL`), `event` (`varchar(255) NULL`), `causer_type` (`varchar(255) NULL`), `causer_id` (`bigint unsigned NULL`), `attribute_changes` (`json NULL`), `properties` (`json NULL`), `created_at`, `updated_at`.
   - **Indexes:** `log_name`, `(subject_type, subject_id)`, `(causer_type, causer_id)`, `created_at` (`ADR-014` retention pruning).

3. **`notifications`** *(`Modules/Notifications`)*
   - **Columns:** `id` (`bigint unsigned AI`, **PK**), `user_id` (`bigint unsigned`, **FK** → `users.id` `ON DELETE CASCADE`), `type` (`varchar(255)`), `data` (`json`), `read_at` (`timestamp NULL`), `created_at` (`timestamp DEFAULT CURRENT_TIMESTAMP`).
   - **Indexes:** `notifications_user_id_read_at_index`.

### Tier 2 — Access Scopes & Course Catalog

4. **`groups`** *(`Modules/AccessManagement`)*
   - **Columns:** `id` (`bigint unsigned AI`, **PK**, also Spatie `team_id`), `owner_id` (`bigint unsigned`, **FK** → `users.id` `RESTRICT`), `name` (`varchar(255)` — intentionally non-unique per `RULE-011`), `created_at`, `updated_at`.
   - **Indexes:** `groups_owner_id_index`.

5. **`permission_grants`** *(`Modules/AccessManagement` — Append-Only Ledger)*
   - **Columns:** `id` (`bigint unsigned AI`, **PK**), `granter_id` (`bigint unsigned`, **FK** → `users.id` `ON DELETE RESTRICT`), `grantee_id` (`bigint unsigned`, **FK** → `users.id` `ON DELETE RESTRICT`), `group_id` (`bigint unsigned NULL`, **FK** → `groups.id` `ON DELETE SET NULL`), `permission_name` (`varchar(255)`), `action` (`varchar(20)`: `granted | revoked`), `granted_at` (`timestamp NULL`), `revoked_at` (`timestamp NULL`), `created_at` (`timestamp DEFAULT CURRENT_TIMESTAMP`, no `updated_at`).
   - **Indexes:** `grantee_id`, `(granter_id, grantee_id)`, `group_id`.

6. **`courses`** *(`Modules/Courses`)*
   - **Columns:** `id` (`bigint unsigned AI`, **PK**), `instructor_id` (`bigint unsigned`, **FK** → `users.id` `ON DELETE RESTRICT`), `title` (`varchar(255)`), `category` (`varchar(255)`), `description` (`text NULL`), `status` (`varchar(20) DEFAULT 'draft'`: `draft | published | archived`), `published_at` (`timestamp NULL`), `archived_at` (`timestamp NULL`), `created_at`, `updated_at`.
   - **Indexes:** **UQ** `(instructor_id, title)`, `status`, `instructor_id`.

### Tier 3 — Membership, Read Model, Enrollment & Course Components

7. **`group_members`** *(`Modules/AccessManagement`)*
   - **Columns:** `id` (`bigint unsigned AI`, **PK**), `group_id` (`bigint unsigned`, **FK** → `groups.id` `ON DELETE CASCADE`), `user_id` (`bigint unsigned`, **FK** → `users.id` `ON DELETE CASCADE`), `joined_at` (`timestamp DEFAULT CURRENT_TIMESTAMP`).
   - **Indexes:** **UQ** `(group_id, user_id)`, `user_id`.

8. **`user_permissions`** *(`Modules/AccessManagement` — `ADR-012` Read Model)*
   - **Columns:** `id` (`bigint unsigned AI`, **PK**), `user_id` (`bigint unsigned`, **FK** → `users.id` `ON DELETE CASCADE`), `permission_name` (`varchar(255)`), `group_id` (`bigint unsigned NULL`, **FK** → `groups.id` `ON DELETE SET NULL`), `granted_via_grant_id` (`bigint unsigned`, **FK** → `permission_grants.id` `ON DELETE CASCADE`), `created_at`, `updated_at`.
   - **Indexes:** **UQ** `(user_id, permission_name, group_id)`, `user_id`.

9. **`lessons`** *(`Modules/Courses`)*
   - **Columns:** `id` (`bigint unsigned AI`, **PK**), `course_id` (`bigint unsigned`, **FK** → `courses.id` `ON DELETE CASCADE`), `title` (`varchar(255)`), `content_reference` (`text NULL`), `order` (`int unsigned`), `created_at`, `updated_at`.
   - **Indexes:** **UQ** `(course_id, order)` (`RULE-014`), `course_id`.

10. **`enrollments`** *(`Modules/Enrollment`)*
    - **Columns:** `id` (`bigint unsigned AI`, **PK**), `student_id` (`bigint unsigned`, **FK** → `users.id` `RESTRICT`), `course_id` (`bigint unsigned`, **FK** → `courses.id` `ON DELETE CASCADE`), `status` (`varchar(20) DEFAULT 'active'`), `enrolled_at` (`timestamp NULL`), `completed_at` (`timestamp NULL`).
    - **Indexes:** **UQ** `(student_id, course_id)`, `course_id`, `student_id`.

11. **`assignments`** *(`Modules/Assignments`)*
    - **Columns:** `id` (`bigint unsigned AI`, **PK**), `course_id` (`bigint unsigned`, **FK** → `courses.id` `ON DELETE CASCADE`), `title` (`varchar(255)`), `description` (`text NULL`), `due_date` (`timestamp`), `resubmission_allowed` (`boolean DEFAULT false`), `max_score` (`decimal(6,2)`), `passing_threshold` (`decimal(5,2)`), `created_at`, `updated_at`.
    - **Indexes:** `course_id`, `due_date`.

12. **`quizzes`** *(`Modules/Quizzes`)*
    - **Columns:** `id` (`bigint unsigned AI`, **PK**), `course_id` (`bigint unsigned`, **FK** → `courses.id` `ON DELETE CASCADE`), `title` (`varchar(255)`), `opens_at` (`timestamp`), `closes_at` (`timestamp`), `max_attempts` (`int unsigned DEFAULT 1`), `passing_threshold` (`decimal(5,2)` — % of dynamic `SUM(questions.points)`), `created_at`, `updated_at`.
    - **Indexes:** `course_id`, `(opens_at, closes_at)`.

### Tier 4 — Student Work, Progress & Quiz Items

13. **`progress_records`** *(`Modules/Progress`)*
    - **Columns:** `id` (`bigint unsigned AI`, **PK**), `enrollment_id` (`bigint unsigned`, **FK** → `enrollments.id` `ON DELETE CASCADE`, **UQ**), `percent_complete` (`decimal(5,2) DEFAULT 0`), `completed_at` (`timestamp NULL`), `updated_at` (`timestamp DEFAULT CURRENT_TIMESTAMP`).
    - **Indexes:** **UQ** `enrollment_id` (enforces strict 1:1 with `enrollments`).

14. **`component_completions`** *(`Modules/Progress` — Polymorphic `ADR-010`/`ADR-015`)*
    - **Columns:** `id` (`bigint unsigned AI`, **PK**), `enrollment_id` (`bigint unsigned`, **FK** → `enrollments.id` `ON DELETE CASCADE`), `component_type` (`varchar(30)`: `lesson | assignment | quiz`), `component_id` (`bigint unsigned`), `completed_at` (`timestamp NULL`), `is_override` (`boolean DEFAULT false`), `overridden_by` (`bigint unsigned NULL`, **FK** → `users.id` `ON DELETE SET NULL`), `created_at`, `updated_at`.
    - **Indexes:** **UQ** `(enrollment_id, component_type, component_id)`, `enrollment_id`.

15. **`submissions`** *(`Modules/Assignments`)*
    - **Columns:** `id` (`bigint unsigned AI`, **PK**), `assignment_id` (`bigint unsigned`, **FK** → `assignments.id` `ON DELETE CASCADE`), `enrollment_id` (`bigint unsigned`, **FK** → `enrollments.id` `ON DELETE CASCADE`), `attempt_number` (`int unsigned`), `file_path` (`varchar(255) NULL`), `content` (`text NULL`), `submitted_at` (`timestamp DEFAULT CURRENT_TIMESTAMP`), `is_late` (`boolean DEFAULT false`), `grade` (`decimal(5,2) NULL`), `graded_by` (`bigint unsigned NULL`, **FK** → `users.id` `ON DELETE SET NULL`), `graded_at` (`timestamp NULL`), `status` (`varchar(20) DEFAULT 'submitted'`), `created_at`, `updated_at`.
    - **Indexes:** **UQ** `(assignment_id, enrollment_id, attempt_number)` (`RULE-015`), `assignment_id`, `enrollment_id`.

16. **`attempts`** *(`Modules/Quizzes`)*
    - **Columns:** `id` (`bigint unsigned AI`, **PK**), `quiz_id` (`bigint unsigned`, **FK** → `quizzes.id` `ON DELETE CASCADE`), `enrollment_id` (`bigint unsigned`, **FK** → `enrollments.id` `ON DELETE CASCADE`), `attempt_number` (`int unsigned`), `score` (`decimal(5,2) NULL`), `started_at` (`timestamp DEFAULT CURRENT_TIMESTAMP`), `submitted_at` (`timestamp NULL`), `created_at`, `updated_at`.
    - **Indexes:** **UQ** `(quiz_id, enrollment_id, attempt_number)`, `enrollment_id`.

17. **`questions`** *(`Modules/Quizzes`)*
    - **Columns:** `id` (`bigint unsigned AI`, **PK**), `quiz_id` (`bigint unsigned`, **FK** → `quizzes.id` `ON DELETE CASCADE`), `prompt` (`text`), `type` (`varchar(30)`), `order` (`int unsigned`), `points` (`decimal(6,2) DEFAULT 1.00`), `correct_boolean` (`boolean NULL`), `correct_number` (`decimal(10,4) NULL`), `numeric_tolerance` (`decimal(10,4) NULL DEFAULT 0`), `created_at`, `updated_at`.
    - **Indexes:** `quiz_id`, `(quiz_id, order)`.

### Tier 5 — Question Sub-Details & Attempt Verdicts

18. **`question_options`** *(`Modules/Quizzes`)*
    - **Columns:** `id` (`bigint unsigned AI`, **PK**), `question_id` (`bigint unsigned`, **FK** → `questions.id` `ON DELETE CASCADE`), `label` (`varchar(255)`), `is_correct` (`boolean DEFAULT false`), `order` (`int unsigned DEFAULT 0`), `created_at`, `updated_at`.
    - **Indexes:** `question_id`.

19. **`question_accepted_answers`** *(`Modules/Quizzes` — 1NF Extraction of `Question.AcceptedAnswer`)*
    - **Columns:** `id` (`bigint unsigned AI`, **PK**), `question_id` (`bigint unsigned`, **FK** → `questions.id` `ON DELETE CASCADE`), `answer_text` (`varchar(255)`), `created_at`, `updated_at`.
    - **Indexes:** `question_id`.

20. **`attempt_answers`** *(`Modules/Quizzes`)*
    - **Columns:** `id` (`bigint unsigned AI`, **PK**), `attempt_id` (`bigint unsigned`, **FK** → `attempts.id` `ON DELETE CASCADE`), `question_id` (`bigint unsigned`, **FK** → `questions.id` `ON DELETE CASCADE`), `selected_option_ids` (`json NULL`), `answer_boolean` (`boolean NULL`), `answer_text` (`varchar(255) NULL`), `answer_number` (`decimal(10,4) NULL`), `is_correct` (`boolean DEFAULT false`), `created_at`, `updated_at`.
    - **Indexes:** `attempt_id`, **UQ** `(attempt_id, question_id)`.

---

## 5. Normalization Verification Table

| Normal Form | Verification Status | Detailed Analysis & Intentional Denormalizations |
|---|---|---|
| **1NF** *(Atomicity)* | **Verified** *(with 1 documented JSON optimization)* | Multivalued short-answer variants (`Question.AcceptedAnswer`) are extracted into `question_accepted_answers`. All columns are scalar except `attempt_answers.selected_option_ids` (`json`), `notifications.data` (`json`), and `activity_log` JSON payloads — deliberate write-throughput optimizations avoiding excess pivot joins on multi-choice answers. |
| **2NF** *(No Partial Key Dependencies)* | **Verified (100%)** | Every table has a single-column surrogate `id` primary key (`ARCH-005`), so no non-prime attribute can depend on a proper subset of any primary key. Composite candidate keys (`enrollments`, `lessons`, `submissions`, `attempts`, `attempt_answers`, `group_members`, `component_completions`, `user_permissions`) contain no partial attribute dependencies. |
| **3NF** *(No Transitive Dependencies)* | **Verified** *(with documented caches/ledgers)* | Non-key columns depend strictly on their table's `id` and candidate key. Three deliberate performance projections exist with strict transactional sync invariants: (1) `users.manager_depth` (`ADR-013`, recomputed on subtree reassignment), (2) `user_permissions` (`ADR-012`, synced atomically with `permission_grants`), (3) `progress_records.percent_complete` (summary of `component_completions`). `submissions.is_late` captures an immutable point-in-time evaluation at `submitted_at` so editing `assignments.due_date` later does not rewrite submission history. |
| **BCNF** *(Every Determinant is a Candidate Key)* | **Verified** | All functional determinants across all 20 tables are either the surrogate `id` PK or an explicitly enforced `UNIQUE` candidate key. Polymorphic `(component_type, component_id)` in `component_completions` is protected against orphans via `ComponentDeletionObserver` (`ADR-015`). |

---

## 6. Deliverable Artifacts

- `docs/Mapping/LMS-Relational-Mapping.svg` — Publication-grade Row-of-Cells relational diagram
- `docs/Mapping/LMS-Relational-Mapping.png` — High-resolution rendered raster diagram
- `docs/Mapping/description.md` — Complete 3NF/BCNF schema mapping specification (this document)
- `docs/Mapping/source/gen_schema.py` — Deterministic Relational Mapping regeneration script
- `docs/Mapping/source/LMS-Relational-Mapping.svg` — Source copy of rendered SVG
