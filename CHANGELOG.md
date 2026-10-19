# Changelog

All notable changes to this project will be documented in this file.

## [Unreleased]

### Fixed

- Symfony 8 compatibility: `EntityVoter::vote()` and `LegacyVoter::vote()` now accept the optional
  `?Vote $vote = null` argument added to `VoterInterface::vote()` (Symfony 7.3+, mandatory in 8.0).
  Without it PHP raised a fatal "Declaration must be compatible" error as soon as the container was built.
  Still compatible with Symfony 6.4 and 7.4 (the extra argument is optional and ignored).

## [5.0.6] - 2026-07-21

### Fixed

- PHP 8.4 deprecates typed parameters defaulting to `null` without an explicit `?` prefix.
  Fixed in `TemplatingHelper`, `FlashHelper`, `AdminRouteLoader`, `DoctrineHelper` and
  `DataGridHelper`.

### Note

- The above fix was originally released as `5.0.5`. Its tag was mistakenly force-moved after
  publication (a `git commit --amend` to add this changelog entry, then a re-tag) to attach
  it, which Packagist correctly rejected: stable tags are immutable once published. `v5.0.5`
  has been restored to its originally-published commit (`a1b1f23`, no changelog entry for
  this fix), and this changelog entry is released as `5.0.6` instead. No code changed between
  `5.0.5` and `5.0.6` - `5.0.6` only adds this entry.

## [5.0.4] - 2026-07-21

### Fixed

- `DoctrineHelper::getManagerForEntity()` used `Doctrine\Common\Util\ClassUtils::getClass()`, which
  was removed from `doctrine/persistence` 3+/4 (the `doctrine/common` package that used to ship it
  is no longer a dependency of modern Doctrine ORM/DBAL installs). This crashed every save/delete
  action with `Attempted to load class "ClassUtils" from namespace "Doctrine\Common\Util"` on any
  project using `doctrine/orm` ^3.0 without also requiring the long-deprecated `doctrine/common`.
  Replaced with a proxy-aware class resolution using `Doctrine\Persistence\Proxy` (still present in
  `doctrine/persistence`), falling back to native `$entity::class` — which is already correct for
  Doctrine ORM 3's native lazy objects (PHP 8.4 lazy ghosts no longer subclass the entity, so
  `::class` already returns the real entity class).

### Compatibility

- Verified against PHP 8.2-8.4, Symfony 7.4, Doctrine ORM 3.

## [5.0.3] - 2024-01-25

See git history prior to this file for changes up to 5.0.x.
