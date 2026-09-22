# LMS Laravel -- Task Index

Source: `docs/LMS_Laravel_BRD_PRD_v2_1_full.md` v2.1. Policies: `docs/handbook/`. Plan: `tasks/plan.md`.
Rule: work phases strictly in order; each checkpoint needs human sign-off before next phase.

AI usage: read this index first, then load ONLY the phase file needed from `tasks/phases/`. Never load all phase files.
Skill: `fast-plan` (index-first, token-capped planning). Machine index: `tasks/phases/manifest.json`.

| Phase | Status | Tasks | Checkpoint | File |
|---|---|---|---|---|
| Phase 1 — Env init [x] | done | -- | signed | `tasks/phases/phase-01.md` (8 lines) |
| Phase 2 — Skeleton for developers [x] | done | -- | signed | `tasks/phases/phase-02.md` (8 lines) |
| Phase 3 — Database Implementation (spec §5, 19 tables) [x] | done | -- | signed | `tasks/phases/phase-03.md` (103 lines) |
| Phase 4 — Auth slice (spec 6.1, SCOPE-004) [x] | done | -- | signed | `tasks/phases/phase-04.md` (61 lines) |
| Phase 5 — AccessManagement (RULE-002–012, ADR-007/008) | open | Task 5.1<br>Task 5.2<br>Task 5.3 | pending (3 open) | `tasks/phases/phase-05.md` (62 lines) |
| Phase 6 — Courses + Lessons (spec 6.2, 6.3) | open | Task 6.1<br>Task 6.2 | pending (2 open) | `tasks/phases/phase-06.md` (39 lines) |
| Phase 7 — Enrollment | open | Task 7.1 | pending (1 open) | `tasks/phases/phase-07.md` (21 lines) |
| Phase 8 — Assignments (spec 6.4, RULE-015/016) | open | Task 8.1<br>Task 8.2 | pending (1 open) | `tasks/phases/phase-08.md` (38 lines) |
| Phase 9 — Quizzes (spec 6.5, SCOPE-005, RULE-017) | open | Task 9.1<br>Task 9.2 | pending (1 open) | `tasks/phases/phase-09.md` (38 lines) |
| Phase 10 — Progress (RULE-018 revised, RULE-019) | open | Task 10.1 | pending (1 open) | `tasks/phases/phase-10.md` (21 lines) |
| Phase 11 — Notifications (spec 6.7) | open | Task 11.1 | pending (1 open) | `tasks/phases/phase-11.md` (21 lines) |
| Phase 12 — Reporting (spec 6.8, ADR-008) | open | Task 12.1 | pending (1 open) | `tasks/phases/phase-12.md` (21 lines) |
| Phase 13 — Integration & Security hardening | open | Task 13.1<br>Task 13.2 | pending (1 open) | `tasks/phases/phase-13.md` (38 lines) |
| Phase 14 — API Documentation | open | Task 14.1 | no boxes | `tasks/phases/phase-14.md` (17 lines) |
| Phase 15 — Performance | open | Task 15.1 | no boxes | `tasks/phases/phase-15.md` (17 lines) |
| Phase 16 — Deployment | open | Task 16.1 | pending (1 open) | `tasks/phases/phase-16.md` (18 lines) |
| Appendix B — Future Schema Backlog Register (Phase 8+ candidates, reference only) | hold | Task B-007<br>Task B-008<br>Task B-009<br>Task B-010<br>Task B-011<br>Task B-014<br>Task B-012<br>Task B-013 | gated (2 holds) | `tasks/phases/appendix-b-backlog.md` (168 lines) |

Dependency chain: 01 -> 02 -> 03 -> 04 -> 05 -> 06 -> 07 -> 08 -> 09 -> 10 -> 11 -> 12 -> 13 -> 14 -> 15 -> 16. Appendix B gated separately (Contracts closed + real requirement + ADR).

Current stop: Phase 5 code complete (delegation, groups, policy, 46 green) → checkpoint E2E + sign-off pending. Phase 6 list endpoint live early (`GET /api/courses`). Appendix B [HOLD], non-actionable.
