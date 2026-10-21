<?php

declare(strict_types=1);

namespace Sidus\AdminBundle\Tests\Unit;

use Sidus\AdminBundle\Model\Admin;
use Sidus\AdminBundle\Tests\Fixtures\Entity\Post;

/**
 * Builds admins directly, without any kernel.
 */
final class AdminFactory
{
    /**
     * @param array<string, array<string, mixed>> $actions action code => overrides of the named arguments of Action
     * @param list<string>                        $permissions
     * @param class-string                        $entity
     */
    public static function admin(
        string $code = 'post',
        string $prefix = '/posts',
        array $actions = ['list' => ['path' => '/'], 'read' => ['path' => '/{id}/{slug}']],
        array $permissions = [],
        string $entity = Post::class,
    ): Admin {
        $built = [];
        foreach ($actions as $actionCode => $action) {
            $built[$actionCode] = $action + [
                'path' => '/',
                'defaults' => ['_controller' => 'controller'],
                'requirements' => [],
                'routeOptions' => [],
                'host' => '',
                'schemes' => [],
                'methods' => [],
                'condition' => null,
                'formType' => null,
                'formOptions' => [],
                'template' => null,
                'templateParameters' => [],
                'baseTemplate' => 'base.html.twig',
                'permissions' => [],
                'options' => [],
            ];
        }

        return new Admin(
            code: $code,
            entity: $entity,
            prefix: $prefix,
            formType: null,
            baseTemplate: 'base.html.twig',
            controllerPattern: [],
            templatePattern: [],
            options: [],
            permissions: $permissions,
            actions: $built,
        );
    }
}
