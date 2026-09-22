> Index: [tasks/todo.md](../todo.md) -- AI: load this phase file only, not the whole list.

## Phase 23 — Reusable Learning Assets (BACKLOG-014, gated)

> Gate: same asset attached to multiple lessons/courses, or independent versioning need + ADR. Ordered after Phase 22 (lesson shape settled).

## Task 23.1: `learning_resources` table

**Description:** Video/PDF/file assets with own lifecycle, decoupled from `lessons.content_reference` pointer. Lessons reference assets; deleting a lesson never deletes a shared asset (refcount or explicit detach rule).

**Acceptance criteria:**
- [ ] One asset attachable to N lessons; lesson delete leaves shared asset intact
- [ ] Orphan-asset policy documented (retain vs prune job)
- [ ] Storage paths validated (disk allowlist, no traversal)

**Verification:**
- [ ] Pest: shared-asset delete safety, refcount behavior

**Dependencies:** Global gate; Phase 22

**Files likely touched:**
- `docs/adr/NNNN-learning-resources.md` (first)
- `Modules/Courses/database/migrations/*.php` (post-ADR)

**Estimated scope:** Medium (3-5 files post-ADR)

### Checkpoint: After Phase 23
- [ ] Orphan policy + storage validation committed
- [ ] Human sign-off before Phase 24
