# ADR-0006: spatie/laravel-query-builder for collection filtering

- **Decision:** `spatie/laravel-query-builder` for all list endpoints; `filter[x]` / `sort` / `include` / `fields[x]` client syntax; per-endpoint `allowedFilters`/`allowedSorts` whitelists. Courses `getAllCourses` is the pilot; other modules migrate as their phases land.
- **Reason:** One flexible shape for every big collection (filter + sort + include + sparse fields) instead of hand-maintained `when` chains per endpoint. Ecosystem standard, documented, tested upstream.
- **Alternatives:** Hand-rolled `Filterable` trait — rejected: re-implements partial/exact/sorts/includes badly, maintenance burden per model. Per-controller `when` chains — rejected: linear duplication, no sorts/includes ever.
- **Impact:** Client query syntax changes (`?status=` → `?filter[status]=`); list tests rewritten; exact filters for IDs/enums, partial for text, callbacks for dates/search-groups.
- **Status:** Accepted (pilot: courses).
