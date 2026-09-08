# 01 — Development Workflow (policy)

Effective for all contributors, human and agent. Non-compliance blocks merge.

## 1. Branching

- `main` is always deployable and CI-green. Never push directly to `main`.
- One branch per task: `phase/<n>-<slug>` (e.g. `phase/5-grant-revoke-api`) or `fix/<slug>`.
- Keep branches rebased on `main`; merge via squash. One task = one PR.

## 2. Commits

- Conventional Commits: `feat:`, `fix:`, `chore:`, `docs:`, `test:`, `refactor:` + imperative subject ≤ 72 chars.
- Every commit must pass the four gates locally (see §4). No "fix CI" follow-ups as a habit — run gates before push.

## 3. Pull requests

- PR template fields (all mandatory): linked task (e.g. `Task 5.2`), spec references (RULE-/ADR- IDs), verification evidence (Pest output, manual steps), risk note.
- Scope: one phase-task per PR. A PR touching two modules needs written justification.
- Draft PRs for work-in-progress; mark ready only when gates + self-review done.

## 4. Required gates (local = CI)

```powershell
./vendor/bin/pint --test
vendor/bin/pest --colors=never
./vendor/bin/phpstan analyse --no-progress --memory-limit=1G
php artisan migrate:fresh --seed --force
```

CI (`.github/workflows/ci.yml`) runs the same four on PHP 8.4. Red CI = no review, no merge.

## 5. Task discipline

- Work phases in `tasks/todo.md` order. Never start a task whose dependencies are unchecked.
- Each phase ends with a checkpoint + human sign-off. Record sign-off as a comment on the phase PR.
- If a task reveals missing prerequisites, stop and file it in `tasks/todo.md` as a new task — do not silently expand scope.
