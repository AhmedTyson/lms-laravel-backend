# Courses Routes — `/api` (planned, Phase 6)

Spec §5.6/5.7. Auth: all `auth:api`. Instructor-owned writes; students read published only.

## POST `/courses` — create draft

Request: `{ "title": "Laravel Basics", "category": "Backend", "description": "..." }` (title required max 255, unique per instructor; category required; description nullable).

Response `201`: `{ "message": "Course created.", "data": { "id": 1, "instructor_id": 4, "title": "Laravel Basics", "category": "Backend", "description": "...", "status": "draft", "published_at": null, "archived_at": null, "created_at": "..." } }`.

Errors: `422` validation (duplicate title → unique violation).

## GET `/courses` — list

Query: `?status=published&category=Backend&instructor_id=4&page=1`. Paginated `data` envelope.

## GET `/courses/{id}` — show

Response `200`: `{ "data": { "<CourseResource>" } }`. `404` if missing.

## PATCH `/courses/{id}` — edit (owner)

Body: any of `title, category, description`. Published-course structural edits need elevated permission (RULE-001) else `403` + `FORBIDDEN`.

## DELETE `/courses/{id}` — delete draft

Draft only; published/archived → `422` + `INVALID_LIFECYCLE`. Response `200`: `{ "message": "Course deleted." }`.

## POST `/courses/{id}/publish` — publish

Sets `status: published`, `published_at: now`. RULE-013 linear lifecycle (draft→published→archived, no skips/backwards) else `422` + `INVALID_LIFECYCLE`.

## POST `/courses/{id}/archive` — archive

Sets `status: archived`, `archived_at: now`. Active enrollments present → `422` + `ACTIVE_ENROLLMENTS`.

## POST `/courses/{id}/lessons` — add lesson

Request: `{ "title": "Routing", "content_reference": "s3://.../video.mp4", "order": 1 }` (`order` required uint, unique per course — RULE-014).

Response `201`: `{ "message": "Lesson created.", "data": { "id": 7, "course_id": 1, "title": "Routing", "content_reference": "...", "order": 1, "created_at": "..." } }`.

## GET `/courses/{id}/lessons` — ordered list

Ordered by `order` asc, paginated.

## PATCH `/lessons/{id}` — edit (reorder via `order`)

Unique `(course_id, order)` enforced → collision `422`. Published-course structural edits need elevated permission (RULE-001).

## DELETE `/lessons/{id}` — delete

Purges its completions (cascade). Response `200`: `{ "message": "Lesson deleted." }`.
