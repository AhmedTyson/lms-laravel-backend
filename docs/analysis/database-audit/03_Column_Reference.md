# 03 — Column-by-Column Master Reference

| Table | Column | Type | Nullable | Default | Meaning | Role | Relationship | Data Quality Concern | Performance Concern | Usefulness /10 | Reason |
|---|---|---|---|---|---|---|---|---|---|---:|---|
| `users` | `id` | bigint | NO | AUTO_INCREMENT | Unique user identifier | identifier | PK | None | Optimal PK cluster | 10 | Essential surrogate key |
| `users` | `name` | varchar(255) | NO | None | User's full display name | text | None | None | None | 8 | Required for UI display |
| `users` | `email` | varchar(255) | NO | None | Unique login email address | identifier | Unique Index | Case sensitivity on MySQL vs SQLite | Looked up on authentication | 10 | Primary identity & login key |
| `users` | `email_verified_at` | timestamp | YES | NULL | Timestamp of email confirmation | timestamp | None | Unverified users might proliferate | Filtered during authorization | 7 | Security verification state |
| `users` | `password` | varchar(255) | NO | None | Bcrypt/Argon2 hashed password | security | None | Plaintext injection risk if misused | None | 10 | Required for authentication |
| `users` | `remember_token` | varchar(100) | YES | NULL | Web session persistence token | security | None | Stale tokens | None | 5 | Framework requirement for web guard |
| `users` | `manager_id` | bigint | YES | NULL | Reporting manager user ID | foreign key | Self-FK `users.id` | Cycles possible without service validation | Self-join lookup overhead | 9 | Core hierarchy link (RULE-002) |
| `users` | `approval_status` | varchar(20) | YES | NULL | Instructor approval state | status | None | String enum without DB CHECK | Unindexed status filter | 8 | Instructor gating (RULE-010) |
| `users` | `manager_depth` | smallint unsigned | NO | 0 | Distance from root Admin | depth cache | None | Drift if write-service bypassed | Pre-computed, avoids recursion | 8 | Pre-computed hierarchy depth (ADR-013) |
| `users` | `created_at` | timestamp | NO | None | Account creation time | timestamp | None | None | None | 7 | Audit & sorting |
| `users` | `updated_at` | timestamp | NO | None | Account update time | timestamp | None | None | None | 7 | Audit & cache invalidation |
| `groups` | `id` | bigint | NO | AUTO_INCREMENT | Group/Team ID | identifier | PK | None | None | 10 | Primary key & Spatie team_id |
| `groups` | `owner_id` | bigint | NO | None | Group manager/owner ID | foreign key | FK `users.id` | Orphaned groups if owner deleted | Filtered on manager dashboards | 9 | Group ownership (RULE-012) |
| `groups` | `name` | varchar(255) | NO | None | Group title | text | None | Non-unique (RULE-011) | Search wildcard scan | 7 | Human readability |
| `permission_grants` | `granter_id` | bigint | NO | None | Actor issuing grant | foreign key | FK `users.id` | None | FK index lookup | 9 | Audit attribution |
| `permission_grants` | `grantee_id` | bigint | NO | None | Actor receiving grant | foreign key | FK `users.id` | None | FK index lookup | 9 | Target actor |
| `permission_grants` | `group_id` | bigint | YES | NULL | Group scope boundary | foreign key | FK `groups.id` | NULL means global | Compound index with grantee | 8 | Scope qualifier (RULE-007/008) |
| `permission_grants` | `action` | varchar(20) | NO | None | Grant or revoke state | status | None | String enum | Filtered on ledger rebuild | 9 | Ledger semantics (RULE-005) |
| `courses` | `instructor_id` | bigint | NO | None | Owning instructor | foreign key | FK `users.id` | Restricted deletion (ADR-011) | Indexed for course listing | 10 | Ownership link |
| `courses` | `title` | varchar(255) | NO | None | Course title | text | None | Unique per instructor | Compound UQ index | 9 | Primary course identifier |
| `courses` | `status` | varchar(20) | NO | draft | Lifecycle state | status | None | Linear transitions app-enforced | Filtered on student catalog | 9 | Access control state (RULE-013) |
| `lessons` | `order` | int unsigned | NO | None | Sequence position | ordering | None | Gaps when deleted | UQ index `(course_id, order)` | 8 | Lesson sequencing (RULE-014) |
| `enrollments` | `student_id` | bigint | NO | None | Enrolled student ID | foreign key | FK `users.id` | Concurrent duplicate attempts | UQ index `(student_id, course_id)` | 10 | Core enrollment pair |
| `assignments` | `passing_threshold` | decimal(5,2) | NO | None | Pass percentage | threshold | None | Stored as %, not points | Read during grading | 9 | Completion math (RULE-018) |
| `submissions` | `attempt_number` | int unsigned | NO | None | Sequential attempt count | ordering | None | Gaps if attempt fails | UQ index on assignment+enrollment | 9 | Attempt sequence (RULE-015) |
| `submissions` | `is_late` | boolean | NO | false | Overdue flag | status | None | App-calculated against due_date | Filtered in gradebook | 8 | Timeliness marker |
| `quizzes` | `passing_threshold` | decimal(5,2) | NO | None | Required pass % | threshold | None | Dynamic total SUM(points) | Read on quiz submit | 9 | Dynamic quiz pass evaluation |
| `questions` | `points` | decimal(6,2) | NO | 1.00 | Question score weight | weight | None | Variable points change total | SUM() queries during scoring | 9 | Mixed-scoring weight |
| `component_completions` | `component_type` | varchar(30) | NO | None | Polymorphic type | discriminator | Morph `lessons|assignments|quizzes` | No DB FK on component_id | Compound index target | 9 | Morph discriminator (ADR-010) |
| `component_completions` | `is_override` | boolean | NO | false | Manual pass flag | override | None | Must log `overridden_by` | Filtered in completion reporting | 8 | Instructor override (RULE-019) |
