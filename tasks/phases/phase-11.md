> Index: [tasks/todo.md](../todo.md) -- AI: load this phase file only, not the whole list.

## Phase 11 — Notifications (spec 6.7)

## Task 11.1: Event-driven queued notifications

**Description:** Listeners on domain events (`UserRegistered`, `InstructorApproved`, `CoursePublished`, `AssignmentGraded`, `PermissionGranted`, …) → queued jobs → `notifications` rows; read/unread API; mailer `log` local.

**Acceptance criteria:**
- [ ] No synchronous sends in request cycle; failed jobs retry via queue; user sees only own notifications

**Verification:**
- [ ] `vendor/bin/pest --filter="NotificationTest"` (fake queue) green

**Dependencies:** Phases 4–10 (events must exist)

**Files likely touched:**
- `Modules/Notifications/*`, listeners in owning modules

**Estimated scope:** Medium (3-5 files)

### Checkpoint: After Phase 11
- [ ] Notification received for grading event E2E; review gate; human sign-off before Phase 12
