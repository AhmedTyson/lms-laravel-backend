# ADR-0008: Stateless JWT revocation via user token_version claim

- **Decision:** Add `users.token_version` (`unsignedInteger`, default `1`) and embed `'ver' => (int) $this->token_version` in `User::getJWTCustomClaims()`. `auth:api` requests verify the token's `ver` claim matches the authenticated user's current `token_version` (and that `suspended_at` is null); password resets and account suspensions increment `token_version`.
- **Reason:** Tymon JWT blacklisting invalidates only the single token presented in the current request (`jti`). Password reset (`FIND-09`) and account suspension (`FIND-15`) have no handle to all previously issued access/refresh tokens for a user; bumping `token_version` revokes all active tokens atomically in O(1) without storing per-token session tables.
- **Alternatives:** Storing all issued `jti` values in a database table — rejected: turns stateless JWT into stateful session tracking and bloats writes on every login/refresh. Waiting for `JWT_TTL` expiry — rejected: violates OWASP ASVS V3.3.2/V3.3.3 and leaves a 60-minute hijack window (or 14-day refresh window).
- **Impact:** Migration adds `token_version` and `suspended_at` to `users`; `User`, `PasswordController`, and `Auth` middleware enforce `ver` parity.
- **Status:** Accepted.
