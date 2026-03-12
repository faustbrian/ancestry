# Package Maintenance Rules

These rules apply to all package work in this repository. RFC 2119
keywords (MUST, MUST NOT, SHOULD, SHOULD NOT, MAY) are used
intentionally.

## Release And Documentation Hygiene

- You MUST update `CHANGELOG.md` for every implementation task that
  creates, modifies, or deletes files before claiming completion.
- You MUST update `README.md`, examples, and other package
  documentation when public behavior, configuration, installation, or
  usage changes.
- You MUST document breaking changes, removals, and migration steps in
  `CHANGELOG.md` before the work is considered complete.
- You SHOULD prefer a documented deprecation path before removing or
  renaming public APIs.

## Public API And Compatibility

- You MUST treat public PHP APIs, configuration keys, environment
  variables, command signatures, events, and exception contracts as
  SemVer-governed surface area.
- You MUST NOT introduce backward-incompatible behavior without a clear,
  documented architectural reason and explicit release-note coverage.
- You MUST NOT alternate between conflicting style patterns without a
  documented architectural reason.
- You MUST keep commits and feature changes focused. Unrelated refactors
  MUST be split into separate work.

## Testing And Verification

- You MUST add or update automated tests for every bug fix and every
  user-visible behavior change.
- You MUST prefer regression coverage before changing existing behavior.
- You MUST run `make refactor` before pushing.
- You MUST run `make lint` before pushing.
- You MUST run `make test` before pushing.
- You MUST report the exact verification commands you ran when handing
  work off for review.

## Dependency Discipline

- You MUST keep the dependency surface minimal and MUST NOT add,
  upgrade, or remove dependencies without a clear maintenance benefit.
- You MUST verify new dependency constraints against the package's
  supported platform and framework matrix before merging.
- You SHOULD prefer existing project utilities and framework features
  over adding new libraries for small conveniences.

## Runtime Safety

- You MUST NOT introduce mutable runtime static properties for caches,
  helper instances, or request-scoped state.
- Laravel Octane keeps workers alive across requests, so mutable static
  properties can leak data between requests, hold stale container
  dependencies, and produce cross-request bugs that do not appear under
  the normal PHP request lifecycle.
- Prefer per-operation context objects, container singletons, or
  instance-owned caches that are explicitly scoped to the right
  lifetime.
