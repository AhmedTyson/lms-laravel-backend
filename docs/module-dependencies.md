# Module Dependencies (must stay acyclic)

```text
Auth → AccessManagement → Courses → Enrollment → {Assignments, Quizzes} → Progress
Notifications ↑ listens to domain events (no direct calls into modules)
Reporting     ↑ read-only aggregation over module data
```

Finer edges (spec §4.2): Courses → Auth, AccessManagement; Enrollment → Auth, Courses; Assignments/Quizzes → Courses, Enrollment; Progress → Enrollment, Courses, Assignments, Quizzes.

Rules: depend downward only; cross-module needs go through events (`CoursePublished` → queued Notification listener), never direct class coupling. Reporting never writes. CI reviewers reject new `use Modules\X` imports that point upward.
