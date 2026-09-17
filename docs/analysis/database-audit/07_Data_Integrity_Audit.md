# 07 — Data Integrity & Constraint Audit

## Constraint Assessment Summary

| Constraint Category | Target / Table | Evaluation | Finding Classification |
|---|---|---|---|
| Primary Key Integrity | All 20 application tables | Surrogate `bigint AUTO_INCREMENT` PKs present on all tables. | No issue detected |
| Foreign Key Integrity | `courses.instructor_id`, `permission_grants.granter/grantee_id` | Hard-delete prevented via `RESTRICT` (ADR-011). SQLite skips ALTER by design. | No issue detected |
| Polymorphic FK Integrity | `component_completions.component_id` | DB-level FK absent due to polymorphic target (`lessons`, `assignments`, `quizzes`). | Potential issue (App-enforced only) |
| Composite Unique Constraints | `enrollments(student_id, course_id)` | Prevents concurrent double-enrollment race conditions. | No issue detected |
| Composite Unique Constraints | `submissions(assignment_id, enrollment_id, attempt_number)` | Prevents duplicate attempt creation (RULE-015). | No issue detected |
| Composite Unique Constraints | `attempts(quiz_id, enrollment_id, attempt_number)` | Prevents duplicate quiz sitting attempts. | No issue detected |
| Composite Unique Constraints | `component_completions(enrollment_id, component_type, component_id)` | Prevents duplicate completion records. | No issue detected |
| Composite Unique Constraints | `user_permissions(user_id, permission_name, group_id)` | Prevents duplicate active grants in read model. | No issue detected |
| String Enum Validation | `courses.status`, `submissions.status`, `permission_grants.action` | Stored as plain `varchar(20)`. Invalid strings blocked at app layer, not DB. | Potential issue (No DB CHECK) |
| Non-Negative Numeric Integrity | `assignments.max_score`, `quizzes.passing_threshold` | Stored as `decimal(6,2)` / `decimal(5,2)`. No DB CHECK for negative numbers. | Potential issue (App-enforced) |
| Append-Only Ledger Integrity | `permission_grants` | No `updated_at` column. `created_at` defaults to `now()`. | No issue detected |

## Audit Findings Detail

### 1. Polymorphic Referential Integrity (ADR-010)
* **Finding:** `component_completions` uses `component_type` and `component_id`. There is no foreign key constraint connecting `component_id` to `lessons`, `assignments`, or `quizzes`.
* **Impact:** Deleting a `lesson` or `quiz` directly via SQL would leave orphan completion records unless handled by an observer/event listener (`ComponentDeletionObserver`).

### 2. Missing Database-Level CHECK Constraints
* **Finding:** State columns like `courses.status` (`draft`, `published`, `archived`) and numeric bounds like `passing_threshold` ($0.00$ to $100.00$) rely entirely on Laravel FormRequest validation.
* **Impact:** Direct DB access or unvalidated seeder scripts could introduce invalid string states.
