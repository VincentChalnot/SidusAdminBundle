# Changelog

All notable changes to this project will be documented in this file.

## [Unreleased] - 6.0

Mechanical foundation only: no class renamed, no behaviour change. Requires `sidus/datagrid-bundle` 5.0.

### Changed

- **BC break**: requires PHP >= 8.4, Symfony `^8.1` components and `sidus/datagrid-bundle` `^5.0`.
- **BC break**: PHP sources moved to `src/` (PSR-4 `Sidus\AdminBundle\` => `src/`).
- **BC break**: `Resources/views/*` moved to `templates/*` (sub-directories and file names unchanged, the `@SidusAdmin` Twig namespace is unchanged).
- **BC break**: `Resources/translations/messages.{en,fr}.yml` moved to `translations/messages.{en,fr}.yaml`.
- `Resources/config/services.yaml` moved to `config/services.yaml`; its `resource`/`exclude` paths and the extension `FileLocator` updated.
- `SidusAdminBundle::getPath()` returns the bundle root (not `src/`) so that templates and translations are discovered.
- `composer.json`: `type` is `symfony-bundle`, `support` links, `config.sort-packages`, `scripts.test`, `autoload-dev`, `exclude-from-classmap`; all imported packages are declared (`psr/log`, `symfony/config`, `dependency-injection`, `options-resolver`, `property-access`, `translation`...). Doctrine (`doctrine/orm`, `doctrine/persistence`) stays optional (`suggest` + `require-dev`).

### Added

- `.gitattributes`, `.gitignore` (including `.idea/`), `phpunit.xml.dist` (PHPUnit 12) and a GitHub Actions CI workflow (PHP 8.4/8.5 x Symfony 8.1.*/8.* x lowest/highest).

### Removed

- Unused `use Sidus\BaseBundle\Utilities\TranslatorUtility` import in `TranslatorHelper`.

### Fixed

- Symfony 8 compatibility: `EntityVoter::vote()` and `LegacyVoter::vote()` now accept the optional
  `?Vote $vote = null` argument added to `VoterInterface::vote()` (Symfony 7.3+, mandatory in 8.0).
  Without it PHP raised a fatal "Declaration must be compatible" error as soon as the container was built.

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
