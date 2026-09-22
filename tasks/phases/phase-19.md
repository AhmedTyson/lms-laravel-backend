> Index: [tasks/todo.md](../todo.md) -- AI: load this phase file only, not the whole list.

## Phase 19 — Queues & Background Jobs (future, needs slow-request evidence)

> Gate: a request-path operation measurably slow (>500ms p95) or needing retries. Sync stays default until then.

## Task 19.1: Queue driver + first offloaded jobs

**Description:** Redis queue on staging/prod. First candidates: verification/notification mails (Phase 11), grading fan-out, certificate issuance. Every job idempotent (key on entity id + action), retries with backoff, dead-letter visibility.

**Acceptance criteria:**
- [ ] No `Mail::send` / heavy compute awaited inline on request path
- [ ] Failed jobs visible (failed-job table + alert), retry-safe by re-run test
- [ ] `QUEUE_CONNECTION=sync` still works locally (jobs run inline)

**Verification:**
- [ ] `vendor/bin/pest` with `QUEUE_CONNECTION=sync` green
- [ ] Fake-queue test asserts job dispatched (not executed inline)

**Dependencies:** Phase 11 (mail volume exists)

**Files likely touched:**
- `Modules/*/app/Jobs/*.php`
- `config/queue.php`, `.env.example`

**Estimated scope:** Medium (4-6 files)

### Checkpoint: After Phase 19
- [ ] Job inventory (job → trigger → idempotency key → retry policy) committed
- [ ] Human sign-off before Phase 20
