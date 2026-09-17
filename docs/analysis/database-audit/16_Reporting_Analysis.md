# 16 — Reporting & Analytical Capability Analysis

## Domain Reporting Capability Summary

### 1. Educational & Progress Reporting
* **Can Answer Directly:**
  * Student course completion rates (`progress_records.percent_complete = 100`).
  * Per-component pass rates (joining `component_completions` across courses).
  * Average attempt count before passing a quiz or assignment.
  * Late submission frequency per course (`submissions.is_late = true`).

### 2. Access Control & Authority Audit Reporting
* **Can Answer Directly:**
  * Active permissions per user across global and group scopes (`user_permissions`).
  * Full delegation audit trail (who granted what to whom via `permission_grants`).
  * Group membership rosters (`group_members`).

### 3. Operational & System Metrics
* **Can Answer Directly:**
  * Daily active quiz attempts (`attempts` filtered by `started_at`).
  * Unread notification backlogs per user (`notifications` where `read_at IS NULL`).
  * System event frequency (`activity_log` grouped by `event`).

### 4. Analytical Limitations (What cannot be answered)
* **Weighted Multi-Factor GPA:** BRD/PRD specifies threshold-based completion, not weighted grade point averages across modules.
* **Student Time-on-Task:** No table records exact seconds spent reading a lesson (`lessons.content_reference` is an external link).
