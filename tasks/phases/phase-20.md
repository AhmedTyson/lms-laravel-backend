> Index: [tasks/todo.md](../todo.md) -- AI: load this phase file only, not the whole list.

## Phase 20 — Full-Text Search (future, needs catalog scale pain)

> Gate: `LIKE` queries measurably slow or relevance complaints on real catalog data. Not before.

## Task 20.1: Search backend + catalog endpoint

**Description:** Full-text index (MySQL `FULLTEXT` first; dedicated engine only with measured need) over course title/description/category. `GET /api/courses?search=` upgrades from `LIKE` to ranked match; API shape unchanged (filter value only, ranking internal).

**Acceptance criteria:**
- [ ] Ranked results (title matches above description matches)
- [ ] Empty/stop-word queries degrade to unfiltered list, never 500
- [ ] SQLite dev path still works (falls back to `LIKE` when no fulltext index)

**Verification:**
- [ ] Pest relevance test (known corpus, expected order)
- [ ] Migration runs on MySQL + SQLite

**Dependencies:** Phase 6 (catalog exists)

**Files likely touched:**
- `Modules/Courses/database/migrations/*.php`
- `Modules/Courses/app/Models/Course.php` (search scope)

**Estimated scope:** Small (2-3 files)

### Checkpoint: After Phase 20
- [ ] Relevance expectations committed with the test corpus
- [ ] Human sign-off before Phase 21
