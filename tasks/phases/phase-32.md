> Index: [tasks/todo.md](../todo.md) -- AI: load this phase file only, not the whole list.

## Phase 32 — Backup & Disaster Recovery (future, pre-launch required)

> Gate: production data exists. RTO/RPO targets set by product first — engineering implements to targets, never invents them.

## Task 32.1: Backup, restore drills, runbook

**Description:** Continuous WAL archiving + daily base backups (per backend-architect rule), weekly restore-against-staging drill, 3am runbook (symptom → exact command → escalate path).

**Acceptance criteria:**
- [ ] Restore drill passes on staging on schedule (log kept)
- [ ] RTO/RPO measured against targets, gap documented if any
- [ ] Secrets backup story exists (vault, never repo)

**Verification:**
- [ ] Drill log committed quarterly; runbook reviewed per drill

**Dependencies:** Phase 16 (production topology known)

**Files likely touched:**
- `docs/runbook-backup.md` (new), infra configs (env-driven)

**Estimated scope:** Small (docs + infra config)

### Checkpoint: After Phase 32
- [ ] RTO/RPO statement + drill log exist
- [ ] Human sign-off before Phase 33
