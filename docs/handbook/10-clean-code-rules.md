# 10 — Clean-Code Rules (process)

Distilled from session audits. Applies to every phase. Violations get a test, a fix, or a ticket — never silence.

## 1. Controller shape

Validate (FormRequest) → authorize (Policy/Gate) → delegate (service) → return (`ApiResponse` + Resource). No inline `$request->validate()` except marked Phase stubs. No business logic in controllers beyond orchestration.

## 2. One envelope

All JSON via `App\Support\ApiResponse` (`success`/`data`/`error`/`jwt`). No hand-built `message`/`data`/`error_code` arrays. 429s included.

## 3. Fillable minimal

`$fillable`/`#[Fillable]` holds identity fields only. Privileged fields (`manager_id`, `approval_status`, roles) set via `forceFill` inside authorized paths only. Factories bypass fillable — tests still seed freely.

## 4. Enums over strings

Status/type fields get native backed enums + casts. No `'pending'` string compares outside the enum file. Add `BelongsTo`/`HasMany` relations instead of raw FK reads.

## 5. Narrow exceptions

Throwing constructors go inside `try`. Catch the narrowest exception (library exception, `Exception`), never bare `Throwable`. Fallbacks must not persist garbage — null or reject, never raw invalid input.

## 6. Single-query patterns

No `exists()` + fetch pairs. One `first()`, null-check, act. Name booleans by outcome (`assignRoleIfExists(): bool`), never silent void on fallible work.

## 7. Module ownership

Each module owns its cross-cutting config (rate limiters, policies) in its own provider. Core keeps shared-only (`api` limiter, morph map). New shared helper needs a second consumer or stays local.

## 8. Delete dead code

Pass-through resources, empty stubs, commented blocks, unused imports — delete on sight. Pint enforces imports; reviewers enforce the rest.

## 9. Comments explain why

Delete restatements of method names. Keep: rule refs (`RULE-002`), ADR refs, deliberate mocks/stubs, non-obvious workarounds with expiry condition.

## 10. Review loop per change

- Gates before commit: `pint --test`, `phpstan`, `pest`. Red stays local.
- Endpoint changed → `brain:export-context --route=<uri>` re-read.
- Commit landed → `docs/ops/jev-audit/jev_audit.py --repo . --limit 5` dry run.
- Finding found → fix now, or ticket with RULE/SCOPE id. No orphan findings.

## Open backlog (from last audit)

| # | Item | Severity | Status |
|---|---|---|---|
| 1 | `verifyEmail` never checks `signature`/`expires` | High | done (`423e36a`, HMAC + expiry) |
| 2 | `Registered` event has no listener, mail never sent | Medium | stubbed (`2cf36ba`), real mail Phase 11 |
| 3 | Phone unique checked pre-mutator (E.164 bypass) | Medium | done (`99116b8`, `PhoneNormalizer` + `prepareForValidation`) |
| 4 | `googleCallback` inline validate, mock user | Low | stub to Phase 7 |
| 5 | `logout`/`refresh`/`me` lack dedicated tests | Low | done (`5f76224`, `AuthSessionTest`) |
