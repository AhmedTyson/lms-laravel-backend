> Index: [tasks/todo.md](../todo.md) -- AI: load this phase file only, not the whole list.

## Appendix B — Future Schema Backlog Register (Phase 8+ candidates, reference only)

Source: `LMS — Future Schema Backlog Register v1` (reviewed 2026-09-16, external AI-authored reference doc).
Status: **non-blocking, non-actionable parking lot.** Not ADRs. Does not modify BRD/PRD, does not add/rename/restructure any live table, does not block Phase 7 (API Contracts).
Numbering note: register's "Phase 7 = API Contracts / Phase 8+ = future schema" does **not** map to this file's numbering (here Phase 7 = Enrollment, Phase 8 = Assignments). Gate below means register-Phase-7 (API Contracts), not todo-Phase-7.

### Global gate (all BACKLOG items)
- [ ] [HOLD] Register-Phase-7 (API Contracts) formally closed
- [ ] [HOLD] Real product requirement triggers reconsideration (not "reference doc suggests it")
- [ ] [HOLD] Decision Procedure passes (10 questions: concept, BRD/PRD-required, existing-table check, correctness vs abstraction, lifecycle, relationships, querying/indexing, history, package-available, no needless polymorphism) → promoted to real ADR
- [ ] [HOLD] Reviewed only when register-Phase-8 planning begins

### B.1 Already covered — no tasks (BACKLOG-001–006, superseded, not gaps)
Spatie Permission + `permission_grants` (001); `groups` + `group_members` (002); `activity_log` + grants ledger (003); `attempts`/`attempt_answers` (004); `submissions` (005); `notifications` (006). No action; listed so nobody re-derives them as gaps.

### B.2 Candidates — ordered by dependency, all [HOLD]

## Task B-007: Course structure hierarchy (Section → Subsection → Unit → Component) [HOLD]

**Description:** Replace flat `Course → Lesson` with nested structure. Current: `courses` → `lessons` (`order` unique per course); PRD 6.3/RULE-014 specifies ordered lessons only.

**Acceptance criteria:**
- [ ] [HOLD] Trigger present: real requirement for grouped modules/units or cross-course content reuse
- [ ] [HOLD] Global gate passes → ADR promoted

**Verification:**
- [ ] ADR file exists under `docs/adr/`; no migration written before ADR

**Dependencies:** Global gate; affects `lessons`, `component_completions` morph targets

**Files likely touched:**
- `docs/adr/NNNN-course-hierarchy.md`
- (post-ADR only) `Modules/Courses/database/migrations/*.php`

**Estimated scope:** Large (5-8 files post-ADR) — keep as single gated task until ADR slices it

## Task B-008: Hierarchical course categories [HOLD]

**Description:** Tree categories (e.g. Programming > Backend > Laravel) vs current flat `courses.category` varchar.

**Acceptance criteria:**
- [ ] [HOLD] Trigger: category browsing/filtering needs a tree
- [ ] [HOLD] Global gate passes → ADR promoted

**Verification:**
- [ ] ADR file exists; no migration before ADR

**Dependencies:** Global gate (independent of B-007)

**Files likely touched:**
- `docs/adr/NNNN-course-categories.md`

**Estimated scope:** Small (1-2 files post-ADR)

## Task B-009: Course instructors many-to-many (co-instructor, TA, reviewer) [HOLD]

**Description:** Replace single `courses.instructor_id` FK with join table. PRD currently assumes one owning instructor.

**Acceptance criteria:**
- [ ] [HOLD] Trigger: real co-teaching/TA requirement appears
- [ ] [HOLD] Global gate passes → ADR promoted

**Verification:**
- [ ] ADR file exists; no migration before ADR

**Dependencies:** Global gate (relates to `users`, Spatie Teams)

**Files likely touched:**
- `docs/adr/NNNN-course-instructors.md`

**Estimated scope:** Medium (3-5 files post-ADR)

## Task B-010: Course prerequisites [HOLD]

**Description:** Prerequisite gating before enrollment. No current business rule requires it.

**Acceptance criteria:**
- [ ] [HOLD] Trigger: PRD amended with prerequisite-gating rule
- [ ] [HOLD] Global gate passes → ADR promoted

**Verification:**
- [ ] ADR file exists; enrollment guard spec updated alongside

**Dependencies:** Global gate (touches enrollment guards)

**Files likely touched:**
- `docs/adr/NNNN-course-prerequisites.md`

**Estimated scope:** Medium (3-5 files post-ADR)

## Task B-011: Course versions (content versioning) [HOLD]

**Description:** Version published course content (student sees enrolled-under version). Relates to open "Archived → Republishing" item.

**Acceptance criteria:**
- [ ] [HOLD] Trigger: historical stability of published content becomes a requirement
- [ ] [HOLD] Global gate passes → ADR promoted

**Verification:**
- [ ] ADR file exists; no migration before ADR

**Dependencies:** Global gate (relates to course lifecycle Task 6.1 archive rules)

**Files likely touched:**
- `docs/adr/NNNN-course-versions.md`

**Estimated scope:** Medium (3-5 files post-ADR)

## Task B-014: Learning resources as reusable assets [HOLD]

**Description:** `learning_resources` table (video/PDF/file, own lifecycle) decoupled from single `lessons.content_reference` pointer.

**Acceptance criteria:**
- [ ] [HOLD] Trigger: same asset must attach to multiple lessons/courses or needs independent versioning
- [ ] [HOLD] Global gate passes → ADR promoted

**Verification:**
- [ ] ADR file exists; no migration before ADR

**Dependencies:** Global gate; ordered after B-007 (lesson shape must settle first)

**Files likely touched:**
- `docs/adr/NNNN-learning-resources.md`

**Estimated scope:** Medium (3-5 files post-ADR)

## Task B-012: Gradebook as distinct domain (grade_items, grades) [HOLD]

**Description:** Weighted gradebook (Quiz 1 = 10%, Assignment 1 = 20%) distinct from raw `submissions.grade` / `attempts.score`. Current Progress (RULE-018/019) is threshold/completion-based; BRD/PRD has no weighted-final-grade concept.

**Acceptance criteria:**
- [ ] [HOLD] Trigger: final course grade/GPA requirement distinct from completion tracking
- [ ] [HOLD] Global gate passes → ADR promoted

**Verification:**
- [ ] ADR file exists; threshold math (per-component totals) preserved

**Dependencies:** Global gate; ordered after submissions/attempts semantics stable

**Files likely touched:**
- `docs/adr/NNNN-gradebook.md`

**Estimated scope:** Medium (3-5 files post-ADR)

## Task B-013: Certification (certificates table) [HOLD]

**Description:** Certificate number, verification code, issue/expiry. Journey line "Receive certificate" (PRD §05) exists but never scoped as Feature 6.1–6.10 with rules.

**Acceptance criteria:**
- [ ] [HOLD] Trigger: certification formally added to Scope (new SCOPE-XXX entry first)
- [ ] [HOLD] Global gate passes → ADR promoted

**Verification:**
- [ ] SCOPE entry + ADR file exist; no migration before either

**Dependencies:** Global gate; ordered after Progress completion engine (depends on completion signal)

**Files likely touched:**
- `docs/adr/NNNN-certificates.md`

**Estimated scope:** Medium (3-5 files post-ADR)

### B.3 Explicitly rejected — no tasks, by design
Discussions/Forums, Live Sessions, SCORM/xAPI, LTI/SSO, Payments/Subscriptions/Coupons, Gamification/Badges, Surveys, Advanced Analytics, Competency Management/Learning Paths. Match BRD Out-of-Scope; reference doc independently arriving at same exclusions is cross-check, not revisit reason.

### Checkpoint: Appendix B
- [ ] [HOLD] Zero migrations from B-tasks exist before their ADR + trigger
- [ ] Register-Phase-8 planning reviews this appendix only when global gate opens
