# Progress Routes — `/api` (planned, Phase 10)

Spec §5.17/5.18. RULE-018 revised (percentage-threshold completion), RULE-019 (instructor override). Completions resolve via morph (`lesson|assignment|quiz`), never type-switch (ADR-010).

## GET `/enrollments/{id}/progress` — progress detail

Own enrollment (or course instructor), else `403`.

Response `200`:

```json
{
  "data": {
    "enrollment_id": 3,
    "percent_complete": "62.50",
    "completed_at": null,
    "completions": [
      { "component_type": "lesson", "component_id": 7, "completed_at": "...", "is_override": false, "overridden_by": null }
    ]
  }
}
```

## GET `/me/progress` — all my progress

Paginated `progress_records` with enrollment + course embedded.

## POST `/completions` — instructor override (RULE-019)

Request: `{ "enrollment_id": 3, "component_type": "quiz", "component_id": 4 }` (`component_type` in `lesson|assignment|quiz`).

Server sets `is_override: true`, `overridden_by` = caller, `completed_at: now`, recomputes `percent_complete`. Unique triple enforced (override flips existing row, never duplicates).

Response `201`: `{ "message": "Completion overridden.", "data": { "<CompletionResource>" } }`.

Errors: `403` non-instructor, `422` unknown component.
