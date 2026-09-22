> Index: [tasks/todo.md](../todo.md) -- AI: load this phase file only, not the whole list.

## Phase 30 — Abuse Hardening (future, needs attack evidence or launch proximity)

> Gate: pre-launch review or observed abuse. Current named limiters are the baseline; this phase sharpens them.

## Task 30.1: Enumerations + velocity + bot defenses

**Description:** Tighten IDOR-adjacent enumerations (uniform 404/403 discipline review), login velocity per account (not just IP), bot scoring on register (config-flagged, off in dev), security headers audit, secret-scan in CI.

**Acceptance criteria:**
- [ ] Enumeration review: every id-keyed endpoint returns indistinguishable responses for missing vs forbidden (where spec allows)
- [ ] Account-velocity limiter independent of IP limiter
- [ ] `composer audit` + secret-scan wired in CI (fail on advisory)

**Verification:**
- [ ] Pest: oracle tests (missing vs forbidden identical), velocity tests
- [ ] CI run proves audit + scan gates

**Dependencies:** Phase 13 (security baseline exists)

**Files likely touched:**
- `app/Providers/AppServiceProvider.php` (limiters)
- `.github/workflows/ci.yml`
- `Modules/*/app/Http/Controllers/*.php` (oracle uniformity)

**Estimated scope:** Medium (4-6 files)

### Checkpoint: After Phase 30
- [ ] Oracle matrix + limiter table committed
- [ ] Human sign-off before Phase 31
