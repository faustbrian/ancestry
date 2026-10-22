# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- Added repository-level maintainer guidance in `AGENTS.md`.
- Deleting a model that uses `HasAncestry` removes its own closure rows (its
  self rows and its paths to its ancestors) in every ancestry type, so a
  deleted model no longer leaves orphaned rows behind. Ancestry snapshots are
  kept, and no ancestry events are dispatched.
- Initial release

### Changed
- Deleting a model that still has descendants in any ancestry type now throws
  `NodeHasDescendantsException` and nothing is deleted. Previously the delete
  went through and left the descendants with paths to a missing node. Delete,
  move or detach the descendants first. Rows left behind for descendants that
  no longer exist (for example from deletes before this release) also block
  the delete; DOCS.md shows the query that removes them.
- Soft deletes leave the closure rows in place, so a restored model keeps its
  hierarchy; only a force delete removes rows or throws. Deletes that skip
  model events (query builder deletes, `deleteQuietly()`, `withoutEvents()`)
  leave the rows behind.
- Deleting a model that uses `HasAncestry` now runs closure-table queries and
  can fail where it used to succeed: under an enforced morph map or morph key
  map every class using the trait (subclasses included) must be mapped, and a
  model loaded without its morph key column cannot be deleted. Without a
  surrounding transaction, a delete whose first check Laravel skips (an
  earlier `deleting` listener returning a value, `Event::defer()`) is refused
  only after the model row is gone. See "Deleting Models" in DOCS.md for the
  remaining limits.
- `ConfiguresTable` reads its table properties without null coalescing on
  typed properties, so current PHPStan passes. Models using it without the
  properties still fall back to `table_name` and `hierarchies`.
