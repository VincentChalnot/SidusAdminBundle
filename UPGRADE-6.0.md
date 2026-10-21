# Upgrading from 5.x to 6.0

6.0 is a **clean break**: no compatibility layer, no deprecated alias. Requirements: PHP 8.4+, Symfony 8.1+,
`sidus/datagrid-bundle` 5 (itself on `sidus/filter-bundle` 7).

## Why (worker mode)

In 5.x the admin held request state in shared services: `AdminRegistry::setCurrentAdmin()`, `Admin::setCurrentAction()`,
the action injected by a setter into the (shared) controller service, a datagrid mutated by `bindDataGridRequest()`.
In a long-running worker (FrankenPHP, RoadRunner) the second request saw the admin, the action and the datagrid of the
first. 6.0 removes the cause instead of resetting it:

- the admins and actions are **immutable value objects built when the container is compiled**;
- the admin and the action of a request are the `_admin` and `_action` **request attributes**, nothing else;
- the services are stateless; whatever belongs to a request (datagrid, query, form) is created for it by a factory.

## Packaging and layout

| 5.x | 6.0 |
|---|---|
| PHP sources at the root of the package | `src/` (PSR-4 `Sidus\AdminBundle\` => `src/`) |
| `Resources/views/*` | `templates/*` (the namespace is still `@SidusAdmin`) |
| `Resources/translations/messages.{en,fr}.yml` | `translations/messages.{en,fr}.yaml` |
| `Resources/config/services.yaml` | `config/services.php` (loaded by the bundle class) |
| `SidusAdminExtension`, `DependencyInjection\Configuration` | removed: `SidusAdminBundle` is an `AbstractBundle` (`configure()`, `loadExtension()`) |
| `sidus/datagrid-bundle ~4.0`, PHP 8.1, Symfony 6.3+ | `^5.0`, PHP 8.4, Symfony 8.1 |

Bundles to register: `SidusFilterBundle`, a provider (e.g. `SidusDoctrineFilterBundle`), `SidusDataGridBundle`,
`SidusAdminBundle`, plus the framework, twig and security bundles.

## Configuration

The shape of `sidus_admin.configurations.<admin>` is unchanged except for the points below.

| 5.x | 6.0 |
|---|---|
| `admin_class`, `action_class` (global and per admin) | removed: `Admin` and `Action` are `final readonly` |
| `controller` (admin key, never read) | removed |
| Admin services tagged `sidus.admin`, custom `Admin` services | removed: the admins only come from the configuration |
| action `options` were also the **route options** | `options` are the options of the action only; the route options are `route_options` |
| `permissions` of the README default `[ROLE_USER]` | the real default always was `[]`, documented as such |
| `base_template` default `@SidusAdmin/base.html.twig`, which did not exist | exists: a neutral layout |
| `template_pattern` required (no template otherwise) | default `['@SidusAdmin/Action/{{action}}.{{format}}.twig']` |
| keys with a dash (`my-admin`) silently renamed `my_admin` | the codes are kept as written and must be `[A-Za-z][A-Za-z0-9_]*` |
| controllers resolved on each request (`_controller_pattern` route default) | resolved when the container is compiled; the route has its final `_controller`, no `_controller_pattern` |
| `action_link` defaults not configurable | `sidus_admin.action_link: { icon: false, icon_template: ..., class: null }` |
| no theme | `sidus_admin.theme: null` (neutral) or `bulma_1` |

New compile-time errors (they were request-time errors, or silently wrong): an entity or form type that is not a class,
an unknown `{{variable}}` in a pattern, no controller for an action, a path without a leading slash, an invalid route,
admin codes that differ only by case, two actions with the same route path (unless disjoint `methods`), the reserved
route defaults `_admin` and `_action`.

Datagrid actions that linked to an admin with the `AdminLinkType` form type:

```yaml
# 5.x
actions:
    create: { form_type: Sidus\AdminBundle\Form\Type\AdminLinkType, admin_action: create }
# 6.0
actions:
    create: { options: { admin_action: create } }   # admin: <code> or entity: <class>, on_denied: hide|disable
```

## Code

### Moved, renamed or removed

| 5.x | 6.0 |
|---|---|
| `Configuration\AdminRegistry` | `Registry\AdminRegistry`, `final readonly` |
| `AdminRegistry::getAdmin($code)`, `hasAdmin()`, `getAdmins()`, `addAdmin()` | `get($code)`, `has($code)`, `all()`; no `addAdmin()` (the registry is built from the configuration) |
| `AdminRegistry::getCurrentAdmin()` / `setCurrentAdmin()` | removed: `Request\AdminContext::getAdmin()` / `requireAdmin()` (reads the request, keeps nothing) |
| `Admin::getCurrentAction()` / `setCurrentAction()` | removed: `AdminContext::getAction()` / `requireAction()`, or the `Action $action` controller argument |
| `Model\Admin`, `Model\Action`, `PermissionCheck`, `ActionLink` | `final readonly`, no setter; the action is built by its admin (the Admin <-> Action reference exists without any setter) |
| `Action::getRoute()` (a shared mutable `Route`) | `Action::createRoute()` (a new `Route` on each call), `getRouteDefaults()`, `getRouteVariables()` |
| `Action::getFormType()` falling back to the admin at call time | the fallback is applied when the container is compiled |
| `Action\ActionInjectableInterface`, `ActionInjectableTrait`, `Event\AdminControllerInjecterSubscriber` | removed: add `Action $action` (and `Admin $admin`) to the arguments of your action |
| `Action\RedirectionTrait` | `Action\AbstractEditDelegatingAction` (extended by `CreateAction` and `CloneAction`) |
| `Event\AdminControllerResolverSubscriber` | removed (compile-time resolution) |
| `DataGrid\DataGridHelper` (`getDataGrid()`, `bindDataGridRequest()`, `buildDataGridForm()`) | removed: `Sidus\DataGridBundle\Factory\DataGridFactory::create($code, $request, $formOptions, $customize)`, used by `ListAction` |
| `Form\Type\AdminLinkType` | removed: the `admin_action` option of a datagrid action, `DataGrid\AdminActionResolver` |
| `Security\Voter\LegacyVoter` (`is_granted('edit', $entity)`, deprecated since 5.0) | removed: `isGranted(null, new PermissionCheck($action, $entity))`, Twig `is_action_granted(action, entity)` |
| `Request\AdminEntityValueResolver` constructor (`$authorizationChecker`, `$managerRegistry`) | `$managerRegistry` only (the checker was never used); `readonly` |
| `Templating\ActionLinkHelper` constructor (`$environment` unused, `$logger` required) | `AdminContext` added, no `$environment`, optional `$logger`; `final` |
| `Twig\AdminExtension::getAdmin()`/`tryTrans()` taking anything, `getName()` | `getAdmin(Admin|string)`, the dead `tryTrans()` and `getName()` removed; `get_admins()` returns `AdminRegistry::all()` |
| `Routing\AdminRouteLoader` throwing "Do not add the loader twice" | stateless: can be called as many times as the router needs |
| `AdminEntityMatcher` result cache | none (a loop over the admins) |
| the services were not `readonly`, helpers had `protected` properties | services are `readonly`; the extension points are the `readonly` classes (`EditAction`, `ListAction`, `FormHelper`, `TemplatingHelper`, `DoctrineHelper`...) |

### Actions

```php
// 5.x: the action was set on the shared service before the call
class PublishAction implements ActionInjectableInterface
{
    use ActionInjectableTrait;

    public function __invoke(Request $request, #[AdminEntity] Post $post): ActionResponseInterface
    {
        $this->action->getAdmin()...;
    }
}

// 6.0: stateless, the action and the admin are arguments
#[AsController]
final readonly class PublishAction
{
    public function __invoke(Request $request, Action $action, Admin $admin, #[AdminEntity] Post $post): ActionResponseInterface
    {
        $admin->getAction('list');
    }
}
```

The built-in actions have the same signatures (`__invoke(Request $request, Action $action, #[AdminEntity] object $data)`,
and `AbstractEmptyFormAction::applyAction(Request $request, Action $action, FormInterface $form, object $data)`).
If you extended one, add the `Action $action` argument, make your class `readonly` and stop calling `setAction()`.
Customizing the query of the list is now `ListAction::customizeHandler()`, called for each request.

### Request-scoped code

```php
// 5.x
$admin = $adminRegistry->getCurrentAdmin();
$action = $admin->getCurrentAction();
// 6.0
$admin = $adminContext->requireAdmin();   // Sidus\AdminBundle\Request\AdminContext
$action = $adminContext->requireAction();
```

In Twig: `current_admin()` and `current_action()` (the action templates also receive `admin` and `action`).

### Security

`LegacyVoter` answered `is_granted('edit', $entityOrClass)` by building a `PermissionCheck` itself. Do it yourself:

```php
// 5.x                                     // 6.0
$checker->isGranted('edit', $post);        $checker->isGranted(null, new PermissionCheck($admin->getAction('edit'), $post));
```

The redirection after a create or a clone now checks the permissions on the saved entity (it used to check the entity
class with the legacy voter).

## Templates

The neutral templates are **not** the Bootstrap-flavoured ones of 5.x: there is no `btn`, `row`, `col-lg-4`, `alert`
class any more, and the datagrid templates of the admin (`@SidusAdmin/DataGrid/bootstrap4.html.twig`,
`@SidusAdmin/Pager/*`) are removed: the datagrid theme is the one of the datagrid bundle.

| 5.x | 6.0 |
|---|---|
| `block title` of `edit.html.twig` (an `<h2>` that clashed with the `<title>` of the layouts) | `heading` (the `<h1>`) with `heading_text`; `title` is the `<title>` of the page |
| form and actions wrapped in `row`/`col-lg-4`, `btn-group pull-right` | `container` > `form` > `form_actions`, classes by the `*_class` variables or your own CSS |
| `message_class`, `submit_class` set before `extends` in `delete.html.twig` | `severity: danger` (`data-severity`, `role="alert"`), still `message_class`, `back_class`, `submit_class` |
| a missing `@SidusAdmin/base.html.twig` | a neutral HTML5 layout with the blocks `title`, `stylesheets`, `body`, `flashes`, `main`, `container`, `javascripts` |
| flash messages left to your layout | rendered by the `flashes` block |
| `@SidusAdmin/Action/list.html.twig` | the same, plus the `<title>` |

To get a styled UI back: enable `sidus_admin.theme: bulma_1` and add `@SidusAdmin/form/bulma_1_layout.html.twig` to
`twig.form_themes` (both opt-in, see the README), or override the blocks of the templates, or give the admin your own
`template_pattern`. The `action_link` icons are off by default and no longer Font Awesome: set
`sidus_admin.action_link.icon_template: '<i class="fas fa-{icon}"></i>'` and `icon: true` to get them back.

If you overrode `title` of `edit.html.twig` to change the heading, override `heading` or `heading_text` instead.

## Translations

The files are now `translations/messages.{en,fr}.yaml`. `messages.fr.yml` shipped the datagrid keys by mistake; it now
translates the admin. The keys of `action_link` labels are `sidus.admin.<admin>.action.<action>.label` and
`sidus.admin.action.<action>.label` (the code looked for `sidus.admin.<action>.label`, which no file ever defined).
New default keys: `sidus.admin.action.{list,read,create,edit,clone,delete}.label`, `sidus.admin.flash.*.success`,
`sidus.admin.action.delete.button.submit`.
A humanized fallback of a capitalized word has no leading space any more.

## Checklist

1. `composer require sidus/admin-bundle:^6.0` with `sidus/datagrid-bundle:^5.0`, migrate your datagrid configuration
   (see its UPGRADE), register the bundles.
2. Remove `admin_class`, `action_class`, `controller` from your configuration; rename the route `options` of the
   actions to `route_options`; check the codes (`[A-Za-z][A-Za-z0-9_]*`).
3. Update your actions (`Action $action` argument, `readonly`), your services that used the current admin or action
   (`AdminContext`), the code that used `AdminRegistry::getAdmin()` and `getAdmins()`.
4. Replace `AdminLinkType` datagrid actions by `options.admin_action`, and `is_granted('edit', $entity)` by a
   `PermissionCheck`.
5. Review your templates against the table above, or enable the Bulma 1 theme.
6. `bin/console cache:clear`: every configuration error is reported there, with the admin and the action.
