# 17 — Security & Access-Related Data Model

## Security Architecture Overview

### 1. Identity & Credentials (`users`)
* Sensitive fields (`password`, `remember_token`) marked as `#[Hidden]` on the `User` model.
* Authentication executed via JWT (`tymon/jwt-auth`). `User` implements `JWTSubject`.

### 2. Dual-Layer Authorization Architecture
* **Layer 1: Role-Based Access Control (RBAC):** Package-managed via `spatie/laravel-permission` (`permissions`, `roles`, `model_has_roles`, `role_has_permissions`). `groups.id` acts as Spatie `team_id`.
* **Layer 2: Delegated Fine-Grained Authorization:**
  * `permission_grants`: Append-only audit ledger enforcing granter manager hierarchy (`RULE-002`), granter permission ceiling (`RULE-004`), and subordinate-only delegation (`RULE-003`).
  * `user_permissions`: Derived read model providing $O(1)$ evaluation (ADR-012).

### 3. Boundary & Scope Isolation
* **Group Isolation:** Group membership (`group_members`) provides scope boundary only. Membership grants zero inherent permissions (`RULE-009`).
* **Instructor Approval Gating:** Instructors start with `approval_status = 'pending'` and cannot author content until approved by an Admin (`RULE-010`).
