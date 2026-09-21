# Reporting Routes — `/api` (planned, Phase 12)

Spec 6.8, ADR-008: every query carries explicit team/group context — no ambient reads. Instructor/admin scoped; students see own rows only.

## GET `/reports/enrollments` — enrollment stats

Query: `?course_id=1&status=active`. Explicit `group_id` when team-scoped.

Response `200`: `{ "data": { "total": 120, "active": 98, "completed": 22, "by_course": [{ "course_id": 1, "active": 40, "completed": 9 }] } }`.

## GET `/reports/completions` — completion rates

Query: `?course_id=1`. Threshold math per component totals (same engine as Progress, read-only here).

Response `200`: `{ "data": { "course_id": 1, "average_percent": "61.20", "completed_count": 22, "overridden_count": 3 } }`.

## GET `/reports/courses/{id}` — course rollup

One course: enrollments, completions, assignment averages, quiz pass rates.

Response `200`: `{ "data": { "course_id": 1, "enrollments": 120, "completion_rate": "18.30", "assignments": [{ "id": 2, "average_grade": "77.40", "graded_count": 95 }], "quizzes": [{ "id": 1, "average_score": "6.10", "pass_rate": "72.00" }] } }`.

Errors: `403` outside caller's team scope, `404` unknown course.
