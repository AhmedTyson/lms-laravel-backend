# 07 — Definition of Done (policy)

A task is done only when ALL applicable boxes check. A phase is done only when its checkpoint checks.

## 1. Per-task Done

- [ ] Acceptance criteria in `tasks/todo.md` each map to a passing test
- [ ] `pint --test`, full Pest suite, PHPStan, `migrate:fresh --seed` green locally
- [ ] Code in the correct module + layer; dependency rules respected
- [ ] Migrations portable; seeders idempotent; no secrets committed
- [ ] New/changed endpoints annotated for Scramble; error shapes documented
- [ ] ADR written if the task adds a package, pattern, or contract
- [ ] Self-review done (handbook 04 §4); PR opened with evidence

## 2. Per-phase Done (checkpoint)

- [ ] All phase tasks Done; full suite + CI green on the phase PR
- [ ] Review lens applied (code / arch / patterns per the checkpoint note)
- [ ] E2E/manual verification recorded for user-facing flows
- [ ] Human sign-off comment on the PR; `tasks/todo.md` boxes ticked in the same PR
- [ ] No deferred hacks without a follow-up task filed (no silent TODOs)

## 3. Release Done (Phase 16)

- [ ] Staging rehearsed from scratch using only the runbook (08)
- [ ] Smoke script passes (health, login, enroll, grade round-trip)
- [ ] Backup + rollback verified; monitoring reachable
- [ ] Tag `vX.Y.Z`; retrospective notes filed under `docs/retrospectives/`
