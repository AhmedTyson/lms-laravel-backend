> Index: [tasks/todo.md](../todo.md) -- AI: load this phase file only, not the whole list.

## Phase 31 — Observability (future, needs production traffic)

> Gate: deployed traffic to observe. Dev-phase logging stays as-is until then.

## Task 31.1: Metrics, traces, alerts

**Description:** Structured logs (no PII/secrets), latency histograms + error counters per endpoint family, trace correlation IDs across request→job, alert thresholds (p95 >2x baseline, error-rate spikes, queue depth).

**Acceptance criteria:**
- [ ] Every endpoint family emits latency + error metrics
- [ ] Correlation ID flows request → queued job → log line
- [ ] Alerts defined with runbook pointers (which dashboard, who ack)
- [ ] No secrets/PII in any log fixture (tested by assertion on sample lines)

**Verification:**
- [ ] Pest: log-shape tests, correlation propagation test
- [ ] Staging alert fire-drill documented

**Dependencies:** Phases 16 (deployed), 19 (queues exist)

**Files likely touched:**
- `app/Providers/AppServiceProvider.php` (request ID middleware wiring)
- `config/logging.php`, alert configs (env-driven)

**Estimated scope:** Medium (4-6 files)

### Checkpoint: After Phase 31
- [ ] Dashboard + alert inventory committed
- [ ] Human sign-off before Phase 32
