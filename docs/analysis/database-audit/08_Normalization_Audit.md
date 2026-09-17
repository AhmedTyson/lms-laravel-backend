# 08 — Normalization & Redundancy Audit

## Normalization Assessment

### 1. First Normal Form (1NF)
* **Status:** Satisfied across all tables except `attempt_answers.selected_option_ids` which stores a JSON array for `multiple_choice` questions.
* **Evaluation:** Storing JSON for `selected_option_ids` is an intentional denormalization that avoids creating a separate `attempt_answer_options` junction table per question attempt, significantly improving quiz submission write speed.

### 2. Second & Third Normal Form (2NF / 3NF)
* **Status:** Core tables are fully normalized to 3NF. All non-key attributes depend strictly on the primary key.

## Intentional Denormalization & Read Models

| Denormalized Concept | Location | Motivation / Purpose | Assessment |
|---|---|---|---|
| Read Model | `user_permissions` | O(1) authorization checking vs scanning append-only `permission_grants` ledger (ADR-012). | Necessary & Highly Effective |
| Hierarchy Cache | `users.manager_depth` | Avoids expensive recursive CTE queries during authority tree depth evaluations (ADR-013). | Necessary for deep trees |
| Stamped Status | `submissions.is_late` | Cached evaluation of `submitted_at > assignments.due_date` at time of submission. | Necessary (prevents due_date edit drift) |
| Polymorphic Target | `component_completions` | Consolidates completions into a single table rather than 3 distinct tables. | Useful for unified progress math |

## Redundancy & Consistency Risk Analysis

### 1. Dual-Table Authorization Pattern (ADR-012)
* **Structure:** `permission_grants` (authoritative append-only log) vs `user_permissions` (active read model).
* **Risk:** If a transaction fails between inserting into `permission_grants` and updating `user_permissions`, authorization state drifts.
* **Mitigation:** `PermissionReadModelSync` operates inside the exact same DB transaction as grant/revoke executions. Verified by `RefinementTest`.

### 2. `users.manager_depth` Recomputation (ADR-013)
* **Risk:** Changing a manager's `manager_id` without updating subordinates breaks depth math.
* **Mitigation:** `ManagerDepth::recomputeSubtree()` recalculates depth recursively for all descendants in the reassignment transaction.
