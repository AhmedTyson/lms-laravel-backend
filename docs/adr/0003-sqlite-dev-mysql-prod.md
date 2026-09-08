# ADR-0003: SQLite dev, MySQL prod

- **Decision:** `DB_CONNECTION=sqlite` locally (file + `:memory:` in tests via phpunit.xml); MySQL in staging/prod. Migrations must be portable — no raw SQL, no engine-only features.
- **Reason:** Zero local services (mysql CLI absent); keeps onboarding to `composer install` + `migrate`.
- **Alternatives:** Docker MySQL for all envs — rejected for now: heavier onboarding, no gain before Phase 9 schema. SQLite in prod — rejected: concurrency + spec targets MySQL.
- **Impact:** `docs/db-conventions.md` enforces portability; CI runs `migrate:fresh --env=testing` on SQLite.
- **Status:** Accepted.
