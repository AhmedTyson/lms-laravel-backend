# Assignments Routes — `/api` (planned, Phase 8)

Spec §5.9/5.10. RULE-015 (attempt uniqueness), RULE-016 (no grade-of-record).

## POST `/courses/{id}/assignments` — create (instructor)

Request: `{ "title": "Middleware HW", "description": "File or text or both…", "due_date": "2026-10-01T23:59:00Z", "resubmission_allowed": false, "max_score": "100.00", "passing_threshold": "60.00" }`.

Rules: `due_date` required + future at creation (app-level, not DB); `max_score` decimal required (any ceiling, not 0–100); `passing_threshold` = percent of `max_score`.

Response `201`: `{ "message": "Assignment created.", "data": { "id": 1, "course_id": 1, "title": "...", "description": "...", "due_date": "...", "resubmission_allowed": false, "max_score": "100.00", "passing_threshold": "60.00", "created_at": "..." } }`.

## GET `/courses/{id}/assignments` — list

Paginated, ordered by `due_date`.

## PATCH `/assignments/{id}` — edit (instructor)

Any mutable field; `due_date` stays future-validated.

## DELETE `/assignments/{id}` — delete (instructor)

Purges submissions. Response `200`: `{ "message": "Assignment deleted." }`.

## POST `/assignments/{id}/submissions` — submit (student)

Request: `{ "enrollment_id": 3, "file_path": "uploads/hw1.pdf", "content": "…" }` (`file_path`/`content` nullable — either, both, or neither per instructor instructions).

Server assigns `attempt_number = max+1` (unique triple enforced), `submitted_at: now`, `is_late: submitted_at > due_date`, `status: submitted`.

Response `201`: `{ "message": "Submission recorded.", "data": { "id": 5, "assignment_id": 1, "enrollment_id": 3, "attempt_number": 2, "file_path": "…", "content": null, "submitted_at": "...", "is_late": false, "grade": null, "status": "submitted" } }`.

Errors: second attempt with `resubmission_allowed: false` → `422` + `RESUBMIT_FORBIDDEN`.

## GET `/assignments/{id}/submissions` — list (instructor)

Paginated, filter `?enrollment_id=`, `?status=`.

## PATCH `/submissions/{id}/grade` — grade attempt (instructor)

Request: `{ "grade": "85.50" }` (numeric, 0 ≤ grade ≤ assignment `max_score`).

Sets `grade`, `graded_by` (caller), `graded_at`, `status: graded`. RULE-016: every attempt graded independently, all visible — no single grade-of-record column anywhere.

Response `200`: `{ "message": "Submission graded.", "data": { "<SubmissionResource>" } }`.
