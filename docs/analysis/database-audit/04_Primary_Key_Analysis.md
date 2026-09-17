# 04 — Primary Key Analysis

## Overview
All 20 application tables and 7 package tables use surrogate integer primary keys (`bigint AUTO_INCREMENT`). This complies with project baseline rule `ARCH-005`.

## Primary Key Inventory

| Table Name | PK Column(s) | Key Type | Width | Surrogate vs Natural | Clustering & Indexing Implications |
|---|---|---|---|---|---|
| `users` | `id` | bigint | 8 bytes | Surrogate | Optimal B-Tree clustering key |
| `groups` | `id` | bigint | 8 bytes | Surrogate | Serves as Spatie `team_id` |
| `group_members` | `id` | bigint | 8 bytes | Surrogate | Secondary UQ on `(group_id, user_id)` |
| `permission_grants` | `id` | bigint | 8 bytes | Surrogate | Append-only sequence order |
| `user_permissions` | `id` | bigint | 8 bytes | Surrogate | Secondary UQ on `(user_id, permission_name, group_id)` |
| `courses` | `id` | bigint | 8 bytes | Surrogate | Secondary UQ on `(instructor_id, title)` |
| `lessons` | `id` | bigint | 8 bytes | Surrogate | Secondary UQ on `(course_id, order)` |
| `enrollments` | `id` | bigint | 8 bytes | Surrogate | Secondary UQ on `(student_id, course_id)` |
| `assignments` | `id` | bigint | 8 bytes | Surrogate | FK target for submissions |
| `submissions` | `id` | bigint | 8 bytes | Surrogate | Secondary UQ on `(assignment_id, enrollment_id, attempt_number)` |
| `quizzes` | `id` | bigint | 8 bytes | Surrogate | FK target for questions & attempts |
| `questions` | `id` | bigint | 8 bytes | Surrogate | Secondary index on `(quiz_id, order)` |
| `question_options` | `id` | bigint | 8 bytes | Surrogate | FK target for attempt answers |
| `question_accepted_answers` | `id` | bigint | 8 bytes | Surrogate | Text variants per question |
| `attempts` | `id` | bigint | 8 bytes | Surrogate | Secondary UQ on `(quiz_id, enrollment_id, attempt_number)` |
| `attempt_answers` | `id` | bigint | 8 bytes | Surrogate | Secondary UQ on `(attempt_id, question_id)` |
| `progress_records` | `id` | bigint | 8 bytes | Surrogate | Secondary UQ on `enrollment_id` (1:1 enforcing) |
| `component_completions` | `id` | bigint | 8 bytes | Surrogate | Secondary UQ on `(enrollment_id, component_type, component_id)` |
| `notifications` | `id` | bigint | 8 bytes | Surrogate | High insert rate sequence |
| `activity_log` | `id` | bigint | 8 bytes | Surrogate | Package-owned sequential audit ID |

## Detailed Evaluation & Analysis

### 1. Uniformity & Consistency
* **Strength:** Every table uses `id` as an auto-incrementing 64-bit integer (`bigint`). This guarantees complete compatibility across standard Laravel conventions and ORM tooling.
* **Storage Efficiency:** `bigint` provides $2^{63}-1$ possible values, completely eliminating primary key exhaustion risks even for high-write tables such as `notifications`, `activity_log`, and `attempt_answers`.

### 2. Natural vs. Surrogate Key Trade-offs
* **Surrogate Choice:** Natural keys (e.g. `users.email`, `(student_id, course_id)`) were deliberately avoided for primary keys. Natural keys were instead protected via secondary UNIQUE constraints.
* **Benefit:** Foreign key references across the schema remain lightweight (single 8-byte integer rather than composite or string keys), maximizing index page density and join execution speed.

### 3. Suspicious PK Patterns / Observations
* **`progress_records` Table:** Uses a surrogate `id` PK while enforcing a `UNIQUE` constraint on `enrollment_id`.
  * *Analysis:* A pure natural PK `PRIMARY KEY (enrollment_id)` could have saved 8 bytes of storage per row and one secondary index lookup, since the relationship is strictly 1:1. However, using surrogate `id` maintains baseline consistency (`ARCH-005`).
* **`group_members`, `component_completions`, `attempt_answers` Junction Tables:** All carry a surrogate `id` alongside composite unique indexes.
  * *Analysis:* While composite primary keys `PRIMARY KEY (group_id, user_id)` are standard in pure relational design, surrogate keys enable Eloquent models to easily reference individual pivot records.
