# ADR-0002: nWidart as the only module structurer

- **Decision:** `nwidart/laravel-modules` ^13.0. Autoload via `wikimedia/composer-merge-plugin` (`Modules/*/composer.json` include, plugin trusted). Conventions over generators from here on.
- **Reason:** Only package providing the business-module layer Laravel lacks; v13.0.0 verified Laravel 13 + PHP ^8.3 compatible.
- **Alternatives:** Architex / Easy Modules / DDD Generator / `alizharb/laravel-modular` — rejected: overlapping all-in-one generators, baseline §4 forbids. Hand-rolled `app/Domains/*` — rejected: no tooling (make/test commands), reinvented wheel.
- **Impact:** Nine shells in `Modules/` (pruned: providers + config + database stubs + empty routes + tests). `composer.json` carries the merge-plugin include; answering `y` to the trust prompt is documented, not optional.
- **Status:** Accepted.
