# 15 — Historical Data & Auditability Analysis

## Capability Matrix

| Audit Question | Can Schema Answer? | Primary Source Table | Notes & Limitations |
|---|---|---|---|
| Who created a record? | YES | `created_at`, `users.id`, `activity_log` | Standard Eloquent timestamps and causer morphs |
| When was a status changed? | PARTIALLY | `courses.published_at`, `archived_at` | Explicit timestamps on `courses`; status changes for submissions only keep latest `updated_at` |
| What power was granted/revoked? | YES | `permission_grants` | Append-only ledger stores complete granter, grantee, action, and timestamp history (RULE-005) |
| What was the previous grade value? | YES | `submissions` | All attempts kept (`attempt_number`). Grades are never overwritten |
| Who performed system actions? | YES | `activity_log` | Package-owned CCTV log (`spatie/laravel-activitylog`, ADR-006) |
| Who manual-overrode a completion? | YES | `component_completions` | Stores `is_override = true` and `overridden_by = user_id` (RULE-019) |
| Historical manager chain changes? | NO | None | `users.manager_id` updates in place. Past manager links are overwritten |

## Key Audit Mechanisms
1. **Permission Ledger (`permission_grants`):** Immutable append-only record. Revoking a grant writes a new row with `action = 'revoked'`, preserving the original grant row.
2. **Submission & Quiz History:** Attempts auto-increment (`attempt_number`). Historic attempts are preserved rather than updated in place, enabling historical performance analysis.
