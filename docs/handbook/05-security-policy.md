# 05 — Security Policy (policy)

## 1. Secrets management

- Secrets live in env only (`.env` local, platform vault in staging/prod). `.env` is git-ignored; `git log -p` must never show a real key, token, or password.
- Seed credentials come from `ADMIN_SEED_EMAIL` / `ADMIN_SEED_PASSWORD`. Rotation: change vault value, re-seed or reset via console — never commit the value.
- JWT secret per environment (`php artisan jwt:secret`). `JWT_TTL=60` default; production TTL changes need an ADR.

## 2. Authentication & authorization

- API: JWT via `auth:api` guard exclusively. No session auth, no token-in-URL, no custom crypto.
- Every route declares authorization: Policy method or Gate, covered by a 403 test (handbook 03 §2). Unprotected routes are release-blockers.
- AccessManagement invariants are security-critical: subordinate-only grants, ceiling rule, explicit team context (ADR-008). Any change here needs two reviewers, one being the phase owner.
- Filament `/admin` uses session auth with its own login; admin users are still subject to the same approval/permission model for API actions.

## 3. Input & data protection

- All input validated in FormRequests (never inline `$request->validate()` sprawl in controllers for shared rules). Mass assignment via `$fillable` only.
- File uploads (submissions): validate MIME + size, store outside webroot (`storage/`), serve via signed/authorized responses — never a public URL guessable by ID (IDs are auto-increment, ARCH-005).
- SQL injection: Eloquent/query builder only; raw expressions need a security justification in the PR.

## 4. Transport & platform

- Rate-limit auth endpoints (`login`, `register`, password flows) — throttle middleware, counts in code review.
- Security headers + HTTPS enforced at platform level (see runbook 08); verify in staging rehearsal.
- Dependencies: `composer audit` clean before phase-close PRs. Security advisories on locked packages = immediate patch task, highest priority.

## 5. Auditability

- Every grant/revoke exists in `permission_grants` AND `activity_log` (RULE-005). Phase 13 verifies ledger completeness. Audit tables are append-only: no update/delete paths, no admin UI delete buttons for them.
