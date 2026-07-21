# Changelog

All notable changes to this project will be documented in this file.

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
