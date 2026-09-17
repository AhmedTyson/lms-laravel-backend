# 13 — Column Value & Quality Scoring

## Scoring Distribution & Criteria
* **10 (Critical):** Core PKs, unique identity markers (`users.email`, `courses.title`).
* **8–9 (High Value):** FKs, status indicators, thresholds, scoring weights.
* **6–7 (Moderate Value):** Optional descriptions, timestamps, UI ordering fields.
* **4–5 (Limited/Technical):** Temporary tokens (`remember_token`).

## Representative Column Scores

| Table | Column | Score /10 | Technical & Operational Justification |
|---|---|---:|---|
| `users` | `id` | 10 | Primary surrogate key, used across 25+ foreign keys. |
| `users` | `email` | 10 | Unique authentication key, indexed. |
| `users` | `manager_id` | 9 | Crucial for reporting tree (RULE-002), self-referencing FK. |
| `users` | `manager_depth` | 8 | Denormalized cache avoiding recursive CTEs (ADR-013). |
| `users` | `remember_token` | 5 | Web session persistence; unused by pure JWT API clients. |
| `permission_grants` | `action` | 9 | Key append-only ledger action marker (`granted` vs `revoked`). |
| `permission_grants` | `group_id` | 8 | Nullable scope delimiter (NULL = global grant). |
| `courses` | `status` | 9 | Enforces strict linear lifecycle (`draft` → `published` → `archived`). |
| `submissions` | `attempt_number` | 9 | Enforces multi-attempt tracking without overwriting historical grades. |
| `submissions` | `is_late` | 8 | Pre-calculated flag at submission time, resilient to due date edits. |
| `quizzes` | `passing_threshold` | 9 | Dynamic evaluation threshold stored as percentage. |
| `questions` | `points` | 9 | Variable weighting per question for mixed-score assessments. |
| `component_completions` | `component_type` | 9 | Discriminator for polymorphic relation (ADR-010). |
| `component_completions` | `is_override` | 8 | Marks instructor intervention for progress overrides (RULE-019). |
