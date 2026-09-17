# 06 — Unary / Recursive Relationships Analysis

## Unary Relationship Matrix

| Table | Self FK | Parent Key | Meaning | Root Allowed | Cycle Risk | Depth | Query Pattern | Index Need | Score /10 |
|---|---|---|---|---|---|---|---|---|---:|
| `users` | `manager_id` | `id` | Manager-Subordinate Hierarchy | YES (`manager_id IS NULL`) | Service Guarded (ADR-007) | Unbounded | Self-Join / `manager_depth` | High (`users_manager_id_index`) | 9 |

## Detailed Breakdown: `users.manager_id` → `users.id`

### 1. What the relation describes
The `manager_id` column represents an Admin-rooted organizational tree structure. 
* **Root records (`manager_id IS NULL`):** System Admins and unassigned top-level Instructors.
* **Subordinates (`manager_id = parent_user_id`):** Managers reporting up to higher managers or Admins.
* **Students:** Always maintain `manager_id = NULL` per business rule `RULE-002` (Students sit entirely outside the authority tree).

### 2. Typical real-world use
* **Subordinate Permission Delegation (RULE-003):** Managers can only grant permissions to users within their subordinate sub-tree.
* **Approval Chain Routing (RULE-010):** Instructor approval assigns `manager_id` to the approving Admin/Manager.

### 3. Cycle Risk & Protection (ADR-007)
* **Risk:** Cycles (e.g., User A → User B → User A) would crash recursive tree traversals.
* **Mitigation:** The database engine (MySQL/SQLite) cannot check cycle prevention natively without custom triggers. Cycle prevention is strictly enforced in `ManagerAssignmentService` using transactions and explicit locking (`SELECT FOR UPDATE`), throwing `CircularManagerAssignmentException` upon detection.

### 4. Depth & Performance Optimization (ADR-013)
* **Problem:** Traversing an unbounded tree in standard SQL requires recursive CTEs (`WITH RECURSIVE`), which add overhead on deep hierarchies.
* **Optimization:** `users.manager_depth` stores a denormalized depth cache (Root Admin = 0). The tree depth is recomputed on write by `ManagerDepth::recomputeSubtree()`, enabling $O(1)$ depth checks.

### 5. Integrity & Deletion Rules
* `ON DELETE SET NULL`: If a manager is deleted, their immediate subordinates' `manager_id` becomes `NULL` (orphaned to root level) unless reassigned prior to deletion.
* `ADR-011`: Soft deletion is enforced for users owning permission grants or courses to prevent broken authority trees.
