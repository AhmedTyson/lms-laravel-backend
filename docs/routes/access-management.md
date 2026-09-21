# AccessManagement Routes — `/api`

No prefix. Auth: all `auth:api`. Envelopes via `ApiResponse`.

## POST `/grants` — append grant row

Throttle: `access-grants` (30/min/user).

Request:

```json
{ "grantee_id": 5, "permission_name": "courses.publish", "group_id": 2 }
```

| Field | Rules |
|---|---|
| `grantee_id` | required, integer, exists users |
| `permission_name` | required, string, max 255 |
| `group_id` | required, integer, exists groups |

Guards (RULE-003/004): grantee must sit under granter's chain; granter must hold permission globally or in-group. Same transaction appends ledger row (`action: granted`) + syncs read model (RULE-005, ADR-012).

Response `201`:

```json
{
  "message": "Grant recorded.",
  "data": { "id": 9, "granter_id": 1, "grantee_id": 5, "group_id": 2, "permission_name": "courses.publish", "action": "granted", "granted_at": "2026-09-21T00:00:00+00:00" }
}
```

Errors: `422` validation, `403` + `GRANT_FORBIDDEN`, `404` + `NOT_FOUND`.

## POST `/revokes` — append revoke row

Same throttle, same request shape, same guards.

Response `201`: `{ "message": "Revoke recorded.", "data": { "<GrantResource, action revoked>" } }`. Read-model row deleted atomically. Original rows untouched (append-only).

Errors: `422`, `403` + `REVOKE_FORBIDDEN`, `404` + `NOT_FOUND`.

## GET `/groups` — list visible groups

Throttle: `access-groups` (60/min/user). Scoped: owned or member only.

Response `200`: `{ "data": [{ "id": 2, "name": "Engineering", "owner_id": 1, "created_at": "..." }] }` (paginated).

## POST `/groups` — create group

Throttle: `access-groups`.

Request: `{ "name": "Engineering" }` (owner = caller, auto-member).

Response `201`: `{ "message": "Group created.", "data": { "<GroupResource>" } }`.

## GET `/groups/{group}` — show

Policy `view` (owner/member), else `403`.

Response `200`: `{ "data": { "<GroupResource>" } }`.

## DELETE `/groups/{group}` — delete + members

Policy `manage` (owner), else `403`. Transactional.

Response `200`: `{ "message": "Group deleted." }`.

## POST `/groups/{group}/members` — add member

Policy `manage`. Throttle: `access-groups`.

Request: `{ "user_id": 5 }` (must exist). Idempotent (`firstOrCreate` + unique pair).

Response `201`: `{ "message": "Member added.", "data": { "id": 4, "group_id": 2, "user_id": 5, "joined_at": "..." } }`.

## DELETE `/groups/{group}/members/{user}` — remove + succession

Policy `manage`. RULE-012: owner removal passes ownership to owner's manager, else oldest surviving user (auto-joined). Non-owner removal: no succession.

Response `200`: `{ "message": "Member removed.", "data": { "<GroupResource with new owner_id>" } }`.

Errors: `404` + `NOT_FOUND` (no membership), `422` (no eligible successor).
