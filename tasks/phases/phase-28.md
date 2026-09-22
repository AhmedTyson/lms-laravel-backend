> Index: [tasks/todo.md](../todo.md) -- AI: load this phase file only, not the whole list.

## Phase 28 — Content Versioning (BACKLOG-011, gated, weakest case)

> Gate: historical stability of published content becomes a requirement + ADR. Mainstream LMSs version via copy/backup, not published-content versions — keep this gated hardest.

## Task 28.1: Published-version pinning

**Description:** Students see the version enrolled under; edits create new versions, never mutate published rows in place. Relates to open "Archived → Republishing" item (archive rules, Task 6.1).

**Acceptance criteria:**
- [ ] Enrolled students pinned; new enrollments get latest
- [ ] Storage growth bounded (retention policy for superseded versions)
- [ ] Grade/progress references resolve against pinned version

**Verification:**
- [ ] Pest: pin stability across edit, retention pruning

**Dependencies:** Global gate; relates to Task 6.1 archive rules

**Files likely touched:**
- `docs/adr/NNNN-course-versions.md` (first)
- `Modules/Courses/database/migrations/*.php` (post-ADR)

**Estimated scope:** Medium (3-5 files post-ADR)

### Checkpoint: After Phase 28
- [ ] Retention policy + pin semantics committed
- [ ] Human sign-off before Phase 29
