# 01 — Database Inventory

## Platform & Schema Information
* **Database Platform:** MySQL (Production) / SQLite (Development)
* **ORM:** Laravel 13 Eloquent
* **Schema Version:** LMS v2.1
* **Architecture:** Modular Architecture via `nwidart/laravel-modules:^13`

## Quantitative Summary
* **Total Application Tables:** 20 (19 spec tables + `user_permissions` read-model)
* **Total Package-Owned Tables:** 7 (`permissions`, `roles`, `model_has_permissions`, `model_has_roles`, `role_has_permissions`, `activity_log`, `telescope_entries`)
* **Total System Tables:** 4 (`cache`, `jobs`, `sessions`, `password_reset_tokens`)
* **Total Tables in Database:** 31
* **Total Columns (Application Tables):** 114
* **Total Primary Keys:** 31 (All auto-increment `bigint` / string primary keys for system tokens)
* **Total Foreign Keys (Application Tables):** 28
* **Total Unique Constraints (Application Tables):** 11
* **Total Indexes (Application Tables, excluding PK/UK):** 25
* **Total Views:** 0
* **Total Stored Procedures / Functions:** 0
* **Total Triggers:** 0
* **Total Enum Structures:** 8 (PHP Backed Enums in application code, string/varchar columns in database)

## Special Schema Structures
* **Recursive / Unary Relationships:** 1 (`users.manager_id` → `users.id`)
* **Implicit / Application-Enforced Foreign Keys:** 1 (`component_completions.component_id` target depends on `component_type` polymorphic discriminator: `lesson`, `assignment`, `quiz` per ADR-010)
* **Junction / Pivot Tables:** 3 (`group_members`, `attempt_answers`, `component_completions`)
* **Read Models / Derived Tables:** 2 (`user_permissions` per ADR-012, `users.manager_depth` per ADR-013)
* **Append-Only Ledgers:** 1 (`permission_grants` per RULE-005)

## Table Classification Matrix

| Table Name | Classification | Domain Group | Primary Responsibility |
|---|---|---|---|
| `users` | Master Data / Identity | Identity | Core user registry and hierarchy |
| `groups` | Master Data / Scope | Access | Permission team boundary |
| `group_members` | Junction / Relationship | Access | User membership in groups |
| `permission_grants` | Audit / Immutable Ledger | Access | Authoritative history of power grants/revokes |
| `user_permissions` | Read Model / Cache | Access | O(1) authorization evaluation lookup |
| `courses` | Master Data / Entity | Teaching | Course catalog and status lifecycle |
| `lessons` | Master Data / Entity | Teaching | Ordered course contents |
| `enrollments` | Transactional / Relationship | Teaching | Student seat in a course |
| `assignments` | Master Data / Entity | Teaching | Course task specifications |
| `submissions` | Transactional / Audit | Teaching | Student work attempts and grades |
| `quizzes` | Master Data / Entity | Teaching | Timed assessment definitions |
| `questions` | Master Data / Entity | Teaching | Quiz items with weights and answers |
| `question_options` | Master Data / Detail | Teaching | Options for choice questions |
| `question_accepted_answers` | Master Data / Detail | Teaching | Acceptable text variants for short answer |
| `attempts` | Transactional / Entity | Teaching | Quiz sitting instances |
| `attempt_answers` | Transactional / Detail | Teaching | Per-question answers given in quiz attempt |
| `progress_records` | Read Model / Summary | Teaching | Overall course completion summary (1:1 with enrollment) |
| `component_completions` | Transactional / Audit | Teaching | Granular completions (lessons, assignments, quizzes) |
| `notifications` | Transactional / Queue | Platform | User message queue and read status |
| `activity_log` | Audit Trail | Platform | Package-owned general system CCTV audit |

## Compact Structural Summary
The database consists of 20 core domain tables organized around 4 primary domains: Identity, Access Control, Teaching/Learning Execution, and Platform Audit/Notifications. All primary keys use surrogate auto-increment big integers (`bigint`). Referential integrity is strictly enforced at the database level for all direct relationships, with the exception of the polymorphic `component_completions` table (ADR-010) and the SQLite dev environment where specific ALTER constraints are bypassed (ADR-011).
