# 02 — Complete Table Extraction

## Table: users

### Purpose
Central identity registry containing authentication credentials, approval status for instructors, and hierarchical reporting chain references (`manager_id`).

### Columns
| # | Column | Type | Nullable | Default | PK | FK | Unique | Indexed | Generated | Score /10 |
|---|---|---|---|---|---|---|---|---|---|---:|
| 1 | id | bigint | NO | None | YES | NO | YES | YES | NO | 10 |
| 2 | name | varchar(255) | NO | None | NO | NO | NO | NO | NO | 8 |
| 3 | email | varchar(255) | NO | None | NO | NO | YES | YES | NO | 10 |
| 4 | email_verified_at | timestamp | YES | NULL | NO | NO | NO | NO | NO | 7 |
| 5 | password | varchar(255) | NO | None | NO | NO | NO | NO | NO | 10 |
| 6 | remember_token | varchar(100) | YES | NULL | NO | NO | NO | NO | NO | 5 |
| 7 | manager_id | bigint | YES | NULL | NO | YES | NO | YES | NO | 9 |
| 8 | approval_status | varchar(20) | YES | NULL | NO | NO | NO | NO | NO | 8 |
| 9 | manager_depth | smallint unsigned | NO | 0 | NO | NO | NO | NO | NO | 8 |
| 10 | created_at | timestamp | NO | None | NO | NO | NO | NO | NO | 7 |
| 11 | updated_at | timestamp | NO | None | NO | NO | NO | NO | NO | 7 |

### Constraints
* `PRIMARY KEY (id)`
* `UNIQUE (email)`
* `FOREIGN KEY (manager_id) REFERENCES users(id) ON DELETE SET NULL`

### Indexes
* `users_email_unique` (`email`)
* `users_manager_id_index` (`manager_id`)

### Relationships
* `users.manager_id` → `users.id` (1:N Self-referencing reporting tree)

### Unary Relationships
* `manager_id` references `users.id`: Represents manager-subordinate organizational hierarchy. Root admins have `manager_id = NULL`. Students have `manager_id = NULL`.

### What this table describes
Identity and organizational position of actors (Admins, Managers, Instructors, Students).

### Useful information obtainable
User authentication credentials, reporting hierarchy, instructor verification status, manager depth distance from root.

### Important information not represented
Role assignments (delegated to Spatie `model_has_roles` and domain `permission_grants`), historical manager changes, soft-delete timestamp.

### Performance notes
`manager_id` is indexed for tree traversal; `manager_depth` provides O(1) depth checks without recursive CTEs.

---

## Table: groups

### Purpose
Represents organizational boundaries/teams used as authorization scopes for permission grants.

### Columns
| # | Column | Type | Nullable | Default | PK | FK | Unique | Indexed | Generated | Score /10 |
|---|---|---|---|---|---|---|---|---|---|---:|
| 1 | id | bigint | NO | None | YES | NO | YES | YES | NO | 10 |
| 2 | owner_id | bigint | NO | None | NO | YES | NO | YES | NO | 9 |
| 3 | name | varchar(255) | NO | None | NO | NO | NO | NO | NO | 7 |
| 4 | created_at | timestamp | NO | None | NO | NO | NO | NO | NO | 6 |
| 5 | updated_at | timestamp | NO | None | NO | NO | NO | NO | NO | 6 |

### Constraints
* `PRIMARY KEY (id)`
* `FOREIGN KEY (owner_id) REFERENCES users(id)`

### Indexes
* `groups_owner_id_index` (`owner_id`)

### Relationships
* `groups.owner_id` → `users.id` (N:1 Group owned by User)

### Unary Relationships
* None.

### What this table describes
Security teams/scopes for authorization.

### Useful information obtainable
Group ownership and scope identification.

### Important information not represented
Group descriptions, parent/child group hierarchies.

### Performance notes
`owner_id` index supports fast lookup of groups owned by a specific manager/admin.

---

## Table: permission_grants

### Purpose
Authoritative append-only audit ledger of granted and revoked permissions across global and group scopes.

### Columns
| # | Column | Type | Nullable | Default | PK | FK | Unique | Indexed | Generated | Score /10 |
|---|---|---|---|---|---|---|---|---|---|---:|
| 1 | id | bigint | NO | None | YES | NO | YES | YES | NO | 10 |
| 2 | granter_id | bigint | NO | None | NO | YES | NO | YES | NO | 9 |
| 3 | grantee_id | bigint | NO | None | NO | YES | NO | YES | NO | 9 |
| 4 | group_id | bigint | YES | NULL | NO | YES | NO | YES | NO | 8 |
| 5 | permission_name | varchar(255) | NO | None | NO | NO | NO | NO | NO | 9 |
| 6 | action | varchar(20) | NO | None | NO | NO | NO | NO | NO | 9 |
| 7 | granted_at | timestamp | YES | NULL | NO | NO | NO | NO | NO | 7 |
| 8 | revoked_at | timestamp | YES | NULL | NO | NO | NO | NO | NO | 7 |
| 9 | created_at | timestamp | NO | CURRENT_TIMESTAMP | NO | NO | NO | NO | NO | 8 |

### Constraints
* `PRIMARY KEY (id)`
* `FOREIGN KEY (granter_id) REFERENCES users(id) ON DELETE RESTRICT`
* `FOREIGN KEY (grantee_id) REFERENCES users(id) ON DELETE RESTRICT`
* `FOREIGN KEY (group_id) REFERENCES groups(id) ON DELETE SET NULL`

### Indexes
* `permission_grants_grantee_id_index` (`grantee_id`)
* `permission_grants_granter_id_grantee_id_index` (`granter_id`, `grantee_id`)
* `permission_grants_group_id_index` (`group_id`)

### Relationships
* `permission_grants.granter_id` → `users.id` (N:1)
* `permission_grants.grantee_id` → `users.id` (N:1)
* `permission_grants.group_id` → `groups.id` (N:1 Optional)

### What this table describes
Immutable legal record of authority delegation.

### Useful information obtainable
Who granted what permission to whom, in which group context, and when it was granted or revoked.

### Important information not represented
Does not store current state efficiently for fast querying (handled by `user_permissions` read model).

### Performance notes
Append-only design avoids UPDATE locks. Indexed for granter/grantee lookups.
