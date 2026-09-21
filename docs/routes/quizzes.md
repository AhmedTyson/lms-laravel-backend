# Quizzes Routes — `/api` (planned, Phase 9)

Spec §5.11–5.16. SCOPE-005 (5 question types), RULE-017 (accepted answers), dynamic threshold (never cached).

## POST `/courses/{id}/quizzes` — create (instructor)

Request: `{ "title": "Week 3 Quiz", "opens_at": "...", "closes_at": "...", "max_attempts": 3, "passing_threshold": "70.00" }` (`closes_at` after `opens_at`; threshold = percent of live `SUM(questions.points)`).

Response `201`: `{ "message": "Quiz created.", "data": { "id": 1, "course_id": 1, "title": "...", "opens_at": "...", "closes_at": "...", "max_attempts": 3, "passing_threshold": "70.00", "created_at": "..." } }`.

## GET `/courses/{id}/quizzes` — list. PATCH/DELETE `/quizzes/{id}` — edit/delete (delete purges attempts + answers).

## POST `/quizzes/{id}/questions` — add question (instructor)

Request by type:

- `single_choice|multiple_choice`: `{ "prompt": "…", "type": "single_choice", "order": 1, "points": "2.00", "options": [{ "label": "A", "is_correct": true, "order": 1 }] }`
- `true_false`: `{ "prompt": "…", "type": "true_false", "order": 2, "points": "1.00", "correct_boolean": true }`
- `numeric`: `{ "prompt": "…", "type": "numeric", "order": 3, "points": "1.50", "correct_number": "3.1416", "numeric_tolerance": "0.001" }`
- `short_answer`: `{ "prompt": "…", "type": "short_answer", "order": 4, "points": "2.00", "accepted_answers": ["eloquent", "Eloquent ORM"] }` (RULE-017)

Response `201`: `{ "message": "Question created.", "data": { "<QuestionResource with options/answers>" } }`.

## GET `/quizzes/{id}/questions` — list ordered. PATCH/DELETE `/questions/{id}` — edit/delete.

## POST `/quizzes/{id}/attempts` — start (student)

Guards: inside window (`opens_at ≤ now ≤ closes_at`) else `422` + `QUIZ_CLOSED`; used attempts < `max_attempts` else `422` + `ATTEMPTS_EXHAUSTED`. Server assigns `attempt_number`, `started_at`.

Response `201`: `{ "message": "Attempt started.", "data": { "id": 1, "quiz_id": 1, "enrollment_id": 3, "attempt_number": 1, "score": null, "started_at": "...", "submitted_at": null } }`.

## POST `/attempts/{id}/answers` — answer one question (student)

Request (exactly one answer field by question type): `{ "question_id": 4, "selected_option_ids": [11], "answer_boolean": null, "answer_text": null, "answer_number": null }`.

Server evaluates `is_correct` immediately (options/boolean/number-tolerance/accepted-list). Unique `(attempt_id, question_id)` — re-answer overwrites or 409 per phase decision.

Response `201`: `{ "message": "Answer recorded.", "data": { "id": 2, "attempt_id": 1, "question_id": 4, "selected_option_ids": [11], "is_correct": true } }`.

## POST `/attempts/{id}/submit` — submit (student)

Sums awarded points vs live `SUM(questions.points)` → `score`, `passed = score ≥ passing_threshold`, sets `submitted_at`.

Response `200`: `{ "message": "Attempt submitted.", "data": { "id": 1, "score": "6.50", "passed": true, "submitted_at": "..." } }`.
