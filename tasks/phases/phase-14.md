> Index: [tasks/todo.md](../todo.md) -- AI: load this phase file only, not the whole list.

## Phase 14 — API Documentation

## Task 14.1: Scramble completeness

**Description:** Every endpoint annotated (auth, params, responses incl. errors); `/docs/api` reviewed page-by-page against Postman-style manual calls.

**Acceptance criteria:**
- [ ] No undocumented route (`route:list` vs Scramble output diffed); all error shapes documented

**Verification:**
- [ ] Manual docs review checklist in PR

**Dependencies:** Phase 13

**Estimated scope:** Small (annotations across controllers)

### Checkpoint: After Phase 14 — docs approved; sign-off before Phase 15
