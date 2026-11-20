# AGENTS.md — LMS Laravel

Init-phase repo (Laravel 13, PHP ^8.4 enforced, Windows pwsh). Spec: `docs/LMS_Laravel_BRD_PRD_v2_1_full.md` v2.1. Baseline: `docs/Architecture Initialization + Phase Replanning Prompt.md`. Tracker: `tasks/plan.md`, `tasks/todo.md`. Developer policies (workflow, standards, testing, review, security, governance, DoD, runbook, module guide): `docs/handbook/01–09` — follow them, cite them in PRs.
Generate module classes only with `php artisan module:make-*` (never root `make:`); plain `php artisan migrate` covers `Modules/*/database/migrations` (verified by probe). Root `app/` = kernel only (User, providers); behavior lives in modules. Telescope `/telescope` local-only.
PHP floor is 8.4 (`composer.json` + `config.platform.php` pin): locked Symfony 8 / activitylog need >= 8.4.1. Never lower CI (`ci.yml` runs 8.4) or the lock below it.

## Commands (run from repo root)

```powershell
vendor/bin/pest --colors=never           # all tests
vendor/bin/pest --filter="Health"        # single test
./vendor/bin/pint --test                 # style gate, run before every commit
./vendor/bin/phpstan analyse --no-progress --memory-limit=1G
php artisan migrate:fresh --force        # SQLite dev
php artisan module:list                  # nWidart modules (Phase 2+)
```

Order that matters: `pint -> pest -> phpstan -> migrate:fresh`. CI runs the same four.

## Verified gotchas (don't rediscover)

- Pest is **v4.7** (not v5): v5 needs PHPUnit 13, v4 needs Symfony 7 — resolved via `composer require ... -W`. Don't upgrade either without re-resolving.
- `phpstan.neon` scans only `app/`, `config/` — scaffold `routes/console.php` uses runtime-bound `$this` that PHPStan flags. API routes covered by Pest instead.
- `config/permission.php`: `'teams' => true` set **before** Spatie migration ran. If permission tables ever reset, keep it `true`.
- `wikimedia/composer-merge-plugin` trusted + `Modules/*/composer.json` include in root `composer.json`. After `module:make`, run `composer dump-autoload` or providers won't resolve. If composer asks trust again, answer `y`.
- PowerShell strips `^` in composer constraints — always single-quote with `*` form: `composer require 'filament/filament:5.*'`.
- PHP needs `ext-intl` (Filament). If missing: uncomment `extension=intl` in `php.ini`.
- Auth split: LMS API = `auth:api` (JWT) only; `web` sessions only for Filament `/admin`. `User implements JWTSubject`; `defaults.guard` stays `web` so panel login works.
- `bootstrap/app.php` registers `routes/api.php` explicitly (Laravel 13 has no api file by default). Health at `/api/health`, Scramble UI at `/docs/api`.
- `.env` holds a real local `JWT_SECRET` and is git-ignored — never commit, never copy to staging/prod.
- `QUEUE_CONNECTION=sync` local / `redis` staging. `MAIL_MAILER=log` local.
- phpunit.xml forces `sqlite :memory:` — never set `DB_CONNECTION` overrides in CI/test env.
- Pest `uses(Trait::class)` with an imported name fails to resolve — bind shared traits in `tests/Pest.php` (`->use(...)`), never per-file. Pint's `fully_qualified_strict_types` will otherwise fight Pest forever.
- All JSON via `App\Support\ApiResponse` (`success`/`data`/`error`/`jwt`) — never hand-built envelopes. 429s included.
- Rate limiters are module-owned (`AuthServiceProvider`, `AccessManagementServiceProvider`); core keeps `api` only. Named limiters: `auth-login/register/verify/password/oauth`, `access-grants/groups`.
- Factories resolve via `Factory::guessFactoryNamesUsing` in `AppServiceProvider` — no per-model wiring. Smoke guard: `tests/Feature/FactorySmokeTest.php`. Demo data: per-module seeders in FK order (`docs/mock-data-plan.md`).
- FormRequest per endpoint, thin controllers (validate → authorize → service → resource). Resources live in `Modules/<Name>/app/Http/Resources/` with `@mixin` model hints (Scramble JR001).
- List endpoints use `spatie/laravel-query-builder` (ADR-0006): `filter[x]`, `sort`, paginated envelope. Phone validation via `propaganistas/laravel-phone`; normalization centralized in `App\Support\PhoneNormalizer` (request pre-validate + model mutator).
- Brain scanner config (`config/laravel-brain.php`): `source_paths` includes `Modules/*/app`, `route_paths` includes `Modules/*/routes/*.php`, AST mode (auto-discover pulls package bulk).
- Route contracts: `docs/routes/<module>.md` per method (bodies + responses). Update the doc when the route changes.
- Clean-code process: `docs/handbook/10-clean-code-rules.md` (10 rules + open backlog). Findings get fix or ticket, never silence.

## Architecture rules (from baseline, enforced in review)

- Only structuring package: `nwidart/laravel-modules:^13`. Never add Architex / Easy Modules / DDD Generator / `alizharb/laravel-modular`.
- Module shells: `Modules/<Name>/` with only needed layers. Pruned at init: providers + config + database stubs + empty routes + tests. No `Http/`, `resources/`, `package.json`, `vite.config.js` until a slice needs them. No BaseService/BaseRepository, no repos/interfaces without concrete need.
- Filament accepted (ADR-0004, v5.8.1): panel at `/admin`. Resources live under their module's layer, never dumped in `app/`. Published panel assets are git-ignored.
- Gate test `tests/Feature/ModulesSmokeTest.php` fails if a module is disabled or a business `api/*` route appears before its phase — keep it green during init.
- Native first: PHP backed enums, Laravel events/queue/validation. `spatie/laravel-data` only if a phase proves DTO need.
- AccessManagement is authorization-only (`who can do what`), never teaching domain. Auth ≠ Authorization ≠ Enrollment — separate modules.
- Later-phase invariants (don't pre-build): `permission_grants` append-only; `manager_id` cycle check = service + transaction + `CircularManagerAssignmentException`; grant/revoke + Reporting use **explicit** Spatie team param, never ambient `setPermissionsTeamId()`.
- Every new package/pattern needs `docs/adr/NNNN-name.md` (Decision / Reason / Alternatives / Impact / Status).
- Approval endpoint (`instructors/{id}/approve`) lives in Auth until Phase 5 moves it to AccessManagement — do not duplicate approval logic elsewhere.
- Phase status words: `done`/`partial`/`open`/`hold`; checkpoint signed only with zero open boxes (`tasks/phases/manifest.json` mirrors `todo.md`).

===

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

## Foundational Context

This application is a Laravel application running on PHP 8.5. Always use the APIs that match the installed major version of each package — do not assume a version.

Before relying on a package's API, confirm its installed version:
- PHP packages: run `composer show --direct` to list direct dependencies with versions, or `composer show <vendor/package>` for a single package.
- JS packages: check `package.json` for the installed versions.

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If a frontend change doesn't show in the UI or you get a "Unable to locate file in Vite manifest" error, run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Use `search-docs` before changes that depend on Laravel ecosystem APIs, behavior, configuration, or version-specific syntax. Skip it for copy-only edits and other changes where package documentation is irrelevant. Reuse sufficient results already in context instead of searching again.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Project Rules

- This project contains committed, area-grouped rules in `.ai/rules` when that directory exists, including path-scoped framework guidelines under `.ai/rules/boost`. Before you enter plan mode or create/edit any file, you MUST first: open @.ai/rules/index.md (it maps file globs to rule files), read every rule file whose globs cover the path(s) in scope, and run `grep -rin 'keyword' .ai/rules` to catch what a path match alone misses. Do not write code until you have read and are following every matching rule. If `.ai/rules` does not exist, continue without it.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.
- Activate the `deploying-to-cloud` skill whenever deploying to Laravel Cloud, configuring Cloud environments or resources, using the Cloud CLI, or troubleshooting Cloud deployments.

=== tests rules ===

# Test Enforcement

- Add or update tests for behavior and logic changes when a test provides meaningful regression coverage.
- Pure copy, styling, and layout-only changes do not require new or updated tests.
- When test coverage applies, run the affected tests and ensure they pass.
- Test the changed behavior and its important failure modes, but do not add tests beyond them.
- Read the `testing-best-practices` skill before writing tests.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== pest/core rules ===

# Pest

- This project uses Pest. Create tests with `php artisan make:test --pest {name}`.
- Do not include the test suite directory in `{name}`. Use `SomeFeatureTest`, not `Feature/SomeFeatureTest`.
- Read the `testing-best-practices` skill for guidance on coverage, naming, structure, dependency isolation, and review.
- Do not delete tests or test files without approval. They are part of the application.

## Running Tests

- Run the narrowest set of tests that covers the change. Pass a file path or `--filter=testName` to `php artisan test --compact`.
- Rerun a test after each change to it.
- Run `vendor/bin/pest` to call the test runner directly. It accepts the same file path and `--filter=testName` arguments.
- After the feature tests pass, ask the user to run the complete suite with `php artisan test --compact`.

</laravel-boost-guidelines>
