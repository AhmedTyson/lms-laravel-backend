> Index: [tasks/todo.md](../todo.md) -- AI: load this phase file only, not the whole list.

## Phase 4 — Auth slice (spec 6.1, SCOPE-004) [x]

## Task 4.1: Registration + email verification

**Description:** `POST /api/register` (Student vs Instructor paths; Instructor gets `approval_status=pending`, `manager_id` NULL until approval). Verification gate blocks enroll/publish (not login/browse).

**Acceptance criteria:**
- [x] Unverified user gets 403 on enrollment stub; verified passes gate
- [x] Student `manager_id` always NULL

**Verification:**
- [x] `vendor/bin/pest --filter="AuthRegistration"` green
- [x] Manual: register → verify link → gate opens

**Dependencies:** Phase 3

**Files likely touched:**
- `Modules/Auth/app/Http/Controllers/*.php`
- `Modules/Auth/routes/api.php`
- `Modules/Auth/tests/Feature/*.php`

**Estimated scope:** Medium (3-5 files)

## Task 4.2: Login, refresh, me (JWT)

**Description:** `POST /api/login` (JWT), refresh, logout (blacklist), `GET /api/me` behind `auth:api`. No session usage in API.

**Acceptance criteria:**
- [x] 401 without token; expired token refreshable within grace; blacklisted token rejected

**Verification:**
- [x] `vendor/bin/pest --filter="AuthLogin"` green

**Dependencies:** Task 4.1

**Files likely touched:**
- `Modules/Auth/app/Http/Controllers/*.php`

**Estimated scope:** Small (1-2 files)

## Task 4.3: Instructor approval workflow

**Description:** Admin-only `POST /api/instructors/{id}/approve` sets `approved` + `manager_id=<admin>` via `ManagerAssignmentService`, fires `InstructorApproved`. Pending instructors 403 on authoring routes.

**Acceptance criteria:**
- [x] Non-admin approve → 403; double-approve idempotent; RULE-010 (manager set at approval, not registration)

**Verification:**
- [x] `vendor/bin/pest --filter="InstructorApproval"` green

**Dependencies:** Tasks 4.2, 5.1 (`ManagerAssignmentService` — build the service first if sequencing demands; approval endpoint wires after)

**Files likely touched:**
- `Modules/Auth/*`, `Modules/AccessManagement/app/Services/ManagerAssignmentService.php`

**Estimated scope:** Medium (3-5 files)

### Checkpoint: After Phase 4 [x]
- [x] Register → verify → login → approve E2E works on SQLite
- [x] **Review gate:** security pass on auth (hashing, tokens, gates) per handbook §05
- [x] Human sign-off before Phase 5
