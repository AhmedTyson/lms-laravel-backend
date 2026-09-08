# 03 — Testing Policy (policy)

## 1. Framework

- Pest v4 (`vendor/bin/pest`). phpunit.xml forces SQLite `:memory:` — never override DB env in tests.
- Single test: `vendor/bin/pest --filter="Name"`. Full suite before every commit and every phase checkpoint.

## 2. What must be tested (per slice)

- Every endpoint: happy path + 401 (no token) + 403 (wrong role/context) + 422 (invalid input).
- Every business rule cited in the task (RULE- IDs): a dedicated test naming the rule.
- Every race the spec closes at DB level (double enroll, duplicate attempt, concurrent reassign): a test proving the constraint fires.
- Every explicit-team-context path: a stale-context regression test proving ambient state can't leak (ADR-008).

## 3. Test placement

- Module behavior: `Modules/<Name>/tests/Feature/`. Cross-module journeys: `tests/Feature/Journeys/`.
- Factories for every model; seeders exercised by `migrate:fresh --seed` in CI.

## 4. Test quality rules

- Tests assert behavior, not implementation: through HTTP + database state, not private methods.
- No sleep-based timing tests; use time-travel helpers for windows (`opens_at`/`closes_at`, `due_date`, `is_late`).
- Failing-before/passing-after: for bug fixes and edge cases (§2.8), commit the failing test first where practical.
- Flaky tests are defects: quarantine immediately, fix within the same phase, never `@skip` silently.

## 5. Coverage expectation

- No hard percentage gate (rejected as gameable). Instead: every task's acceptance criteria map 1:1 to tests, reviewed in the phase PR. Untested acceptance criterion = task not done.
