# ADR-0001: Tymon JWT as sole API auth

- **Decision:** `tymon/jwt-auth` ^2.3; `api` guard (`driver=jwt`) in `config/auth.php`; `User implements JWTSubject`. Web sessions exist only for the Filament panel, never for LMS API clients.
- **Reason:** Baseline mandates Tymon; stateless tokens fit the REST API + future mobile clients.
- **Alternatives:** Laravel Sanctum (token) — rejected: baseline pins Tymon and JWT carries claims needed for approval/team context. Session auth for API — rejected: not stateless, breaks mobile.
- **Impact:** All Phase 12+ endpoints sit behind `auth:api`. `config/jwt.php` published; `JWT_TTL=60` default. `defaults.guard` stays `web` so Filament session login keeps working.
- **Status:** Accepted (verified installable on Laravel 13).
