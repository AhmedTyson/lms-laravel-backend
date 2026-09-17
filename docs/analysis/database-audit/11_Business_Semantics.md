# 11 — Table Business Semantics

## Core Domain Modeling Summary

### Identity Domain (`users`)
* **Entity:** Individual human actor.
* **Row Semantics:** One specific person registered in the LMS system.
* **Business Operation:** Authentication, authorization baseline, hierarchy position.
* **Data Classification:** Master Data.

### Access Control Domain (`groups`, `group_members`, `permission_grants`, `user_permissions`)
* **`groups`:** Scope boundary / room for group-level permissions.
* **`group_members`:** Membership registry (grants zero rights by itself per `RULE-009`).
* **`permission_grants`:** Immutable court record of delegated power.
* **`user_permissions`:** Cache of current effective capabilities.
* **Data Classification:** Master / Audit Ledger / Read Model.

### Teaching & Content Domain (`courses`, `lessons`, `assignments`, `quizzes`, `questions`)
* **`courses`:** Educational product catalog with linear lifecycle (`draft` → `published` → `archived`).
* **`lessons`:** Structured sequential content units within a course.
* **`assignments`:** Subjective tasks with free-form scoring thresholds.
* **`quizzes` & `questions`:** Objective assessments with 5 auto-scorable item types.
* **Data Classification:** Master Data / Content Definitions.

### Execution Domain (`enrollments`, `submissions`, `attempts`, `attempt_answers`, `progress_records`, `component_completions`)
* **`enrollments`:** Active contract binding a student to a course seat.
* **`submissions` & `attempts`:** Historical student performance artifacts.
* **`progress_records` & `component_completions`:** Earned achievements and dashboard tracking metrics.
* **Data Classification:** Transactional Data.
