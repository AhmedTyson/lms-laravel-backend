> Index: [tasks/todo.md](../todo.md) -- AI: load this phase file only, not the whole list.

## Phase 35 — API v2 & Deprecation (future, needs breaking-change pressure)

> Gate: a breaking change that cannot ship compatibly (field removal, semantic change). Additive changes never trigger this phase.

## Task 35.1: Versioned API + deprecation discipline

**Description:** URL-prefix versioning (`/api/v2/...`), v1 frozen + deprecated with removal date. Breaking-change detector (Brain or linter) gates every contract diff. Migration notes per breaking endpoint.

**Acceptance criteria:**
- [ ] v1 and v2 served side by side; v1 responses byte-identical to pre-freeze fixtures
- [ ] Every v1 endpoint carries deprecation header + sunset date
- [ ] Detector runs in CI on contract changes

**Verification:**
- [ ] Pest: v1 fixture-freeze tests (golden responses), v2 behavior tests
- [ ] CI detector proof on a trial breaking diff

**Dependencies:** Phase 14 (API docs versioned alongside)

**Files likely touched:**
- `routes/` version prefixes, `docs/routes/v2/` contract docs
- `.github/workflows/ci.yml` (detector gate)

**Estimated scope:** Large (routes + docs + CI)

### Checkpoint: After Phase 35
- [ ] Sunset calendar + migration notes committed
- [ ] Human sign-off before Phase 36
