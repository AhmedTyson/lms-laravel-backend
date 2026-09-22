> Index: [tasks/todo.md](../todo.md) -- AI: load this phase file only, not the whole list.

## Phase 26 — Category Tree (BACKLOG-008, gated)

> Gate: category browsing/filtering needs a tree + ADR. Flat `courses.category` varchar stays until then.

## Task 26.1: Hierarchical categories

**Description:** Tree categories (e.g. Programming > Backend > Laravel) replacing flat varchar. Adjacency + depth guard, or nested-set if subtree queries dominate (ADR decides).

**Acceptance criteria:**
- [ ] Existing varchar values migrate to root-level nodes (no data loss)
- [ ] Depth cap enforced (no runaway nesting)
- [ ] Course filter API accepts node + subtree toggle

**Verification:**
- [ ] Pest: migration fidelity, depth cap, subtree filter

**Dependencies:** Global gate (independent of Phase 22)

**Files likely touched:**
- `docs/adr/NNNN-course-categories.md` (first)
- `Modules/Courses/database/migrations/*.php` (post-ADR)

**Estimated scope:** Small (1-2 files post-ADR)

### Checkpoint: After Phase 26
- [ ] Tree strategy (adjacency vs nested-set) recorded with reason
- [ ] Human sign-off before Phase 27
