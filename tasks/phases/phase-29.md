> Index: [tasks/todo.md](../todo.md) -- AI: load this phase file only, not the whole list.

## Phase 29 — Audit Trail Expansion (future, compliance-gated)

> Gate: compliance/audit requirement naming retained events + retention window. `permission_grants` + `activity_log` already cover grant/revoke + model trail.

## Task 29.1: Retention + coverage completion

**Description:** Retention enforcement per model (prune job, `permission_grants` never pruned). Coverage audit: every privileged write (role changes, overrides, grade edits, succession) emits a trail row. Read path for auditors (scoped, paginated).

**Acceptance criteria:**
- [ ] Coverage matrix committed (privileged write → trail row, each proven by test)
- [ ] Prune job + schedule, ledger tables excluded by name
- [ ] No PII in trail properties beyond what policy allows

**Verification:**
- [ ] Pest: one test per matrix row; prune dry-run test

**Dependencies:** Phases 5, 10 (writes exist to cover)

**Files likely touched:**
- `Modules/*/app/Observers/*` or explicit log calls (no model-event business logic per handbook 02)
- `routes/console.php` (prune schedule)

**Estimated scope:** Medium (4-6 files)

### Checkpoint: After Phase 29
- [ ] Coverage matrix + retention policy committed
- [ ] Human sign-off before Phase 30
