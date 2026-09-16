> Index: [tasks/todo.md](../todo.md) -- AI: load this phase file only, not the whole list.

## Phase 16 — Deployment

## Task 16.1: Production readiness

**Description:** Per `docs/handbook/08-build-release-runbook.md`: prod `.env` matrix, `migrate --force` + seed order runbook, Filament admin bootstrap, backup + log review, rollback plan, smoke script (health, login, enroll).

**Acceptance criteria:**
- [ ] Staging deploy rehearsed from scratch using only the runbook; smoke script passes

**Verification:**
- [ ] Rehearsal log attached to PR; tag release `v1.0.0`

**Dependencies:** Phase 15

**Estimated scope:** Small (docs + scripts)

### Checkpoint: Final
- [ ] All phase checkpoints signed; release tagged; retrospective notes filed.
