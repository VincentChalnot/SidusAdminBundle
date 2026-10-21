Sidus/AdminBundle
=================

The missing link between the controllers, the router component, the forms and the entities: describe an entity once in
the configuration and get its routes, its permissions, its templates and its list.

```twig
<a href="{{ admin_path('post', 'list') }}">List</a>
<a href="{{ entity_path(post, 'edit') }}">Edit</a>
```

Version 6 requires PHP 8.4+, Symfony 8.1+ and [sidus/datagrid-bundle](https://github.com/VincentChalnot/SidusDataGridBundle)
5. Coming from 5.x: read [UPGRADE-6.0.md](UPGRADE-6.0.md).

- [Principles](#principles)
- [Installation](#installation)
- [Quick start](#quick-start)
- [Configuration reference](#configuration-reference)
- [The built-in actions](#the-built-in-actions)
- [Custom actions](#custom-actions)
- [The current admin and action](#the-current-admin-and-action)
- [Routing](#routing)
- [Permissions](#permissions)
- [Lists and datagrids](#lists-and-datagrids)
- [Templates and themes](#templates-and-themes)
- [Translations](#translations)

## Principles

Everything is immutable, nothing survives a request: the bundle is safe in a long-running worker (FrankenPHP,
RoadRunner...).

- The admins and their actions are `final readonly` value objects built **once, when the container is compiled** from
  the configuration. The configuration is validated at that time too: an unknown controller, a duplicated route or a
  missing prefix is an error of `cache:warmup`, never of a request.
- The registry is read-only, the services are stateless. The admin and the action of the request being handled are
  **request attributes** (`_admin` and `_action`): the actions receive them as controller arguments, anything else
  reads them from the request (see [the current admin and action](#the-current-admin-and-action)).
- Everything per-request (a datagrid, its query, its form) is created by a factory for the request and never stored.

## Installation

```bash
composer require sidus/admin-bundle
```

The bundle needs the form, security and twig bundles of Symfony and a datagrid provider for its lists, for instance
Doctrine ORM with `sidus/doctrine-filter-bundle`.

```php
// config/bundles.php
return [
    // ...
    Sidus\FilterBundle\SidusFilterBundle::class => ['all' => true],
    Sidus\DoctrineFilterBundle\SidusDoctrineFilterBundle::class => ['all' => true],
    Sidus\DataGridBundle\SidusDataGridBundle::class => ['all' => true],
    Sidus\AdminBundle\SidusAdminBundle::class => ['all' => true],
];
```

Load the routes of the admins (they are generated from the configuration):

```yaml
# config/routes/admin.yaml
sidus_admin:
    resource: .
    type: sidus_admin
```

The templates (`@SidusAdmin`) and the translations are registered by the bundle. Doctrine is optional: the built-in
read, create, edit, clone and delete actions need Doctrine ORM (they find and save entities with it), nothing else
does; your own actions work with any kind of entity.

## Quick start

```yaml
# config/packages/sidus_admin.yaml
sidus_admin:
    configurations:
        post:
            entity: App\Entity\Post
            prefix: /post
            form_type: App\Form\PostType
            permissions: [ROLE_USER]
            controller_pattern:
                - 'App\Action\{{Action}}Action'          # your own actions first...
                - 'Sidus\AdminBundle\Action\{{Action}}Action' # ...then the built-in ones
            actions:
                list:   { path: / }
                create: { path: /create, permissions: [ROLE_EDITOR] }
                read:   { path: '/{id}', requirements: { id: '\d+' } }
                edit:   { path: '/{id}/edit', requirements: { id: '\d+' }, permissions: [ROLE_EDITOR] }
                delete: { path: '/{id}/delete', requirements: { id: '\d+' }, permissions: [ROLE_ADMIN] }

# config/packages/sidus_data_grid.yaml : the datagrid of the list action is the one of the code of the admin
sidus_data_grid:
    configurations:
        post:
            list:
                provider: doctrine
                options: { entity: App\Entity\Post }
                filters:
                    title: { type: text }
            columns:
                title: { sortable: true }
                status: ~
            actions:
                create: { options: { admin_action: create } } # a link to the create action of the admin, only for those granted
```

That is all: `/post/` lists the posts, `/post/create` creates one, `/post/12/edit` edits it (with a flash message and the
redirections), `/post/12/delete` asks for a confirmation.

## Configuration reference

Values are the defaults. `<admin_code>` and `<action_code>` start with a letter and contain letters, digits and
underscores (they are used in route names and form names).

```yaml
sidus_admin:
    # Opt-in set of action templates: null is neutral, framework-agnostic HTML. Available: bulma_1
    theme: null

    # The layout of all the actions, it must define the "container" block.
    # Default: '@SidusAdmin/base.html.twig' ('@SidusAdmin/theme/bulma_1/base.html.twig' with the theme)
    base_template: null

    # Default options of the action_link() Twig function and of the action_link form type
    action_link:
        icon: false                                               # render an icon with the label
        icon_template: '<span data-icon="{icon}" aria-hidden="true"></span>' # raw HTML, {icon} is the icon name
        class: null                                               # CSS class of the link

    configurations:
        <admin_code>:
            entity: ~   # REQUIRED: class (or interface) name of the entity, it must exist
            prefix: ~   # REQUIRED: routing prefix of all the actions

            # Controllers of the actions that have no _controller: the first reference that exists wins, the check is
            # done when the container is compiled. A reference is a service id or the name of a class without
            # constructor arguments, optionally followed by ::method.
            # Variables: {{admin}} / {{Admin}} the code of the admin with a lowercase / uppercase first letter,
            #            {{action}} / {{Action}} the same for the code of the action
            controller_pattern: []

            # Templates of the actions that have no template, the first one that exists wins.
            # Variables: the ones above and {{format}} (html). Default: ['@SidusAdmin/Action/{{action}}.{{format}}.twig']
            template_pattern: []

            base_template: null   # overrides the global base_template for this admin
            form_type: null       # form type class of the actions that have none
            options: {}           # anything you need, Admin::getOption()
            permissions: []       # security attributes the user needs for ALL the actions of the admin (all of them)

            actions:
                <action_code>:
                    path: ~       # REQUIRED: route path, starts with a slash, appended to the prefix
                    form_type: null        # default: the form type of the admin
                    form_options: {}       # static form options
                    base_template: null    # default: the one of the admin
                    template: null         # a fixed template, instead of the template_pattern
                    template_parameters: {}  # extra variables of the template
                    permissions: []        # on top of the permissions of the admin (all of them)
                    options: {}            # anything you need, Action::getOption(). NOT route options
                    # The route (see https://symfony.com/doc/current/routing.html)
                    defaults: {}           # _controller, or _controller_pattern (overrides the one of the admin)
                    requirements: {}
                    route_options: {}
                    host: ''
                    schemes: []
                    methods: []
                    condition: null
```

Errors name the admin and the action: `Invalid configuration for "sidus_admin.configurations.post.actions.export": no
controller found for the controller_pattern [App\Action\{{Action}}Action], tried: App\Action\ExportAction`.
The following are refused when the container is compiled: a missing `entity` or `prefix`, an entity or form type that is
not a class, an unknown `{{variable}}` in a pattern, an action without any controller, a path without a leading slash,
an invalid route, admin codes that only differ by case, two actions with the same route path (unless their `methods`
are disjoint), the reserved route defaults `_admin` and `_action`.

## The built-in actions

They are services of the bundle, to be referenced by the `controller_pattern` of your admins (they are named after the
code of the action: `Sidus\AdminBundle\Action\{{Action}}Action`).

| Action | What it does | Needs |
|---|---|---|
| `ListAction` | renders the datagrid of the admin | `sidus/datagrid-bundle` |
| `ReadAction` | the form of the entity, disabled | Doctrine ORM |
| `CreateAction` | the form of a new entity, saves it and redirects to its edit (or read, or list) action | Doctrine ORM |
| `EditAction` | the form of the entity, saves it and stays on the page | Doctrine ORM |
| `CloneAction` | the form of a copy of the entity, saves it as a new entity | Doctrine ORM |
| `DeleteAction` | a confirmation form, deletes the entity and redirects to the `redirect_action` option of the action (`list`) | Doctrine ORM |

The entity of an action is found from the route parameters by the `#[AdminEntity]` argument resolver: by identifier
(`/{id}`), else by a unique attribute of the entity (`/{slug}`), else by a unique constraint. An unknown entity is a 404.
Saving and deleting add a flash message (see [translations](#translations)).

## Custom actions

An action is a plain service, with `#[AsController]`, shared by all the admins and all the requests. It is given the
action and the admin it is routed to **as arguments** (never inject them, it has no state), and the entity with
`#[AdminEntity]`:

```php
use Sidus\AdminBundle\Attribute\AdminEntity;
use Sidus\AdminBundle\Model\Action;
use Sidus\AdminBundle\Model\Admin;
use Sidus\AdminBundle\Request\ActionResponseInterface;
use Sidus\AdminBundle\Request\RedirectActionResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
final readonly class PublishAction
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function __invoke(
        Request $request,
        Action $action,   // the configured action: $action->getCode(), ->getOption('redirect_action'), ->getAdmin()...
        Admin $admin,     // optional, the same as $action->getAdmin()
        #[AdminEntity]
        Post $post,       // resolved from the route parameters
    ): ActionResponseInterface {
        $post->publish();
        $this->entityManager->flush();

        return new RedirectActionResponse($admin->getAction($action->getOption('redirect_action', 'list')));
    }
}
```

```yaml
actions:
    publish: { path: '/{id}/publish', methods: [POST], permissions: [ROLE_EDITOR], options: { redirect_action: list } }
```

With `controller_pattern: ['App\Action\{{Action}}Action', 'Sidus\AdminBundle\Action\{{Action}}Action']` the action
`publish` is `App\Action\PublishAction`, and `list`, `edit`... are the built-in ones; the resolution happens when the
container is compiled and ends up in the `_controller` default of the route. Set `defaults: { _controller: ... }` on an
action to choose its controller yourself.

What an action returns (no `Response` needed):

- `RedirectActionResponse($action, $entity = null, $parameters = [])`: a redirection to an admin action (the route
  parameters are read from the entity);
- `ActionResponse($action, $parameters)` or `TemplatingHelper::renderAction()`, `renderFormAction($action, $form, $data)`,
  `renderListAction($action, $datagrid)`: the template of the action is rendered with `action`, `admin`, the template
  parameters of the action and these parameters. Any other value follows the normal Symfony rules.

The helpers `FormHelper`, `TemplatingHelper`, `RoutingHelper`, `DoctrineHelper`, `FlashHelper` and `TranslatorHelper` are
readonly services to build your own actions; the built-in actions are `readonly` classes you can extend (your subclass
must be `readonly` too).

## The current admin and action

The admin and the action of the request are the `_admin` and `_action` request attributes, turned into objects when the
request is received. Do not look for them anywhere else, and never store them.

- **In a controller**: the `Action $action` and `Admin $admin` arguments.
- **In a service**: `Sidus\AdminBundle\Request\AdminContext` (it reads the request stack each time, it keeps nothing):
  `getAdmin()`, `getAction()` (`null` outside of an admin request), `requireAdmin()`, `requireAction()` (throw).
- **In Twig**: the `admin` and `action` variables of the action templates, or `current_admin()` and `current_action()`.

## Routing

Routes are named `sidus_admin.<admin>.<action>`. When routing to an entity, the `AdminRouter` takes the missing route
parameters from the entity (the route `/{id}/edit` and the property `id`), then from the routing context.

```php
$adminRouter->generateAdminPath('post', 'list', ['page' => 2]);
$adminRouter->generateEntityPath($post, 'edit');                // the admin of the entity: the first matching one
$adminRouter->generateAdminEntityPath('post', $post, 'edit', [], UrlGeneratorInterface::ABSOLUTE_URL);
```

```twig
<a href="{{ admin_path('post', 'list') }}">List</a>
<a href="{{ entity_path(post, 'edit') }}">Edit</a>
<a href="{{ admin_entity_path('post', post, 'edit') }}">Edit (when several admins share an entity)</a>
{{ entity_admin(post).code }} {{ admin('post').action('edit').routeName }} {{ get_admins()|keys|join(', ') }}
```

`action_link` renders a link to an action only when the user is granted it (nothing otherwise); the admin is the
current one unless given:

```twig
{{ action_link({action: 'edit', entity: post}) }}
{{ action_link({action: 'delete', admin: 'post', entity: post, label: 'Remove', class: 'danger', icon: true}) }}
{{ action_link_fallback({entity: post}, {action: 'edit'}, {action: 'read'}) }}  {# the first one that is rendered #}
```

The routes themselves are values: `Action::createRoute()` builds a new `Route` each time, `Action::getRouteVariables()`
lists the placeholders of the path.

## Permissions

The permissions of an admin and of an action are security attributes (roles, or the attributes of your own voters),
**all** of them must be granted, on the entity when there is one (a voter can look at it), else on the entity class.
The bundle checks them for you:

- before the controller runs: the permissions of the action, on the entity class;
- once the arguments are resolved: the same on every argument that is an instance of the entity of the admin.

A denied user gets a 403 (or is asked to log in). To ask the same question yourself, use a `PermissionCheck` (the
security attribute is `null`):

```php
$authorizationChecker->isGranted(null, new PermissionCheck($action, $entity));
```
```twig
{% if is_action_granted(admin('post').action('edit'), post) %}...{% endif %}
```

An entity-level rule is an ordinary Symfony voter on the attributes you put in `permissions`.

## Lists and datagrids

`ListAction` creates the datagrid of the request with the `DataGridFactory` of the datagrid bundle and renders it. The
datagrid is, by priority, the `datagrid` option of the action, the `datagrid` option of the admin, the datagrid that has
the code of the admin.

```yaml
sidus_admin:
    configurations:
        post:
            options: { datagrid: posts }      # for all the actions of the admin
            actions:
                list: { path: /, options: { datagrid: published_posts } }   # for this action only
```

**Links to the admin from the datagrid.** A datagrid action with the option `admin_action` is completed by the bundle
(`AdminActionResolver`): its `uri` is the path of that action of the admin, its label the translation of the action,
and it only exists for the users who are granted that action.

```yaml
sidus_data_grid:
    configurations:
        post:
            actions:
                create:
                    options:
                        admin_action: create   # REQUIRED
                        admin: post            # the admin: by code,
                        # entity: App\Entity\Post  # or by entity, else the admin of the current request
                        on_denied: hide        # hide (default: the `hidden` attribute) or disable
                    label: New post            # optional
                    route_parameters: { }      # the route parameters of the admin action
```

**Restricting the query for a request** (the user's tenant, a route parameter...): extend `ListAction`, override
`customizeHandler()` and reference your class in the `controller_pattern`. The handler is the fresh, single-use query
handler of this request:

```php
#[AsController]
final readonly class ListAction extends \Sidus\AdminBundle\Action\ListAction
{
    protected function customizeHandler(QueryHandlerInterface $handler, Request $request, Action $action): QueryHandlerInterface
    {
        return $handler->withCustomizer(static function (DoctrineQueryContext $context): void {
            $context->queryBuilder()->andWhere($context->rootAlias().'.published = true');
        });
    }
}
```

## Templates and themes

Out of the box the pages are **plain semantic HTML with no CSS framework**: the layout is `@SidusAdmin/base.html.twig`,
the actions `@SidusAdmin/Action/{list,read,create,edit,clone,delete}.html.twig`. Style them with your own CSS (the
`sidus-admin-*` classes and the `data-admin`, `data-action`, `data-severity` attributes are the hooks), by overriding
blocks, or by enabling a theme.

- **Your own layout**: `sidus_admin.base_template: base.html.twig`. It only needs a `container` block (the neutral layout is a good
  starting point to extend). The neutral layout has the blocks `title`, `stylesheets`,
  `body`, `flashes` (the flash messages), `main`, `container` and `javascripts`.
- **Your own action templates**: the `template_pattern` of the admin (`['Action/{{action}}.{{format}}.twig',
  '@SidusAdmin/Action/{{action}}.{{format}}.twig']`: your `templates/Action/edit.html.twig` first, the bundle's when you
  have none), or the `template` of an action. Variables: `action`, `admin`, `form`, `data`, `listPath` (the list of the
  admin, or `null`), `datagrid` and the `template_parameters` of the action.
- **The blocks of the form templates** (`edit`, which `create`, `clone`, `read` and `delete` extend): `container`,
  `heading` (`heading_text`), `form`, `message`, `form_actions`, `back_button`, `submit_button`. The variables
  `form_container_class`, `message_class`, `back_class` and `submit_class`, when defined, are the CSS classes of the
  elements.
- The datagrid has its **own theme** (`theme` of the datagrid, see the datagrid bundle), it is not the business of the
  admin.

### Opt-in theme: Bulma 1

Two independent switches, none of them is ever activated for you. Load Bulma yourself (the bundle never links a
stylesheet).

```yaml
sidus_admin:
    theme: bulma_1          # layout and action templates of @SidusAdmin/theme/bulma_1/

twig:
    form_themes:
        - '@SidusAdmin/form/bulma_1_layout.html.twig'   # the Symfony form layout: fields, errors, buttons...
```

With `theme: bulma_1` every `@SidusAdmin/...` entry of the `template_pattern` is first looked up under
`@SidusAdmin/theme/bulma_1/...`, then as it is (an action the theme does not restyle, like the list, stays neutral), and
the default base template is the one of the theme. A `base_template` you set always wins. The form layout covers the
usual form types (text-like inputs, textarea, choices as select, radios and checkboxes, checkbox, date and time, file,
collection, buttons, help and errors) and can be used for any form of your application. There is no Bootstrap layout.

## Translations

The bundle ships `en` and `fr`. Each text is looked up from the most to the least specific key, then falls back to the
humanized codes. `<admin>` and `<action>` are the codes.

| Text | Keys |
|---|---|
| Heading and `<title>` | `sidus.admin.<admin>.action.<action>.title`, `sidus.admin.action.<action>.title`; parameter `%entity%` |
| Message above the form | `sidus.admin.<admin>.action.<action>.message`, `sidus.admin.action.<action>.message`; parameter `%entity%` |
| Submit and back buttons | `sidus.admin.<admin>.action.<action>.button.submit` / `.back`, `sidus.admin.action.<action>.button.submit` / `.back`, `sidus.admin.button.submit` / `.back` |
| Label of a link to an action | `sidus.admin.<admin>.action.<action>.label`, `sidus.admin.action.<action>.label` |
| Flash message after a save or a delete | `sidus.admin.<admin>.<action>.success`, `sidus.admin.flash.<action>.success` |

## Testing your admins

An `Admin` is a plain immutable object: build one in a unit test with its named constructor arguments (the `$actions`
argument is the configuration of the actions, see `tests/Unit/AdminFactory.php`). For functional tests, `tests/App/TestKernel.php`
shows a complete kernel (Doctrine on SQLite, a firewall with HTTP basic authentication, the datagrid and admin bundles)
and `tests/Functional/WorkerModeTest.php` how to replay many requests on one booted kernel to prove that nothing leaks
from a request to the next.
