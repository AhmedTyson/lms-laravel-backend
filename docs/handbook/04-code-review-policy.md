# 04 — Code Review Policy (policy)

## 1. When review happens

- Every PR needs one approving review. Phase checkpoints (in `tasks/todo.md`) additionally need human sign-off recorded on the PR.
- Three review lenses rotate per phase: **code** (quality, tests), **arch** (module/layer placement, dependencies), **patterns** (no over-engineering, convention reuse). The phase checkpoint states which lens applies; Phase 13 requires all three.

## 2. Reviewer checklist (must verify, not skim)

- [ ] Task acceptance criteria each map to a test; tests fail without the change (spot-check).
- [ ] Files live in the correct module and layer (`docs/module-dependencies.md` respected; no upward imports).
- [ ] No forbidden patterns (handbook 02 §3): model-event logic, bare `hasPermissionTo()` in AccessManagement/Reporting, raw SQL, hardcoded secrets.
- [ ] Migrations portable (SQLite-safe), uniques/indexes match spec §5, ledger tables append-only.
- [ ] API shapes documented (Scramble annotations for new/changed endpoints).
- [ ] Pint/Pest/PHPStan evidence attached; CI green.

## 3. Review conduct

- One finding = one comment: file, line, problem, concrete fix. No drive-by style nits (Pint owns style).
- Blocking vs advisory labels required. Blocking: correctness, security, arch violations, missing tests. Everything else advisory.
- Author responds to every blocking comment (fix or rebut with spec/ADR citation). Stale threads block merge.
- Review SLA: 1 business day for task PRs, 2 for phase-close PRs.

## 4. Self-review first

Before requesting review, the author walks their own diff and confirms: no debug leftovers, no commented code, no unrelated changes, `.env`/keys untouched.
