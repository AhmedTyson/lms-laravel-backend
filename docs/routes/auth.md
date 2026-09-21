# Auth Routes — `/api/auth`

Base: `/api` (module provider) + `auth` prefix. Envelopes via `ApiResponse`.

## POST `/register` — student/instructor signup

Auth: public. Throttle: `auth-register` (6/min/IP).

Request:

```json
{
  "name": "Sara Ahmed",
  "email": "sara@example.com",
  "phone_number": "+201001234567",
  "phone_country": "EG",
  "role": "student",
  "password": "Str0ng!Pass",
  "password_confirmation": "Str0ng!Pass"
}
```

| Field | Rules |
|---|---|
| `name` | required, string, max 255 |
| `email` | required, email, max 255, unique |
| `phone_number` | nullable, EG mobile E.164, unique (canonicalised pre-validate) |
| `phone_country` | nullable, 2-char code, hint only, not stored |
| `role` | required, `student`\|`instructor` |
| `password` | required, confirmed, min 8, letters + mixed case + numbers + symbols |

Response `201`:

```json
{
  "message": "Registration successful. Please verify your email address.",
  "data": { "id": 1, "name": "Sara Ahmed", "email": "sara@example.com", "phone_number": "+201001234567", "manager_id": null, "manager_depth": 0, "approval_status": null, "email_verified": false, "email_verified_at": null, "created_at": "2026-09-21T00:00:00+00:00" }
}
```

Instructors get `approval_status: "pending"`. Errors: `422` validation.

## POST `/login` — JWT issue

Auth: public. Throttle: `auth-login` (5/min per email+IP).

Request: `{ "email": "sara@example.com", "password": "Str0ng!Pass" }`.

Response `200`:

```json
{
  "access_token": "<jwt>",
  "token_type": "bearer",
  "expires_in": 3600,
  "user": { "<UserResource>" }
}
```

Errors: `401` + `INVALID_CREDENTIALS`.

## POST `/verify-email` — HMAC-signed verify

Auth: public. Throttle: `auth-verify` (6/min/IP).

Request: `{ "id": 1, "hash": "<40-hex>", "expires": 1780000000, "signature": "<hmac>" }`.

Response `200`: `{ "message": "Email verified successfully.", "data": { "<UserResource>" } }`. Already verified → same shape, `Email is already verified.`.

Errors: `400` + `INVALID_SIGNATURE` (bad HMAC or expired), `400` + `INVALID_HASH`.

## POST `/forgot-password` — reset link

Auth: public. Throttle: `auth-password` (5/min/IP).

Request: `{ "email": "sara@example.com" }` (no `exists` rule — never leaks registration).

Response `200`: `{ "message": "If your email is registered, you will receive a password reset link shortly." }`.

Errors: `400` + `RESET_LINK_FAILED`.

## POST `/reset-password` — set new password

Auth: public. Throttle: `auth-password`.

Request: `{ "token": "<mail-token>", "email": "...", "password": "...", "password_confirmation": "..." }` (password = register strength rules).

Response `200`: `{ "message": "Password reset successfully." }`.

Errors: `400` + `INVALID_RESET_TOKEN`.

## GET `/google/redirect` — OAuth URL

Auth: public. Throttle: `auth-oauth` (10/min/IP).

Response `200`: `{ "url": "https://accounts.google.com/..." }`.

Errors: `503` + `OAUTH_NOT_CONFIGURED` (no Google credentials).

## POST `/google/callback` — OAuth login

Auth: public. Throttle: `auth-oauth`.

Request: `{ "code": "<google-code>", "role": "student" }` (`role` nullable, default `student`).

Response `200`: jwt envelope (same shape as login). First link sets `email_verified_at`, instructors pend.

Errors: `422` validation, `503` + `OAUTH_NOT_CONFIGURED`, `422` + `INVALID_GOOGLE_CODE`.

## POST `/logout` — blacklist JWT

Auth: `auth:api`. Throttle: `api`.

Response `200`: `{ "message": "Successfully logged out." }`.

## POST `/refresh` — rotate JWT

Auth: `auth:api`. Throttle: `api`.

Response `200`: `{ "access_token": "<jwt>", "token_type": "bearer", "expires_in": 3600 }` (no `user` key).

## GET `/me` — profile

Auth: `auth:api`. Throttle: `api`.

Response `200`: `{ "data": { "<UserResource>" } }`.

## POST `/instructors/{id}/approve` — approve + assign manager

Auth: `auth:api` + `approve-instructors` ability. Throttle: `api`.

Request: `{ "manager_id": 3 }` (nullable, defaults to caller).

Response `200`: `{ "message": "Instructor approved successfully.", "data": { "<UserResource, approval_status approved>" } }`.

Errors: `409` + `ALREADY_APPROVED`.

## Shared shapes

`UserResource`: `id, name, email, phone_number, manager_id, manager_depth, approval_status, email_verified, email_verified_at, created_at`.
