# ADR-0007: Explicit team sentinel and unified read-model authorization

- **Decision:** Global Spatie roles and permissions (`config/permission.php` `teams => true`) use team sentinel `0`, scoped explicitly via a restore-on-exit helper (never ambient request-wide `setPermissionsTeamId()`). `PermissionReadModelSync` mirrors `permission_grants` into `user_permissions`, and authorization checks (`Gate::before` / explicit team checks) consult both `user_permissions` (global `group_id IS NULL` or explicit `group_id`) and team-0 Spatie roles.
- **Reason:** Prevents `NOT NULL` `team_id` SQL crashes during role assignment (`FIND-01`), eliminates ambient team-context bleed across requests (`ADR-008`), and closes the split-brain where delegated grants in `user_permissions` were ignored by `Gate`/`can()` (`FIND-04`).
- **Alternatives:** Disabling Spatie `teams` — rejected: violates `AGENTS.md` and `config/permission.php` baseline invariant. Synchronizing every grant solely into `model_has_permissions` without `user_permissions` — rejected: breaks `ADR-012` read-model contract and `RefinementTest`.
- **Impact:** `RegistrationService`, `GoogleAuthService`, `DatabaseSeeder`, `AccessManagementServiceProvider`, and `DelegationService` evaluate and assign roles/permissions with explicit team/group scope.
- **Status:** Accepted.
