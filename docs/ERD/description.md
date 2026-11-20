# LMSDB (Laravel v2.1) — Conceptual Chen ERD Specification

## 1. System Overview & Database Purpose

**LMSDB** is the relational database backing the **Learning Management System (LMS)** Laravel 13 modular backend (`Modules/*`), aligned with `docs/LMS_Laravel_BRD_PRD_v2_1_full.md` (v2.1) and verified against all root and module database migrations.

The conceptual model cleanly separates four bounded contexts across nine modules:
- **Identity (`Auth`):** Central user registry (`User`), email verification, instructor approval gating (`SCOPE-004`, `RULE-010`), and an Admin-rooted reporting tree (`Manages`, `RULE-002`).
- **Access Control (`AccessManagement`):** General-purpose, non-teaching permission delegation (`PermissionGrant`, `RULE-003`–`008`) and permission scoping rooms (`Group`, `MemberOf`, `RULE-009`, `RULE-011`–`012`).
- **Teaching & Learning (`Courses`, `Enrollment`, `Assignments`, `Quizzes`, `Progress`):** Structured courses (`Course`, `Lesson`), student enrollments (`Enrollment`), free-form assignments (`Assignment`, `Submission`), dynamically weighted quizzes (`Quiz`, `Question`, `QuestionOption`, `Attempt`, `Answers`), and unified progress tracking (`ProgressRecord`, `ComponentCompletion`).
- **Platform Communication & Audit (`Notifications`, Platform):** Queued user notifications (`Notification`) and supplementary system-wide activity logging (`ActivityLog`, `ADR-006`).

> **Scope Note:** `Reporting` is read-only by design and owns no entities or tables. Package-owned Spatie RBAC tables (`roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions`) and Laravel infrastructure tables (`cache`, `jobs`, `sessions`, `password_reset_tokens`, `telescope_*`) support framework/RBAC mechanics and are documented separately in `docs/Mapping/description.md`.

---

## 2. Chen Notation Conventions

| Symbol | Visual Style | Meaning |
|---|---|---|
| **Strong Entity** | Single rectangle (`#dbe9ff` fill, `#1f3a68` border) | Independent domain concept with its own primary identity (`id`). |
| **Weak Entity** | Double concentric rectangle | Existence-dependent entity identified through an owner via a partial key / discriminator (materialized with surrogate `id` PK per `ARCH-005` + composite unique key). |
| **Relationship** | Single diamond (`#ffe9c7` fill, `#8a5a00` border) | Semantic association between entities (`1:1`, `1:N`, or `M:N`); `M:N` relationships become junction tables. |
| **Identifying Relationship** | Double concentric diamond | Links a Weak Entity to its owning Strong/Associative Entity (`ON DELETE CASCADE`). |
| **Simple Attribute** | Single oval (`#e6f7e6` fill, `#2f6b2f` border) | Atomic single-valued property. |
| **Primary Key Attribute** | Solid underlined label inside oval | Unique identifier for a strong entity (`id`). |
| **Partial Key / Discriminator** | Dashed underlined label inside oval | Distinguishes weak entity instances within an owner's scope (e.g., `order`, `attempt_number`, `(component_type, component_id)`). |
| **Multivalued Attribute** | Double concentric oval | Attribute holding multiple values per entity instance (`Question.AcceptedAnswer`), extracted into a child table in 1NF (`question_accepted_answers`). |
| **Derived Attribute** | Dashed oval border | Computed from other data rather than authoritative base input (`User.manager_depth` cached per `ADR-013`; `Quiz.total_points` computed live via `SUM(questions.points)` per `RULE-018` rev). |
| **Total Participation** | Double connecting line | Mandatory participation — every instance of the entity must participate in the relationship. |
| **Partial Participation** | Single connecting line | Optional participation — instances may exist without participating in the relationship. |
| **Cardinality (`1`, `N`, `M`)** | Red bold label near entity border | Maximum cardinality ratio (`1:1`, `1:N`, `M:N`). |

---

## 3. Complete Entity Catalog

1. **`User`** *(Strong Entity — `Auth` → `users`)*
   - **Purpose:** Population identity registry for Students, Instructors (Pending/Approved), Managers, and Root Admin.
   - **Attributes:** `id` *(key)*, `name`, `email`, `phone_number`, `email_verified_at`, `password`, `remember_token`, `approval_status`, `manager_depth` *(derived — cached distance from root Admin per `ADR-013`)*.
   - **Domain Notes:** Students sit outside the authority tree (`manager_id = NULL`). Instructors receive `manager_id` upon approval (`RULE-010`).

2. **`Group`** *(Strong Entity — `AccessManagement` → `groups`)*
   - **Purpose:** Organizational permission scope (mapped to Spatie `team_id`). Names are intentionally non-unique (`RULE-011`).
   - **Attributes:** `id` *(key)*, `name`.

3. **`PermissionGrant`** *(Strong Entity — `AccessManagement` → `permission_grants`)*
   - **Purpose:** Authoritative, immutable append-only court ledger recording every permission grant and revoke action (`RULE-005`).
   - **Attributes:** `id` *(key)*, `permission_name`, `action` (`granted | revoked`), `granted_at`, `revoked_at`, `created_at` *(no `updated_at`)*.

4. **`ActivityLog`** *(Strong Entity — Platform → `activity_log`)*
   - **Purpose:** Package-owned (`spatie/laravel-activitylog`, `ADR-006`) supplementary audit trail capturing model mutations and domain events with a 90-day retention window (`ADR-014`).
   - **Attributes:** `id` *(key)*, `log_name`, `description`, `subject_type`, `subject_id`, `event`, `causer_type`, `causer_id`, `attribute_changes`, `properties`, `created_at`.

5. **`Notification`** *(Weak Entity on `User` — `Notifications` → `notifications`)*
   - **Purpose:** Queued user notification inbox tracking read/unread domain alerts.
   - **Attributes:** `id` *(key/surrogate)*, `type`, `data`, `read_at`, `created_at`.

6. **`Course`** *(Strong Entity — `Courses` → `courses`)*
   - **Purpose:** Instructor-authored course following a strictly linear lifecycle (`draft` → `published` → `archived`, `RULE-013`).
   - **Attributes:** `id` *(key)*, `title`, `category`, `description`, `status`, `published_at`, `archived_at`.

7. **`Lesson`** *(Weak Entity on `Course` — `Courses` → `lessons`)*
   - **Purpose:** Ordered instructional unit within a course; progress points at `Lesson.id` rather than `order` so reordering never breaks completions.
   - **Attributes:** `order` *(partial key, unique per course per `RULE-014`)*, `id` *(surrogate PK)*, `title`, `content_reference`.

8. **`Assignment`** *(Strong Entity — `Assignments` → `assignments`)*
   - **Purpose:** Course coursework specification with instructor-defined free-form `max_score` and percentage `passing_threshold` (`RULE-018` rev).
   - **Attributes:** `id` *(key)*, `title`, `description` *(doubles as submission format instructions)*, `due_date`, `resubmission_allowed`, `max_score`, `passing_threshold`.

9. **`Submission`** *(Weak Entity on `Assignment` & `Enrollment` — `Assignments` → `submissions`)*
   - **Purpose:** Individual student attempt on an assignment; all attempts are preserved and independently gradable (`RULE-015`, `RULE-016`).
   - **Attributes:** `attempt_number` *(partial key)*, `id` *(surrogate PK)*, `file_path`, `content`, `submitted_at`, `is_late`, `grade`, `graded_at`, `status`.

10. **`Enrollment`** *(Strong / Associative Entity — `Enrollment` → `enrollments`)*
    - **Purpose:** Represents one unique seat for a student inside a course (`(student_id, course_id)` unique), anchoring all learning activity (`Submission`, `Attempt`, `ProgressRecord`, `ComponentCompletion`).
    - **Attributes:** `id` *(key)*, `status` (`active | completed`), `enrolled_at`, `completed_at`.

11. **`ProgressRecord`** *(Weak Entity 1:1 on `Enrollment` — `Progress` → `progress_records`)*
    - **Purpose:** Cached per-seat course completion summary (`percent_complete` and overall `completed_at`).
    - **Attributes:** `id` *(key/surrogate)*, `percent_complete`, `completed_at`, `updated_at`.

12. **`ComponentCompletion`** *(Weak Entity on `Enrollment` — `Progress` → `component_completions`)*
    - **Purpose:** Granular completion mark for a polymorphic learning component (`lesson | assignment | quiz` via `ADR-010`), earned via threshold (`RULE-018` rev) or recorded instructor override (`RULE-019`).
    - **Attributes:** `component_type` *(partial key)*, `component_id` *(partial key)*, `id` *(surrogate PK)*, `completed_at`, `is_override`.

13. **`Quiz`** *(Strong Entity — `Quizzes` → `quizzes`)*
    - **Purpose:** Timed assessment window (`opens_at`..`closes_at`) with configurable `max_attempts` and dynamic percentage `passing_threshold` (`RULE-018` rev).
    - **Attributes:** `id` *(key)*, `title`, `opens_at`, `closes_at`, `max_attempts`, `passing_threshold`, `total_points` *(derived — computed dynamically as `SUM(questions.points)`, never stored in DB)*.

14. **`Attempt`** *(Weak Entity on `Quiz` & `Enrollment` — `Quizzes` → `attempts`)*
    - **Purpose:** Individual student sitting of a quiz, keyed per `(quiz_id, enrollment_id, attempt_number)`.
    - **Attributes:** `attempt_number` *(partial key)*, `id` *(surrogate PK)*, `score`, `started_at`, `submitted_at`.

15. **`Question`** *(Weak Entity on `Quiz` — `Quizzes` → `questions`)*
    - **Purpose:** Weighted scorable quiz item supporting 5 auto-gradable types (`single_choice`, `multiple_choice`, `true_false`, `short_answer`, `numeric`, `SCOPE-005`).
    - **Attributes:** `order` *(partial key)*, `id` *(surrogate PK)*, `prompt`, `type`, `points`, `correct_boolean`, `correct_number`, `numeric_tolerance`, `AcceptedAnswer` *(multivalued — short-answer accepted text variants per `RULE-017`, normalized to `question_accepted_answers`)*.

16. **`QuestionOption`** *(Weak Entity on `Question` — `Quizzes` → `question_options`)*
    - **Purpose:** Pre-marked selectable choices for `single_choice` and `multiple_choice` questions.
    - **Attributes:** `order` *(partial key)*, `id` *(surrogate PK)*, `label`, `is_correct`.

---

## 4. Complete Relationship Catalog

| Relationship | Between | Cardinality | Participation | Own Attributes | Semantic Meaning |
|---|---|---|---|---|---|
| `Manages` | `User` ↔ `User` (Unary) | `1 : N` | Partial / Partial | — | Strict Admin-rooted reporting tree (`manager_id`); cycles blocked at service layer (`ADR-007`). |
| `Owns` | `User` ↔ `Group` | `1 : N` | Partial / Total | — | Every group has an owner (`owner_id`), subject to succession on removal (`RULE-012`). |
| `MemberOf` | `User` ↔ `Group` | `M : N` | Partial / Partial | `joined_at` | Group membership list (`group_members`); grants zero inherent permissions (`RULE-009`). |
| `Grants` | `User` ↔ `PermissionGrant` | `1 : N` | Partial / Total | — | Identifies the `granter_id` issuing a grant or revocation (`RULE-003`, `RULE-004`). |
| `ReceivesGrant` | `User` ↔ `PermissionGrant` | `1 : N` | Partial / Total | — | Identifies the `grantee_id` receiving or losing a permission. |
| `ScopedTo` | `Group` ↔ `PermissionGrant` | `1 : N` | Partial / Partial | — | Optional `group_id` team scope; `NULL` represents a global grant (`RULE-007/008`). |
| `Notifies` | `User` ↔ `Notification` *(Id.)* | `1 : N` | Partial / Total | — | Queued notifications owned by and restricted to the recipient `user_id`. |
| `Instructs` | `User` ↔ `Course` | `1 : N` | Partial / Total | — | Approved instructor (`instructor_id`) who authored and owns the course (`ADR-011`). |
| `HasLesson` | `Course` ↔ `Lesson` *(Id.)* | `1 : N` | Partial / Total | — | Ordered chapters belonging to a course (`(course_id, order)` unique). |
| `HasAssignment` | `Course` ↔ `Assignment` | `1 : N` | Partial / Total | — | Coursework assignments published within a course. |
| `HasQuiz` | `Course` ↔ `Quiz` | `1 : N` | Partial / Total | — | Timed quizzes authored inside a course. |
| `EnrollsIn` | `User` ↔ `Enrollment` | `1 : N` | Partial / Total | — | Student seat registration (`student_id`). |
| `ForCourse` | `Course` ↔ `Enrollment` | `1 : N` | Partial / Total | — | Course targeted by a student enrollment (`(student_id, course_id)` unique). |
| `ForAssignment` | `Assignment` ↔ `Submission` *(Id.)* | `1 : N` | Partial / Total | — | Assignment answered by a student submission attempt. |
| `Submits` | `Enrollment` ↔ `Submission` *(Id.)* | `1 : N` | Partial / Total | — | Enrollment seat under which submission attempts are filed (`RULE-015`). |
| `Grades` | `User` ↔ `Submission` | `1 : N` | Partial / Partial | — | Optional instructor/admin (`graded_by`) who scored a submission. |
| `HasQuestion` | `Quiz` ↔ `Question` *(Id.)* | `1 : N` | Partial / Total | — | Weighted questions comprising a quiz. |
| `HasOption` | `Question` ↔ `QuestionOption` *(Id.)* | `1 : N` | Partial / Total | — | Choice options belonging to a multiple/single-choice question. |
| `ForQuiz` | `Quiz` ↔ `Attempt` *(Id.)* | `1 : N` | Partial / Total | — | Quiz being taken during a student attempt. |
| `Sits` | `Enrollment` ↔ `Attempt` *(Id.)* | `1 : N` | Partial / Total | — | Student enrollment sitting a quiz attempt. |
| `Answers` | `Attempt` ↔ `Question` | `M : N` | Partial / Partial | `selected_option_ids`, `answer_boolean`, `answer_text`, `answer_number`, `is_correct` | Student response and auto-graded verdict per question per sitting (`attempt_answers`). |
| `TracksProgress` | `Enrollment` ↔ `ProgressRecord` *(Id.)* | `1 : 1` | Total / Total | — | Strict 1:1 cached course progress record per enrollment. |
| `Completes` | `Enrollment` ↔ `ComponentCompletion` *(Id.)* | `1 : N` | Partial / Total | — | Granular completion marks per enrollment; polymorphic target `(component_type, component_id)` resolved via `ADR-010`. |
| `Overrides` | `User` ↔ `ComponentCompletion` | `1 : N` | Partial / Partial | — | Optional instructor/admin (`overridden_by`) recording a manual completion override (`RULE-019`). |

---

## 5. Architectural & Design Decisions

1. **Derived Attributes vs. Physical Materializations:**
   - **`Quiz.total_points`** is strictly derived (`SUM(questions.points)`) and intentionally **never stored** in `quizzes`, ensuring question weight edits immediately update the effective pass score (`RULE-018` rev).
   - **`User.manager_depth`** is conceptually derived from the recursive `Manages` tree, but materialized as a write-maintained cache column (`ADR-013`, `ManagerDepth::recomputeSubtree()`) so ceiling-rule checks stay $O(1)$.
   - **`user_permissions` (Table #20, `ADR-012`):** Intentionally omitted as an independent conceptual entity in the Chen ERD because it is a **derived read-model projection** of `PermissionGrant`, `User`, and `Group` synchronized atomically within the grant/revoke transaction (`PermissionReadModelSync`) for $O(1)$ runtime authorization checks. It appears explicitly in Tier 3 of the Relational Schema Mapping (`docs/Mapping/description.md`).
2. **Multivalued Attribute Normalization (`AcceptedAnswer`):** `Question.AcceptedAnswer` is modeled as a Chen multivalued attribute (`{{AcceptedAnswer}}`) for `short_answer` variants (`RULE-017`) and normalized into `question_accepted_answers` in 1NF.
3. **Associative Entities (`Enrollment`, `PermissionGrant`):** Modeled as full entities rather than diamonds because downstream entities (`Submission`, `Attempt`, `ProgressRecord`, `ComponentCompletion`, `user_permissions`) hold direct foreign keys to them.
4. **Polymorphic Targets (`ComponentCompletion` & `ActivityLog`):** Per `ADR-010` and `ADR-006`, polymorphic `(component_type, component_id)` and `(subject_type, subject_id)` / `(causer_type, causer_id)` associations omit cross-table SQL foreign keys and enforce orphan cleanup in the application layer (`ComponentDeletionObserver`, `ADR-015`).

---

## 6. Deliverable Artifacts

- `docs/ERD/LMS-Chen-ERD.svg` — Publication-grade Chen ERD vector diagram
- `docs/ERD/LMS-Chen-ERD.png` — High-resolution rendered raster diagram (`7900×4600`)
- `docs/ERD/LMS-Chen-ERD.drawio` — Editable Draw.io XML source diagram
- `docs/ERD/description.md` — Conceptual ERD specification (this document)
- `docs/ERD/source/spec.json` — Declarative JSON specification for both ERD and Relational Mapping
- `docs/ERD/source/build_spec.py` — Deterministic layout & corridor allocation generator
- `docs/ERD/source/gen_erd.py` — Deterministic Chen ERD regeneration script
