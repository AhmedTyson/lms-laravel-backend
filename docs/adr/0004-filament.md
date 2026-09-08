# ADR-0004: Filament admin panel

- **Decision:** Install `filament/filament` ^5 (v5.8.1 verified resolvable + installable on Laravel 13, no advisories). Panel at `/admin` (session guard); LMS API stays JWT-only. Resources per module land in later phases, owned by each module slice.
- **Reason:** Admin persona needs oversight UI (approvals, users, groups, reports); building bespoke CRUD would dwarf module work. Real architectural need, not scaffolding sugar.
- **Alternatives:** No admin UI (API-only + Scramble) — rejected: instructor-approval and reporting workflows need human UI. Custom Blade admin — rejected: cost, Filament covers tables/forms natively.
- **Impact:** Livewire + Filament stack merged; `app/Providers/Filament/AdminPanelProvider.php`; published assets git-ignored (`public/{css,fonts,js}/filament`); requires PHP `ext-intl` (enabled locally, added to README prerequisites). Module structure rule still applies: Filament Resources live under their module's layer, not dumped in `app/`.
- **Status:** Accepted.
