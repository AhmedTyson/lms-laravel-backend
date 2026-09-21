# Enrollment Routes — `/api` (planned, Phase 7)

Spec §5.8. Student-self enrollment; instructors view their course rosters.

## POST `/enrollments` — enroll

Request: `{ "course_id": 1 }` (required, exists courses; course must be `published`, else `422` + `COURSE_NOT_OPEN`).

Student = caller (`student_id` from token, never from body).

Response `201`: `{ "message": "Enrolled.", "data": { "id": 3, "student_id": 9, "course_id": 1, "status": "active", "enrolled_at": "...", "completed_at": null } }`.

Errors: duplicate pair → `409` + `ALREADY_ENROLLED` (unique student+course closes concurrent race; use `firstOrCreate`-style guard).

## GET `/me/enrollments` — my enrollments

Paginated, `?status=active|completed`. Response `200` data envelope.

## GET `/courses/{id}/enrollments` — roster (instructor/owner)

Paginated enrollments with student resource embedded.

## DELETE `/enrollments/{id}` — withdraw

Own enrollment only (or instructor of course) else `403`. Row deleted or status-flipped per phase decision; completions purge with it.

Response `200`: `{ "message": "Enrollment withdrawn." }`.
