# 14 — Cardinality & Optionality Analysis

## Cardinality & Boundary Table

| Parent Table | Child Table | Relationship Type | Min Child | Max Child | Optionality | Enforcement Mechanism |
|---|---|---|---|---|---|---|
| `users` | `courses` | 1:N | 0 | $\infty$ | Optional | FK `instructor_id` |
| `users` | `enrollments` | 1:N | 0 | $\infty$ | Optional | UQ `(student_id, course_id)` |
| `courses` | `lessons` | 1:N | 0 | $\infty$ | Optional | UQ `(course_id, order)` |
| `courses` | `assignments` | 1:N | 0 | $\infty$ | Optional | FK `course_id` |
| `courses` | `quizzes` | 1:N | 0 | $\infty$ | Optional | FK `course_id` |
| `enrollments` | `progress_records` | 1:1 | 1 | 1 | Mandatory | UQ `enrollment_id` |
| `assignments` | `submissions` | 1:N | 0 | Max Attempts | Optional | UQ `(assignment_id, enrollment_id, attempt_number)` |
| `quizzes` | `attempts` | 1:N | 0 | `max_attempts` | Optional | UQ `(quiz_id, enrollment_id, attempt_number)` |
| `quizzes` | `questions` | 1:N | 0 | $\infty$ | Optional | FK `quiz_id` |
| `questions` | `question_options` | 1:N | 0 | $\infty$ | Optional | FK `question_id` |
| `groups` | `group_members` | 1:N | 0 | $\infty$ | Optional | UQ `(group_id, user_id)` |

## Boundary & Domain Inferences vs DB Facts
* **DB-Enforced Fact:** An enrollment can have at most ONE `progress_records` row (`UNIQUE(enrollment_id)`).
* **DB-Enforced Fact:** A student cannot enroll in the same course twice (`UNIQUE(student_id, course_id)`).
* **Application Inference:** A quiz attempt count cannot exceed `quizzes.max_attempts`. (Max child attempts enforced by application service prior to insertion).
