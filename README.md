Sidus/AdminBundle
================

The missing link between the controllers, the router component, the forms and the entities.

Example in twig:
```twig
<a href="{{ admin_path('post', 'list') }}">List</a>
<a href="{{ entity_path(entity, 'edit') }}">Edit</a>
```

## Warning

This bundle requires the security component of Symfony, it will enforce access to any entity using the voter.
To ease the configuration of this, we recommend the ````cleverage/permission-bundle````.
Also, by a design mistake, this bundle is tightly linked to the use of Doctrine, it can still be used for non-doctrine
entities but unplugging Doctrine from dependencies will require some extra work.

## Configuration

Simple example:
```yaml
sidus_admin:
    configurations:
        post:
            entity: MyBundle\Entity\Post # Class name of your entity
            prefix: /post # Routing prefix
            controller_pattern:
                - 'Sidus\AdminBundle\Action\{{Action}}Action' # Full controller reference
            template_pattern:
                - '@SidusAdmin/Action/{{action}}.{{format}}.twig'
            actions:
                list:
                    path:     /list # Routing path
                edit:
                    path:     /edit/{id}
                    form_type: MyBundle\Form\Type\EditPostType # Form type to use in controller
```

### Configuration reference

All values are the default one except when specified otherwise.

```yaml
sidus_admin:
    admin_class: Sidus\AdminBundle\Admin\Admin
    action_class: Sidus\AdminBundle\Admin\Action
    configurations:
        <admin_code>:
            entity: ~ # REQUIRED: The fully qualified class name of the entity (or the Doctrine's shorter reference)
            prefix: ~ # REQUIRED: Routing prefix for all actions
            controller_pattern: [] # The controller reference that will be used to generate routing
            # Available interpolation variables are:
            # {{admin}} lowercase first letter admin code
            # {{Admin}} uppercase first letter admin code
            # {{action}} lowercase first letter action code
            # {{Action}} uppercase first letter action code
            # If you don't set any controller_pattern you will need to set the _controller attribute in the defaults of
            # each action.
            template_pattern: [] # The template pattern
            action_class: # Defaults to main action_class
            options: {} # You can put anything here
            actions:
                <action_code>: # The action code needs to match the controller's method name without the "Action" suffix
                    form_type: ~ # Useful in combination with AbstractAdminController::getForm($request, $data)
                    form_options: ~ # Static form options
                    template: ~ # Computed by the TemplateResolver using template_pattern if null
                    # All the following options are used to generate the route for the routing component
                    # See Symfony doc here: http://symfony.com/doc/current/routing.html
                    path: ~ # REQUIRED
                    defaults: ~
                    requirements: ~
                    options: ~
                    host: ~
                    schemes: ~
                    methods: ~
                    condition: ~
```

## Usage

### Generating routes

When routing to an entity, the AdminRouter component will try to fetch missing route parameters from the routing context
and then from the entity itself, meaning if you name your route parameters accordingly to your entity properties, you
won't need to pass any parameter manually.

#### PHP
```php
<?php
/** @var $adminRouter AdminRouter */
use Sidus\AdminBundle\Routing\AdminRouter;$adminRouter->generateAdminPath('post', 'list');
$adminRouter->generateEntityPath($entity, 'edit');
```

When dealing with multiple admins for a single class, you can use this function instead:
```php
<?php
/** @var $adminRouter AdminRouter */
use Sidus\AdminBundle\Routing\AdminRouter;$adminRouter->generateAdminEntityPath('post', $entity, 'edit');
```

#### Twig
```twig
<a href="{{ admin_path('post', 'list') }}">List</a>
<a href="{{ entity_path(entity, 'edit') }}">Edit</a>
```

When dealing with multiple admins for a single class, you can use this function instead:

```twig
<a href="{{ admin_entity_path('post', entity, 'edit') }}">Edit</a>
```

#### Additional optional arguments

For each method, you can pass additional route parameters in the argument just after the action name, you can also set
the UrlGeneratorInterface reference type (absolute, relative...).
```php
<?php
/** @var $adminRouter AdminRouter */
use Sidus\AdminBundle\Routing\AdminRouter;use Symfony\Component\Routing\Generator\UrlGeneratorInterface;$adminRouter->generateAdminEntityPath(
    'post',
    $entity,
    'edit',
    ['parametrer' => 'value'],
    UrlGeneratorInterface::ABSOLUTE_PATH
);
```

