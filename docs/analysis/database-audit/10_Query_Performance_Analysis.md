# 10 — Query & Performance Analysis

## Critical Query Patterns & Performance Evaluation

### 1. Authorization Evaluation Query (O(1) vs CTE)
* **Access Pattern:** Checking if User X has `edit-course` permission in Group Y.
* **Naive Query (without read model):** Requires scanning `permission_grants` for all active grants, checking granter subordinate trees up to root Admin.
* **Optimized Execution (ADR-012):**
  ```sql
  SELECT 1 FROM user_permissions 
  WHERE user_id = ? AND permission_name = ? AND (group_id = ? OR group_id IS NULL) 
  LIMIT 1;
  ```
* **Performance Impact:** Index `(user_id, permission_name, group_id)` converts permission checks into a single-digit microsecond point lookup.

### 2. Activity Log Nightly Prune Query (ADR-014)
* **Access Pattern:** Deleting audit log entries older than N days (`PruneActivityLog` command).
* **Execution:**
  ```sql
  DELETE FROM activity_log WHERE created_at < ?;
  ```
* **Performance Impact:** Index on `created_at` enables range scanning. Without `activity_log_created_at_index`, nightly prunes would execute full table scans locking large chunks of `activity_log`.

### 3. Student Progress Dashboard Query
* **Access Pattern:** Rendering overall percentage completion for an enrolled student across all components.
* **Execution:** Reads directly from pre-computed `progress_records` row:
  ```sql
  SELECT percent_complete FROM progress_records WHERE enrollment_id = ?;
  ```
* **Performance Impact:** Instant response. Component threshold math is evaluated on submission write, keeping dashboard read latency minimal.

### 4. Concurrent Enrollment Prevention (Race Condition)
* **Access Pattern:** Two simultaneous HTTP POST requests to `/api/courses/10/enroll`.
* **Database Guard:** Unique index `(student_id, course_id)` forces the second transaction to abort with a SQL state `23000` constraint violation, preventing duplicate seat creation at the DB boundary.
