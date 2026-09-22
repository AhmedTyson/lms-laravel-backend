# Mock-Data Plan — factories + seeders per phase

Rule: every model ships a factory; every phase ships a seeder. Factories resolve via `Factory::guessFactoryNamesUsing` in `AppServiceProvider` — no per-model wiring.

| Phase | Status | Factories | Seeder demo graph |
|---|---|---|---|
| 1–2 Env/skeleton | closed | — (no models) | admin only |
| 3 Database | closed | all 19 models | — (schema only) |
| 4 Auth | closed | `User` (root) | pending instructor + verified student |
| 5 AccessManagement | closed | `Group`, `GroupMember`, `PermissionGrant`, `UserPermission` | cohort group + 3 members |
| 6 Courses | **open** (code) / closed (mock data) | `Course`, `Lesson` | published course + 3 lessons, 1 draft |
| 7 Enrollment | planned | `Enrollment` | student enrolled in published course |
| 8 Assignments | planned | `Assignment`, `Submission` | assignment + 1 submission |
| 9 Quizzes | planned | `Quiz`, `Question`, `QuestionOption`, `QuestionAcceptedAnswer`, `Attempt`, `AttemptAnswer` | quiz + choice/TF questions + attempt |
| 10 Progress | planned | `ProgressRecord`, `ComponentCompletion` | record + 1 completion |
| 11 Notifications | planned | `Notification` | 1 inbox row |
| 12 Reporting | planned | — (reads aggregates) | none |

Seed order = FK order (admin → auth → access → courses → enrollment → …). Run: `php artisan migrate:fresh --seed`. Test guard: `tests/Feature/FactorySmokeTest.php` (all 19 resolve + insert).
