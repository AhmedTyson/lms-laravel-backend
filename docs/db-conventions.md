# DB Conventions (for developers authoring the Phase 9 schema)

Spec §5 is the schema source of truth (19 tables). These rules keep migrations portable across SQLite (dev) and MySQL (prod).

1. **IDs**: auto-increment integers everywhere (ARCH-005). No UUIDs, no ULIDs.
2. **No engine-specific SQL**: no raw `DB::statement`, no MySQL-only index types, no `tztz` backport. `timestamp()` columns, nullable where spec says so.
3. **Enums**: native PHP backed enums in code; string columns in DB. No enum package.
4. **Index/unique naming**: Laravel defaults (`table_column_unique`). Required uniques: `users.email`, `enrollments(student_id,course_id)`, `lessons(course_id,order)`, `courses(instructor_id,title)`, `submissions(assignment_id,enrollment_id,attempt_number)`, `attempts(quiz_id,enrollment_id,attempt_number)`, `attempt_answers(attempt_id,question_id)`, `component_completions(enrollment_id,component_type,component_id)`, `group_members(group_id,user_id)`.
5. **`permission_grants` is append-only**: no `updated_at`, never update/delete — revokes are new rows (`action=revoked`).
6. **`activity_log` is package-owned**: published from `spatie/laravel-activitylog`, never hand-authored or edited.
7. **App-level (not DB) rules**: `due_date` future-dated, `manager_id` acyclicity (service + transaction, ADR-007 in spec), published-content guards (policies). MySQL can't enforce these — don't try with CHECK constraints.
8. **Where migrations live**: module tables in `Modules/<Name>/database/migrations/`; shared/auth tables in root `database/migrations/`.
9. **Seed order**: root Admin first (`DatabaseSeeder`), module seeders after. Credentials from env only.
