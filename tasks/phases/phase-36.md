> Index: [tasks/todo.md](../todo.md) -- AI: load this phase file only, not the whole list.

## Phase 36 — Multi-Tenancy Hardening (future, needs second-tenant demand)

> Gate: a real second tenant (org/institution). Single-org operation never triggers this.

## Task 36.1: Tenant isolation audit + enforcement

**Description:** Every read/write tenant-scoped (Spatie Teams already the mechanism — ADR-008). Global scopes where safe, explicit scope checks where not. Cross-tenant test matrix: each endpoint × foreign-tenant actor = 403/404.

**Acceptance criteria:**
- [ ] Isolation matrix committed (endpoint × actor → expected outcome, each tested)
- [ ] No ambient team reads (grep-clean, same rule as Phase 5 review gate)
- [ ] Tenant onboarding/offboarding path documented (provision, seed admin, deprovision)

**Verification:**
- [ ] Pest: full cross-tenant matrix green
- [ ] `grep -rn "setPermissionsTeamId" Modules/` empty (ambient reads banned)

**Dependencies:** Phases 5 (team rule), 12 (team-scoped reporting)

**Files likely touched:**
- `Modules/*/app/Models/*.php` (scopes)
- `Modules/*/app/Http/Controllers/*.php` (scope threading)

**Estimated scope:** Large (cross-cutting)

### Checkpoint: After Phase 36 (final)
- [ ] Isolation matrix + onboarding runbook committed
- [ ] Human sign-off closes the future track
