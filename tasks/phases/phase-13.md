> Index: [tasks/todo.md](../todo.md) -- AI: load this phase file only, not the whole list.

## Phase 13 — Integration & Security hardening

## Task 13.1: End-to-end journeys

**Description:** Pest E2E: student full journey + instructor full journey + delegation journey against SQLite; edge-case matrix from spec §2.8 all covered.

**Acceptance criteria:**
- [ ] All §2.8 edge cases have a failing-before/passing-after test record

**Verification:**
- [ ] Full suite green; manual walkthrough on staging

**Dependencies:** Phases 4–12

**Files likely touched:**
- `tests/Feature/Journeys/*.php`

**Estimated scope:** Medium (3-5 files)

## Task 13.2: OWASP + audit pass

**Description:** Per `docs/handbook/05-security-policy.md`: authz matrix audit (every route has policy/gate test), rate limits on auth endpoints, secrets scan (no keys in repo), audit-ledger completeness (every grant/revoke in both ledgers).

**Acceptance criteria:**
- [ ] Zero routes without authorization test; `git log -p | grep -i secret` clean (beyond placeholders)

**Verification:**
- [ ] Security checklist signed in PR; `pint`, suite, PHPStan green

**Dependencies:** Task 13.1

**Files likely touched:**
- `routes/*.php`, `Modules/*/routes/*.php`, `config/rate-limiting` (if added)

**Estimated scope:** Small (1-2 files + review)

### Checkpoint: After Phase 13
- [ ] **Review gate:** full code + arch + pattern review across modules; human sign-off before Phase 14
