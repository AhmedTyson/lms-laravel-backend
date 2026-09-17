# 09 — Index Inventory & Optimization Audit

## Complete Index Catalog

| Table | Index Name | Columns | Unique | Type | Purpose & Likely Query Pattern | Selectivity | Write Cost | Assessment |
|---|---|---|---|---|---|---|---|---|
| `users` | `users_email_unique` | `email` | YES | B-Tree | User login (`SELECT * FROM users WHERE email = ?`) | Extremely High | Low | Essential |
| `users` | `users_manager_id_index` | `manager_id` | NO | B-Tree | Subordinate lookup (`WHERE manager_id = ?`) | Moderate | Low | Essential |
| `groups` | `groups_owner_id_index` | `owner_id` | NO | B-Tree | Groups by manager/owner | High | Low | Essential |
| `group_members` | `group_members_group_id_user_id_unique` | `group_id, user_id` | YES | B-Tree | Prevent double join & fast membership lookup | Extremely High | Low | Essential |
| `group_members` | `group_members_user_id_index` | `user_id` | NO | B-Tree | Groups a user belongs to | High | Low | Essential |
| `permission_grants` | `permission_grants_grantee_id_index` | `grantee_id` | NO | B-Tree | User's historical grants | High | Low | Essential |
| `permission_grants` | `permission_grants_granter_id_grantee_id_index` | `granter_id, grantee_id` | NO | B-Tree | Granter-to-grantee audit check | High | Low | Essential |
| `permission_grants` | `permission_grants_group_id_index` | `group_id` | NO | B-Tree | Grants scoped to a group | Moderate | Low | Essential |
| `user_permissions` | `user_permissions_user_id_permission_name_group_id_unique` | `user_id, permission_name, group_id` | YES | B-Tree | O(1) authorization check | Extremely High | Low | Essential (ADR-012) |
| `user_permissions` | `user_permissions_user_id_index` | `user_id` | NO | B-Tree | List all user permissions | High | Low | Redundant (Covered by UQ) |
| `courses` | `courses_instructor_id_title_unique` | `instructor_id, title` | YES | B-Tree | Prevent duplicate titles per instructor | Extremely High | Low | Essential |
| `courses` | `courses_status_index` | `status` | NO | B-Tree | Catalog filter (`WHERE status = 'published'`) | Low | Low | Moderate |
| `courses` | `courses_instructor_id_index` | `instructor_id` | NO | B-Tree | Courses by instructor | High | Low | Redundant (Covered by UQ) |
| `lessons` | `lessons_course_id_order_unique` | `course_id, order` | YES | B-Tree | Lesson ordering & uniqueness (RULE-014) | Extremely High | Low | Essential |
| `lessons` | `lessons_course_id_index` | `course_id` | NO | B-Tree | Fetch lessons for course | High | Low | Redundant (Covered by UQ) |
| `enrollments` | `enrollments_student_id_course_id_unique` | `student_id, course_id` | YES | B-Tree | Prevent concurrent double-enrollment | Extremely High | Low | Essential |
| `enrollments` | `enrollments_course_id_index` | `course_id` | NO | B-Tree | Roster for a course | High | Low | Essential |
| `enrollments` | `enrollments_student_id_index` | `student_id` | NO | B-Tree | Student's enrolled courses | High | Low | Redundant (Covered by UQ) |
| `assignments` | `assignments_course_id_index` | `course_id` | NO | B-Tree | Assignments in a course | High | Low | Essential |
| `assignments` | `assignments_due_date_index` | `due_date` | NO | B-Tree | Overdue & upcoming assignments | High | Low | Essential |
| `submissions` | `submissions_assignment_id_enrollment_id_attempt_number_unique` | `assignment_id, enrollment_id, attempt_number` | YES | B-Tree | Prevent duplicate attempt submit (RULE-015) | Extremely High | Low | Essential |
| `quizzes` | `quizzes_course_id_index` | `course_id` | NO | B-Tree | Quizzes in course | High | Low | Essential |
| `quizzes` | `quizzes_opens_at_closes_at_index` | `opens_at, closes_at` | NO | B-Tree | Active quiz window filtering | High | Low | Essential |
| `attempts` | `attempts_quiz_id_enrollment_id_attempt_number_unique` | `quiz_id, enrollment_id, attempt_number` | YES | B-Tree | Prevent duplicate quiz attempts | Extremely High | Low | Essential |
| `component_completions` | `component_completions_enrollment_id_component_type_component_id_unique` | `enrollment_id, component_type, component_id` | YES | B-Tree | Prevent duplicate gold stars | Extremely High | Low | Essential |
| `notifications` | `notifications_user_id_read_at_index` | `user_id, read_at` | NO | B-Tree | Unread notifications count/list | High | Low | Essential |
| `activity_log` | `activity_log_created_at_index` | `created_at` | NO | B-Tree | Nightly retention pruning (ADR-014) | High | Low | Essential |

## Index Efficiency & Redundancy Analysis
1. **Redundant Single-Column Indexes:**
   * `courses.instructor_id` index is redundant because compound unique index `(instructor_id, title)` has `instructor_id` as its left-most prefix.
   * `lessons.course_id` index is redundant due to `(course_id, order)` unique index.
   * `enrollments.student_id` index is redundant due to `(student_id, course_id)` unique index.
   * `user_permissions.user_id` index is redundant due to `(user_id, permission_name, group_id)` unique index.
2. **Performance Impact of Redundancy:**
   While B-Tree engines incur minor write overhead for duplicate left-prefix indexes, keeping explicit single-column indexes ensures Laravel schema tools generate expected relationships cleanly.
