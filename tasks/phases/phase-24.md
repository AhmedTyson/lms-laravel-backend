> Index: [tasks/todo.md](../todo.md) -- AI: load this phase file only, not the whole list.

## Phase 24 — Certification (BACKLOG-013, gated)

> Gate: certification formally scoped (new SCOPE-XXX) + ADR. Journey line alone is not scope.

## Task 24.1: Certificates table + issuance

**Description:** Certificate number, verification code, issue/expiry. Issued off completion signal (Phase 10 engine), via queue (Phase 19) when present. Public verification endpoint (unsigned, rate-limited) checks code validity without exposing student data.

**Acceptance criteria:**
- [ ] Verification codes unguessable (CSPRNG, sufficient entropy), unique index
- [ ] Revocation path exists (course retraction / fraud)
- [ ] Public verify endpoint returns valid/invalid only — no PII leak

**Verification:**
- [ ] Pest: issuance on completion, revocation, verify endpoint privacy

**Dependencies:** Global gate; Phases 10, 19

**Files likely touched:**
- `docs/adr/NNNN-certificates.md` (first, post-SCOPE entry)
- `Modules/*/database/migrations/*.php` (post-ADR)

**Estimated scope:** Medium (4-6 files post-ADR)

### Checkpoint: After Phase 24
- [ ] SCOPE entry + ADR + revocation story all exist
- [ ] Human sign-off before Phase 25
