# Notifications Routes — `/api` (planned, Phase 11)

Spec §5.19. `type` maps to domain events (`InstructorApproved`, `GradePublished`, `EnrollmentConfirmed`…), `data` JSON payload. Owner-scoped always.

## GET `/notifications` — my inbox

Query: `?unread=1&page=1`. Ordered newest first (`index(user_id, read_at)`).

Response `200`: `{ "data": [{ "id": 1, "type": "GradePublished", "data": { "assignment_id": 2, "grade": "85.50" }, "read_at": null, "created_at": "..." }] }` (paginated).

## POST `/notifications/{id}/read` — mark read

Own notification only else `404` (never 403 — no existence leak). Idempotent: already-read returns same shape.

Response `200`: `{ "message": "Notification marked as read.", "data": { "<NotificationResource, read_at set>" } }`.

## DELETE `/notifications/{id}` — delete

Own only else `404`. Response `200`: `{ "message": "Notification deleted." }`.

## Sending (no endpoint)

Created server-side by listeners on domain events (`Registered`, `InstructorApproved`, grading, enrollment). Never client-writable — no POST `/notifications`.
