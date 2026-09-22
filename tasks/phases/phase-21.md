> Index: [tasks/todo.md](../todo.md) -- AI: load this phase file only, not the whole list.

## Phase 21 — Gradebook (BACKLOG-012, gated)

> Gate (Appendix B global gate): final-grade/GPA requirement distinct from completion tracking + SCOPE entry + ADR. Current threshold model (RULE-018/019) stays untouched until then.

## Task 21.1: Weighted grade items + grades

**Description:** `grade_items` (component reference, weight %) + `grades` (computed, auditable). Threshold math per-component totals preserved; gradebook reads submissions/attempts, never replaces them.

**Acceptance criteria:**
- [ ] Weights sum to 100 per course (validated); re-weighting recomputes transparently
- [ ] Raw grades (submissions/attempts) immutable by gradebook writes
- [ ] `GET /api/courses/{id}/gradebook` + `GET /api/me/grades` behind team scope

**Verification:**
- [ ] Pest: weight math, re-weight recompute, scope enforcement

**Dependencies:** Global gate; Phases 8–10 stable

**Files likely touched:**
- `Modules/Progress/database/migrations/*.php` (or new Gradebook module per ADR)
- `docs/adr/NNNN-gradebook.md` (before any migration)

**Estimated scope:** Large (5-8 files post-ADR)

### Checkpoint: After Phase 21
- [ ] ADR + SCOPE entry exist; threshold engine untouched, proven by suite
- [ ] Human sign-off before Phase 22
