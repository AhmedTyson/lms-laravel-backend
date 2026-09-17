# 05 — Foreign Key & Relationship Analysis

## Complete Relationship Matrix

| Child Table | Child Column | Parent Table | Parent Column | Type | Optional? | Delete Rule | Update Rule | Business Meaning | Performance Notes |
|---|---|---|---|---|---|---|---|---|---|
| `users` | `manager_id` | `users` | `id` | 1:N Unary | YES | SET NULL | CASCADE | Subordinate reports to Manager | Indexed `users_manager_id_index` |
| `groups` | `owner_id` | `users` | `id` | N:1 | NO | RESTRICT | CASCADE | Group owned by User | Indexed `groups_owner_id_index` |
| `group_members` | `group_id` | `groups` | `id` | M:N Pivot | NO | CASCADE | CASCADE | Group membership boundary | Part of UQ `(group_id, user_id)` |
| `group_members` | `user_id` | `users` | `id` | M:N Pivot | NO | CASCADE | CASCADE | Member user link | Indexed `group_members_user_id_index` |
| `permission_grants` | `granter_id` | `users` | `id` | N:1 | NO | RESTRICT | CASCADE | Actor issuing grant | Indexed `(granter_id, grantee_id)` |
| `permission_grants` | `grantee_id` | `users` | `id` | N:1 | NO | RESTRICT | CASCADE | Actor receiving grant | Indexed `permission_grants_grantee_id_index` |
| `permission_grants` | `group_id` | `groups` | `id` | N:1 | YES | SET NULL | CASCADE | Group scope of grant | Indexed `permission_grants_group_id_index` |
| `user_permissions` | `user_id` | `users` | `id` | N:1 | NO | CASCADE | CASCADE | Target user for O(1) read | Part of UQ `(user_id, permission_name, group_id)` |
| `user_permissions` | `group_id` | `groups` | `id` | N:1 | YES | SET NULL | CASCADE | Group scope for read model | Part of UQ |
| `user_permissions` | `granted_via_grant_id` | `permission_grants` | `id` | N:1 | NO | CASCADE | CASCADE | Audit link back to grant ledger | Deleting grant removes read model row |
| `courses` | `instructor_id` | `users` | `id` | N:1 | NO | RESTRICT | CASCADE | Owning instructor | Part of UQ `(instructor_id, title)` (ADR-011) |
| `lessons` | `course_id` | `courses` | `id` | N:1 | NO | CASCADE | CASCADE | Parent course | Part of UQ `(course_id, order)` |
| `enrollments` | `student_id` | `users` | `id` | N:1 | NO | RESTRICT | CASCADE | Enrolled student user | Part of UQ `(student_id, course_id)` |
| `enrollments` | `course_id` | `courses` | `id` | N:1 | NO | CASCADE | CASCADE | Target course | Part of UQ |
| `assignments` | `course_id` | `courses` | `id` | N:1 | NO | CASCADE | CASCADE | Parent course | Indexed `assignments_course_id_index` |
| `submissions` | `assignment_id` | `assignments` | `id` | N:1 | NO | CASCADE | CASCADE | Target assignment | Part of UQ `(assignment_id, enrollment_id, attempt_number)` |
| `submissions` | `enrollment_id` | `enrollments` | `id` | N:1 | NO | CASCADE | CASCADE | Student enrollment | Part of UQ |
| `submissions` | `graded_by` | `users` | `id` | N:1 | YES | SET NULL | CASCADE | Instructor who graded | Unindexed (low frequency query) |
| `quizzes` | `course_id` | `courses` | `id` | N:1 | NO | CASCADE | CASCADE | Parent course | Indexed `quizzes_course_id_index` |
| `questions` | `quiz_id` | `quizzes` | `id` | N:1 | NO | CASCADE | CASCADE | Parent quiz | Part of index `(quiz_id, order)` |
| `question_options` | `question_id` | `questions` | `id` | N:1 | NO | CASCADE | CASCADE | Parent question | Indexed `question_options_question_id_index` |
| `question_accepted_answers` | `question_id` | `questions` | `id` | N:1 | NO | CASCADE | CASCADE | Parent question | Indexed `question_accepted_answers_question_id_index` |
| `attempts` | `quiz_id` | `quizzes` | `id` | N:1 | NO | CASCADE | CASCADE | Target quiz | Part of UQ `(quiz_id, enrollment_id, attempt_number)` |
| `attempts` | `enrollment_id` | `enrollments` | `id` | N:1 | NO | CASCADE | CASCADE | Student enrollment | Part of UQ |
| `attempt_answers` | `attempt_id` | `attempts` | `id` | N:1 | NO | CASCADE | CASCADE | Quiz sitting instance | Part of UQ `(attempt_id, question_id)` |
| `attempt_answers` | `question_id` | `questions` | `id` | N:1 | NO | CASCADE | CASCADE | Answered question | Part of UQ |
| `progress_records` | `enrollment_id` | `enrollments` | `id` | 1:1 | NO | CASCADE | CASCADE | Tracked enrollment | Unique constraint enforces 1:1 |
| `component_completions` | `enrollment_id` | `enrollments` | `id` | N:1 | NO | CASCADE | CASCADE | Student enrollment | Part of UQ `(enrollment_id, component_type, component_id)` |
| `component_completions` | `overridden_by` | `users` | `id` | N:1 | YES | SET NULL | CASCADE | Overriding instructor | Unindexed |
| `notifications` | `user_id` | `users` | `id` | N:1 | NO | CASCADE | CASCADE | Recipient user | Part of composite index `(user_id, read_at)` |

## Relationship Descriptions
1. **`users.manager_id` → `users.id` (1:N Unary):** A user can have at most one manager, while a manager can have multiple subordinates. Forms an Admin-rooted organizational tree.
2. **`courses.instructor_id` → `users.id` (N:1):** An instructor owns multiple courses; a course belongs to exactly one instructor. Hard deletion of instructors with active courses is prohibited (`RESTRICT` per ADR-011).
3. **`enrollments.student_id` & `course_id` (M:N via `enrollments`):** Links students to courses. DB unique index `(student_id, course_id)` prevents concurrent double-enrollment races.
4. **`component_completions.component_id` (Polymorphic):** Resolves dynamically to `lessons.id`, `assignments.id`, or `quizzes.id` based on `component_type` (ADR-010). Foreign keys are intentionally absent at the DB layer to support polymorphs.
