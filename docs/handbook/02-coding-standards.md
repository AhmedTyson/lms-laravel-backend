# 02 — Coding Standards (policy)

## 1. Toolchain (non-negotiable)

- PHP ^8.4, Laravel 13. Pint is the formatter — never hand-format; run `./vendor/bin/pint` (fix) and commit the result.
- PHPStan level 5 over `app/`, `config/`. New code must not introduce warnings. The `routes/` exclusion (scaffold `$this` idiom) stays unless the scaffold changes.

## 2. Module placement

- Business logic lives in `Modules/<Name>/`. Root `app/` holds only shared kernel concerns (base User model, global exceptions).
- Per module, create only needed layers: `Domain` (enums, events, invariants), `Application` (actions, services), `Infrastructure` (external adapters), `Http` (controllers, requests, resources), `Database` (migrations, factories, seeders), `Tests`. No empty scaffolding.
- Filament Resources belong to their module, never in root `app/`.

## 3. Forbidden patterns

- No `BaseService` / `BaseRepository` / generic abstractions without a concrete second use.
- No repositories when Eloquent + query scopes suffice. No CQRS, no event sourcing.
- No business logic in Eloquent model events (cycle checks, grants, scoring belong in explicitly-invoked services — testable in isolation).
- No `hasPermissionTo()` without an explicit team parameter inside AccessManagement and Reporting (ADR-008). Grep-enforced in review.
- No `DB::statement` / engine-specific SQL (SQLite dev must keep working).
- No hardcoded credentials, tokens, or secrets anywhere. Ever. (See 05.)

## 4. API conventions

- REST under `/api`. JWT (`auth:api`) for all clients; sessions only for Filament `/admin`.
- Controllers thin: validate (FormRequest) → authorize (Policy/Gate) → delegate to service → return Resource.
- Errors: 422 validation with field messages, 401 unauthenticated, 403 unauthorized, 404 missing. Document every shape in Scramble annotations.
- List endpoints always paginated with search/sort where the spec requires filtering.

## 5. Language preferences

- Native PHP backed enums (no enum package). Laravel events/queue/validation (no wrappers).
- `spatie/laravel-data` DTOs only where a phase proves the need — record in an ADR first.
