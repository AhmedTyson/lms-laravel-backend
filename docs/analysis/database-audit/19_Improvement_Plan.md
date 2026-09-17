# 19 — Performance-Aware Improvement Plan

## Recommended Actions

### Category A: Must Fix (Completed in Phase 3 Baseline)
1. **Restricted Foreign Keys (ADR-011):** Applied `RESTRICT` on `courses.instructor_id` and `permission_grants.granter/grantee_id` to prevent hard-deletion of active authors.
2. **Read Model Sync (ADR-012):** Implemented `user_permissions` table and `PermissionReadModelSync` to eliminate expensive recursive ledger scans.
3. **Prune Indexing (ADR-014):** Added B-Tree index on `activity_log.created_at` to prevent full table scans during nightly audit log cleanup.

### Category B: Should Fix (Consider for Future Schema Optimizations)
1. **Remove Duplicate Single-Column Indexes:**
   * Drop single-column index `courses_instructor_id_index` (covered by `courses_instructor_id_title_unique`).
   * Drop single-column index `lessons_course_id_index` (covered by `lessons_course_id_order_unique`).
   * Drop single-column index `enrollments_student_id_index` (covered by `enrollments_student_id_course_id_unique`).
   * *Trade-off:* Saves minor write I/O overhead on inserts; negligible impact on modern NVMe drives.

### Category C: Consider (Optional Workload-Dependent Enhancements)
1. **DB-Level CHECK Constraints for Production MySQL:** Add DB-level `CHECK (passing_threshold BETWEEN 0 AND 100)` constraints if non-Laravel direct DB access tools (e.g. BI connectors) write to the schema.
