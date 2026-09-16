> Index: [tasks/todo.md](../todo.md) -- AI: load this phase file only, not the whole list.

## Phase 9 — Quizzes (spec 6.5, SCOPE-005, RULE-017)

## Task 9.1: Quiz + question authoring

**Description:** Quiz CRUD (open/close window, `max_attempts`, `%` threshold of dynamic points sum), questions with per-question `points` and 5 types (choice ×2, true/false, short-answer multi-variant, numeric + tolerance).

**Acceptance criteria:**
- [ ] Mixed-points quiz totals correctly; threshold stays percentage after question edits

**Verification:**
- [ ] `vendor/bin/pest --filter="QuizAuthoring"` green

**Dependencies:** Phase 7

**Files likely touched:**
- `Modules/Quizzes/*`

**Estimated scope:** Medium (3-5 files)

## Task 9.2: Attempts + auto-scoring

**Description:** Start (window check at start), answer, submit → auto-score per type; attempt cap enforced; `score` recorded per attempt.

**Acceptance criteria:**
- [ ] Start after `closes_at` → 422; over-cap attempt → 422; each type scores correctly (incl. numeric tolerance, multi-variant text)

**Verification:**
- [ ] `vendor/bin/pest --filter="QuizAttempt"` green

**Dependencies:** Task 9.1

**Files likely touched:**
- `Modules/Quizzes/app/Services/ScoringService.php` + controllers

**Estimated scope:** Medium (3-5 files)

### Checkpoint: After Phase 9
- [ ] Full quiz round-trip E2E; review gate; human sign-off before Phase 10
