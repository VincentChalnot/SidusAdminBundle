Sidus/AdminBundle
================

The missing link between the controllers, the router component, the forms and the entities.

Example in twig:
```twig
<a href="{{ admin_path('post', 'list') }}">List</a>
<a href="{{ entity_path(entity, 'edit') }}">Edit</a>
```

## Configuration

Simple example:
```yaml
sidus_admin:
    configurations:
        post:
            controller: 'MyBundle:Post' # Controller reference, like in Symfony routing but without the action name
            entity: MyBundle\Entity\Post # Class name of your enity
            prefix: /post # Routing prefix
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
    fallback_template_directory: ~
    configurations:
        <admin_code>:
            controller: ~ # REQUIRED: The controller reference that will be used to generate routing
            prefix: ~ # REQUIRED: Routing prefix for all actions
            entity: ~ # REQUIRED: The fully qualified class name of the entity (or the Doctrine's shorter reference)
            action_class: # Defaults to main action_class
            base_template: ~ # Can be used in your template (in extends for eg.), not used by this bundle otherwise
            fallback_template_directory: ~ # When template is not found in the controller's directory, uses this folder
            options: {} # You can put anything here
            actions:
                <action_code>: # The action code needs to match the controller's method name without the "Action" suffix
                    form_type: ~ # Useful in combination with AbstractAdminController::getForm($request, $data)
                    form_options: ~ # Static form options
                    template: <controller>:<action_code>.<format>.twig # Computed by the TemplateResolver
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
/** @var $adminRouter \Sidus\AdminBundle\Routing\AdminRouter */
$adminRouter->generateAdminPath('post', 'list');
$adminRouter->generateEntityPath($entity, 'edit');
```

When dealing with multiple admins for a single class, you can use this function instead:
```php
<?php
/** @var $adminRouter \Sidus\AdminBundle\Routing\AdminRouter */
$adminRouter->generateAdminEntityPath('post', $entity, 'edit');
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
/** @var $adminRouter \Sidus\AdminBundle\Routing\AdminRouter */
$adminRouter->generateAdminEntityPath(
    'post',
    $entity,
    'edit',
    ['parametrer' => 'value'],
    \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_PATH
);
```

## Controllers

This bundle provides an (optional) abstract controller to make final controller's action methods less verbose:

```php
<?php

namespace MyBundle\Controller;

use Sidus\AdminBundle\Controller\AbstractAdminController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use MyBundle\Entity\Post;

class PostController extends AbstractAdminController
{
    /**
     * @param Request $request
     * @param Post    $post
     *
     * @return Response
     */
    public function editAction(Request $request, Post $post)
    {
        $form = $this->getForm($request, $post);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->saveEntity($post);

            return $this->redirectToEntity($post, 'edit');
        }

        return $this->renderAction(
            [
                'form' => $form->createView(),
                'post' => $post,
                'admin' => $this->admin,
            ]
        );
    }
}
```

If you don't want the whole AbstractAdminController thing, you can just implements the AdminInjectableInterface to have
the admin object associated to your request injected at runtime:

```php
<?php

namespace MyBundle\Controller;

use Sidus\AdminBundle\Controller\AdminInjectableInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sidus\AdminBundle\Admin\Admin;

class PostController extends Controller implements AdminInjectableInterface
{
    /** @var Admin */
    protected $admin;

    /**
     * @param Admin $admin
     */
    public function setAdmin(Admin $admin)
    {
        $this->admin = $admin;
    }
       
    // Your code
}
```
