# 18 — Schema Risks & Anomalies

## Risk Matrix

| Risk ID | Risk / Anomaly Description | Severity | Impact | Mitigation Status |
|---|---|---|---|---|
| RISK-01 | Polymorphic target in `component_completions` lacks DB-level FK | Medium | Deleting a lesson/quiz outside ORM could leave orphan completions | Mitigated via application `ComponentDeletionObserver` |
| RISK-02 | Unconstrained String Enums (`courses.status`, `submissions.status`) | Low | Direct SQL insert could inject invalid string states | Mitigated at App level via Laravel FormRequests & Enums |
| RISK-03 | Missing Database CHECK constraint for negative scores/thresholds | Low | Database accepts negative `max_score` or `passing_threshold` values | Mitigated at App validation layer |
| RISK-04 | User hard-deletion breaking course/grant relationships | High | Hard-deleting user breaks course authorship or grant audit trails | Mitigated via explicit RESTRICT FKs (ADR-011) |
| RISK-05 | Manager tree cycle insertion | High | Cyclic `manager_id` references break reporting tree traversals | Mitigated via service transaction locking (ADR-007) |
| RISK-06 | Read model (`user_permissions`) drifting from ledger (`permission_grants`) | High | Authorization state drift | Mitigated by executing sync in single atomic transaction (ADR-012) |
