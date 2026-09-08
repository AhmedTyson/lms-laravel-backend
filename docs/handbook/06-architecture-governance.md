# 06 — Architecture Governance (policy)

## 1. Decision records

- Any new package, pattern, or cross-module contract needs `docs/adr/NNNN-name.md` with Decision / Reason / Alternatives / Impact / Status — before the code lands, not after.
- ADRs are immutable once accepted; supersede with a new ADR referencing the old one. Existing: 0001 JWT, 0002 nWidart-only, 0003 SQLite-dev/MySQL-prod, 0004 Filament.

## 2. Package approval

- Install now / later / never lists live in `tasks/plan.md` §G. A package outside "install now" needs an ADR + reviewer approval.
- Permanently rejected (no re-proposals without new evidence): overlapping module/architecture generators, enum packages, CQRS/event-sourcing stacks, generic base-class libraries.
- `composer audit` must be clean; security-blocked versions (as seen with Filament 5.0.0) are skipped, never force-installed.

## 3. Dependency rules

- Direction in `docs/module-dependencies.md` is law: downward only, events for upward communication, Reporting read-only.
- New `use Modules\<Other>` imports are flagged in review; upward or lateral imports require an ADR.
- Module-internal layering follows handbook 02 §2. Shared kernel code in root `app/` needs justification — default answer is "put it in the owning module."

## 4. Spec authority

- `docs/LMS_Laravel_BRD_PRD_v2_1_full.md` v2.1 outranks discussion. Sibling .NET doc (v2.0) is not authoritative for this repo.
- Deferred items (Appendix A open list: bulk import, cache admin endpoints, password reset, republishing, group tie-break) stay out of scope until a phase explicitly adopts them via ADR.
- Rule changes (e.g. scoring model) update the spec reference in the task PR — code and spec must never disagree silently.
