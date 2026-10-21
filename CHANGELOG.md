# Changelog

All notable changes to this project will be documented in this file.

## [6.0.0]

The functional rewrite on top of `sidus/datagrid-bundle` 5: everything is immutable and nothing survives a request,
so the bundle is safe in a long-running worker (FrankenPHP, RoadRunner). A clean break, see `UPGRADE-6.0.md`.

### Changed (BC breaks)

- Requires PHP 8.4+, Symfony 8.1+ and `sidus/datagrid-bundle` `^5.0`; `composer.json` declares every imported package
  (`psr/log`, `symfony/config`, `dependency-injection`, `options-resolver`, `property-access`, `translation`, `twig-bundle`,
  `security-bundle`...); Doctrine stays optional (`suggest`).
- Layout: PHP in `src/` (PSR-4 `Sidus\AdminBundle\` => `src/`), `Resources/views` -> `templates`,
  `Resources/translations/*.yml` -> `translations/*.yaml`, `Resources/config/services.yaml` -> `config/services.php`.
- `SidusAdminBundle` is an `AbstractBundle`; `SidusAdminExtension` and `DependencyInjection\Configuration` are removed.
- `Admin` and `Action` are `final readonly` (no setter); the action describes its route with plain values and
  `Action::createRoute()` builds a new `Route` each time (`getRoute()` is removed). `PermissionCheck`, `ActionLink`,
  `ActionResponse`, `RedirectActionResponse` are `final readonly` too.
- The admins are built at container compile time by `AdminDefinitionPass`: configuration errors name the admin and the
  action (missing entity or prefix, unknown class, unknown pattern variable, no controller for an action, invalid route,
  codes that differ only by case, duplicated route paths, reserved route defaults...). Controllers are resolved at compile
  time and stored in the `_controller` default of the route.
- `AdminRegistry` is read-only and moved to `Registry\AdminRegistry` (`get()`, `has()`, `all()`); `AdminEntityMatcher`
  has no cache; `AdminRouteLoader` has no state (no more "Do not add the loader twice").
- Removed all shared request state: `AdminRegistry::getCurrentAdmin()`/`setCurrentAdmin()`, `Admin::getCurrentAction()`/
  `setCurrentAction()`, `ActionInjectableInterface`, `ActionInjectableTrait`, `AdminControllerInjecterSubscriber`,
  `AdminControllerResolverSubscriber`. The `_admin` and `_action` request attributes are the only source of truth.
- Actions are `readonly` services taking `Action $action` (and optionally `Admin $admin`) as arguments; `RedirectionTrait`
  becomes `AbstractEditDelegatingAction`; the redirection after a create or a clone checks a `PermissionCheck` on the
  saved entity.
- `ListAction` uses `DataGridFactory::create()` and `customizeHandler()` for the per-request query; `DataGridHelper` and
  the `AdminLinkType` form type are removed.
- `LegacyVoter` (`is_granted('edit', $entity)`, deprecated since 5.0) is removed, use a `PermissionCheck`.
- `admin_class`, `action_class`, the unused admin `controller` key and the `sidus.admin` tag are removed. The `options` of
  an action are no longer passed to the route: use `route_options`. Admin and action codes are no longer silently
  normalized (`my-admin` -> `my_admin`) and must match `[A-Za-z][A-Za-z0-9_]*`.
- The templates are neutral, framework-agnostic HTML (no Bootstrap class) with overridable blocks: `edit.html.twig` has
  `heading`/`heading_text`/`form`/`message`/`form_actions`/`back_button`/`submit_button`, `title` is the page title. The
  Bootstrap 4 datagrid and pager templates are removed (the datagrid theme comes from the datagrid bundle). `action_link`
  renders no icon by default and no longer assumes Font Awesome.
- Translations are `translations/messages.{en,fr}.yaml` (loaded with `symfony/yaml`): `fr` contained the datagrid keys by
  mistake and now translates the admin.

### Added

- `Request\AdminContext` (stateless, reads the request stack), the `Action`/`Admin` controller arguments
  (`AdminArgumentValueResolver`), the Twig functions `current_admin()` and `current_action()`.
- `DataGrid\AdminActionResolver`: datagrid actions with `options.admin_action` (and `admin`, `entity`, `on_denied`) get the
  `uri` and the label of the admin action, and are hidden or disabled for the users who are not granted it.
- `sidus_admin.theme` (`null` or `bulma_1`) and, separately and opt-in, the Bulma 1 Symfony form layout
  `@SidusAdmin/form/bulma_1_layout.html.twig` (to add to `twig.form_themes`); `sidus_admin.action_link` defaults;
  `@SidusAdmin/base.html.twig` (the default `base_template`) and a default `template_pattern`.
- `.gitattributes`, `.gitignore`, `phpunit.xml.dist` (PHPUnit 12), a GitHub Actions matrix (PHP 8.4/8.5, Symfony
  8.1/8.x, lowest/highest), a kernel test harness (Doctrine on SQLite, security, datagrid) with worker-mode, CRUD,
  permission, routing, compile-error, template and theme tests, `UPGRADE-6.0.md`, a rewritten README.

### Fixed

- Symfony 8 compatibility: `EntityVoter::vote()` accepts the optional `?Vote $vote = null` argument added to
  `VoterInterface::vote()` (Symfony 7.3+, mandatory in 8.0). Without it PHP raised a fatal "Declaration must be
  compatible" error as soon as the container was built.
- The labels of `action_link` looked for the translation `sidus.admin.<action>.label`, which no file defines: the keys
  are `sidus.admin.action.<action>.label`. A humanized fallback of a capitalized word no longer starts with a space.
- A dead `AdminExtension::tryTrans()` calling an undefined method, an unused `$authorizationChecker` in
  `AdminEntityValueResolver` and an unused `Twig\Environment` in `ActionLinkHelper` are removed.

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
