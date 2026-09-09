# LMS Database Guide (spec v2.1)

Three views of the same schema. Keep them in sync when the schema changes:

| View | File | Renders in |
|---|---|---|
| Source of truth (DBML) | `docs/schema.dbml` | [dbdiagram.io](https://dbdiagram.io) (paste file contents) |
| GitHub-native diagram | This file (Mermaid below) | Any markdown renderer with Mermaid (GitHub) |
| Interactive explorer | `docs/erd.html` | Browser, offline, dark mode |

Conventions: auto-increment PKs everywhere (ARCH-005) · completion thresholds are percentages of variable per-component totals (RULE-018 rev) · Reporting owns no tables (read-only by design).

## ER diagram (crow's foot)

```mermaid
erDiagram
    users ||--o{ groups : owns
    users ||--o{ group_members : joins
    users ||--o{ permission_grants : "grants (granter)"
    users ||--o{ permission_grants : "receives (grantee)"
    groups ||--o{ group_members : contains
    groups ||--o{ permission_grants : scopes
    users ||--o{ courses : instructs
    courses ||--o{ lessons : contains
    users ||--o{ enrollments : "enrolls (student)"
    courses ||--o{ enrollments : fills
    courses ||--o{ assignments : assigns
    assignments ||--o{ submissions : receives
    enrollments ||--o{ submissions : submits
    users ||--o{ submissions : "grades (grader)"
    courses ||--o{ quizzes : quizzes
    quizzes ||--o{ questions : asks
    questions ||--o{ question_options : offers
    questions ||--o{ question_accepted_answers : accepts
    quizzes ||--o{ attempts : attempts
    enrollments ||--o{ attempts : sits
    attempts ||--o{ attempt_answers : answers
    questions ||--o{ attempt_answers : targets
    enrollments ||--|| progress_records : tracks
    enrollments ||--o{ component_completions : completes
    users ||--o{ component_completions : "overrides (overrider)"
    users ||--o{ notifications : notifies

    users {
        bigint id PK
        varchar name
        varchar email UK
        timestamp email_verified_at "NULL"
        varchar password
        bigint manager_id FK "NULL, self-ref, RULE-002"
        varchar approval_status "NULL, RULE-010"
    }
    groups {
        bigint id PK "also Spatie team_id"
        bigint owner_id FK
        varchar name "NOT unique, RULE-011"
    }
    group_members {
        bigint id PK
        bigint group_id FK
        bigint user_id FK
        timestamp joined_at
    }
    permission_grants {
        bigint id PK
        bigint granter_id FK
        bigint grantee_id FK
        bigint group_id FK "NULL = global"
        varchar permission_name
        varchar action "granted|revoked"
        timestamp granted_at "NULL"
        timestamp revoked_at "NULL"
    }
    courses {
        bigint id PK
        bigint instructor_id FK
        varchar title
        varchar category
        text description "NULL"
        varchar status "draft|published|archived"
        timestamp published_at "NULL"
        timestamp archived_at "NULL"
    }
    lessons {
        bigint id PK
        bigint course_id FK
        varchar title
        text content_reference "NULL, external pointer"
        int order "UQ per course, RULE-014"
    }
    enrollments {
        bigint id PK
        bigint student_id FK
        bigint course_id FK
        varchar status "active|completed"
        timestamp enrolled_at "NULL"
        timestamp completed_at "NULL"
    }
    assignments {
        bigint id PK
        bigint course_id FK
        varchar title
        text description "NULL, doubles as instructions"
        timestamp due_date "required"
        boolean resubmission_allowed
        decimal max_score "free-form ceiling"
        decimal passing_threshold "percent of max"
    }
    submissions {
        bigint id PK
        bigint assignment_id FK
        bigint enrollment_id FK
        int attempt_number
        varchar file_path "NULL"
        text content "NULL"
        timestamp submitted_at
        boolean is_late
        decimal grade "NULL"
        bigint graded_by FK "NULL"
        timestamp graded_at "NULL"
        varchar status "submitted|graded"
    }
    quizzes {
        bigint id PK
        bigint course_id FK
        varchar title
        timestamp opens_at
        timestamp closes_at
        int max_attempts
        decimal passing_threshold "percent of live sum"
    }
    questions {
        bigint id PK
        bigint quiz_id FK
        text prompt
        varchar type "5 auto-scorable types"
        int order
        decimal points "per-question weight"
        boolean correct_boolean "NULL"
        decimal correct_number "NULL"
        decimal numeric_tolerance "NULL"
    }
    question_options {
        bigint id PK
        bigint question_id FK
        varchar label
        boolean is_correct
        int order
    }
    question_accepted_answers {
        bigint id PK
        bigint question_id FK
        varchar answer_text
    }
    attempts {
        bigint id PK
        bigint quiz_id FK
        bigint enrollment_id FK
        int attempt_number
        decimal score "NULL"
        timestamp started_at
        timestamp submitted_at "NULL"
    }
    attempt_answers {
        bigint id PK
        bigint attempt_id FK
        bigint question_id FK
        json selected_option_ids "NULL"
        boolean answer_boolean "NULL"
        varchar answer_text "NULL"
        decimal answer_number "NULL"
        boolean is_correct
    }
    progress_records {
        bigint enrollment_id PK_FK "strict 1-to-1"
        decimal percent_complete
        timestamp completed_at "NULL"
    }
    component_completions {
        bigint id PK
        bigint enrollment_id FK
        varchar component_type "lesson|assignment|quiz"
        bigint component_id "morph target"
        timestamp completed_at "NULL"
        boolean is_override "RULE-019"
        bigint overridden_by FK "NULL"
    }
    notifications {
        bigint id PK
        bigint user_id FK
        varchar type "domain event"
        json data
        timestamp read_at "NULL"
    }
    activity_log {
        bigint id PK "package-owned, ADR-006"
        varchar log_name "NULL"
        text description
        varchar subject_type "NULL"
        bigint subject_id "NULL"
        varchar causer_type "NULL"
        bigint causer_id "NULL"
        varchar event "NULL"
        json properties "NULL"
        string batch_uuid "NULL"
    }
```

## Race-closing unique indexes (the three bouncers + friends)

| Index | Table | Race it kills |
|---|---|---|
| `(student_id, course_id)` | enrollments | Concurrent double-enroll |
| `(assignment_id, enrollment_id, attempt_number)` | submissions | Duplicate attempt when resubmission off (RULE-015) |
| `(quiz_id, enrollment_id, attempt_number)` | attempts | Duplicate quiz attempt |
| `(course_id, order)` | lessons | Duplicate lesson position (RULE-014) |
| `(instructor_id, title)` | courses | Duplicate course names per instructor |
| `(attempt_id, question_id)` | attempt_answers | Double answer per question |
| `(enrollment_id, component_type, component_id)` | component_completions | Double completion |
| `(group_id, user_id)` | group_members | Double membership |

## Why `users` is split across two migrations

**Framework identity vs. LMS domain.** The root migration (`0001_…_create_users_table`) is pristine Laravel — name, email, password. Every Laravel app has it. The Auth-module migration (`120000_add_manager_fields_to_users_table`) adds what makes a user an LMS citizen: a place in the authority tree (`manager_id`, RULE-002) and the instructor approval gate (`approval_status`, RULE-010).

Three reasons: **1)** root stays stock Laravel — upgrades, packages (Filament reads the base table), and new developers see familiar ground; **2)** domain evolution lives with its owner — hierarchy changes diff in `Modules/Auth`, not a framework file; **3)** migration order narrates boot order — base identity first, domain meaning layered on top. One combined migration would weld LMS concepts into framework scaffolding and make both harder to change.

## Table business stories

Short version of each table's purpose — full narratives in `docs/erd.html` (click any table):

- **users** — population registry: who you are, not what you may do. One `manager_id` forms the Admin-rooted tree; students stay outside it.
- **groups** — rooms, not ranks. Scopes for permissions; names may repeat; ownership passes on when owners leave.
- **group_members** — guest list only. Membership grants zero capabilities (RULE-009).
- **permission_grants** — court record of power. Append-only; subordinate-only + ceiling rule per row.
- **courses** — instructor's promise. Linear draft→published→archived; published content is a contract.
- **lessons** — numbered chapters. Progress points at IDs, so reordering never corrupts.
- **enrollments** — one seat per student per course; everything hangs off this row.
- **assignments** — free-form scoring (any max) with percentage thresholds; prose instructions.
- **submissions** — every attempt kept forever, graded independently; no grade-of-record.
- **quizzes** — timed exam room; threshold recomputed from live question points.
- **questions** — weighted scorable moments, five auto-gradable types.
- **question_options / question_accepted_answers** — pre-marked choices; accepted phrasing variants.
- **attempts / attempt_answers** — sittings and per-question verdicts, enabling review and appeals.
- **progress_records** — the dashboard percentage, one row per seat.
- **component_completions** — gold stars via polymorphic relation, earned by threshold or attributed override.
- **notifications** — queued mailbox, read/unread tracked, users see only theirs.
- **activity_log** — CCTV trail for everything; grants stay the court record for power.
