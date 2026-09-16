> Index: [tasks/todo.md](../todo.md) -- AI: load this phase file only, not the whole list.

## Phase 5 — AccessManagement (RULE-002–012, ADR-007/008)

## Task 5.1: Manager tree service + cycle guard

**Description:** `ManagerAssignmentService::assign()` — transaction + `SELECT FOR UPDATE` + upward chain walk, throws `CircularManagerAssignmentException` (never silent, never model event). Reassignment non-retroactive (RULE-006).

**Acceptance criteria:**
- [ ] Cycle (incl. self-parent) rejected with dedicated exception; history rows untouched by reassignment
- [ ] Reassignment calls `ManagerDepth::recomputeSubtree()` in the same transaction (ADR-013); depth test in `RefinementTest` stays green

**Verification:**
- [ ] `vendor/bin/pest --filter="ManagerAssignment"` incl. cycle + self-parent cases

**Dependencies:** Phase 3

**Files likely touched:**
- `Modules/AccessManagement/app/Services/ManagerAssignmentService.php`
- `Modules/AccessManagement/app/Exceptions/CircularManagerAssignmentException.php`

**Estimated scope:** Medium (3-5 files)

## Task 5.2: Grant/revoke API (explicit team context)

**Description:** `POST /api/grants`, `POST /api/revokes`: subordinate-only (RULE-003) + ceiling (RULE-004) + append-only ledger rows (RULE-005). Every permission check passes explicit group context — bare `hasPermissionTo()` rejected in review.

**Acceptance criteria:**
- [ ] Grant to non-subordinate → 403; grant of unheld permission → 403 (global + group-scoped)
- [ ] Revoke writes new `revoked` row; nothing updated/deleted
- [ ] Every grant/revoke calls `PermissionReadModelSync::syncFromGrant()` inside the same transaction (ADR-012); `RefinementTest` grant→revoke→grant consistency stays green

**Verification:**
- [ ] `vendor/bin/pest --filter="PermissionGrant"` incl. stale-context regression (ambient team must not leak)

**Dependencies:** Tasks 5.1, 4.2

**Files likely touched:**
- `Modules/AccessManagement/app/Http/Controllers/*.php`
- `Modules/AccessManagement/app/Services/GrantService.php`

**Estimated scope:** Medium (3-5 files)

## Task 5.3: Groups + succession

**Description:** Group CRUD, membership (≠ permission, RULE-009), ownership succession on owner removal → owner's manager, else any Admin (RULE-012; tie-break deferred per spec).

**Acceptance criteria:**
- [ ] Member without grant gets 403 on guarded action; succession chain verified by test

**Verification:**
- [ ] `vendor/bin/pest --filter="GroupTest"` green

**Dependencies:** Task 5.2

**Files likely touched:**
- `Modules/AccessManagement/*` (models, controllers, policies)

**Estimated scope:** Medium (3-5 files)

### Checkpoint: After Phase 5
- [ ] Full delegation E2E (grant → use → revoke → audit readable)
- [ ] **Review gate:** pattern review (service boundaries, no logic in models) + arch review (explicit-team rule grep-clean)
- [ ] Human sign-off before Phase 6
