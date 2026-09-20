# ADR-0005: Google OAuth via Socialite (stateless)

- **Decision:** `laravel/socialite` for Google login; stateless flow (`Socialite::driver('google')->stateless()`); user lookup by verified Google email; `email_verified_at` set on first link; instructor role keeps `pending` approval (SCOPE-004); JWT issued exactly like password login.
- **Reason:** Official first-party OAuth client; stateless fits token API (no session); Google-verified email replaces our verification step legitimately.
- **Alternatives:** Manual OAuth HTTP calls — rejected: state/PKCE/token-verify burden with no gain. Session-based Socialite — rejected: API has no session (ADR-0001).
- **Impact:** `GOOGLE_CLIENT_ID/SECRET/REDIRECT_URI` env (empty defaults, local dev disabled without them); `GoogleCallbackRequest` + `GoogleAuthService` in Auth module; `GOOGLE_*` documented in `.env.example`.
- **Status:** Accepted.
