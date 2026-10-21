<?php

declare(strict_types=1);

namespace Sidus\AdminBundle\Tests\Unit\Model;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Sidus\AdminBundle\Model\Action;
use Sidus\AdminBundle\Model\ActionLink;
use Sidus\AdminBundle\Model\Admin;
use Sidus\AdminBundle\Model\PermissionCheck;
use Sidus\AdminBundle\Registry\AdminRegistry;
use Sidus\AdminBundle\Request\ActionResponse;
use Sidus\AdminBundle\Request\RedirectActionResponse;
use Sidus\AdminBundle\Tests\Unit\AdminFactory;

final class AdminAndActionTest extends TestCase
{
    public function testActionsReferenceTheirAdmin(): void
    {
        $admin = AdminFactory::admin();

        self::assertSame(['list', 'read'], array_keys($admin->getActions()));
        foreach ($admin->getActions() as $code => $action) {
            self::assertSame($admin, $action->getAdmin());
            self::assertSame($code, $action->getCode());
        }
    }

    public function testUnknownActionListsTheAvailableOnes(): void
    {
        $admin = AdminFactory::admin();

        self::assertTrue($admin->hasAction('list'));
        self::assertFalse($admin->hasAction('nope'));
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage("No action with code 'nope' for admin 'post', available actions: list, read");

        $admin->getAction('nope');
    }

    public function testHasRoute(): void
    {
        $admin = AdminFactory::admin();

        self::assertTrue($admin->hasRoute('sidus_admin.post.list'));
        self::assertTrue($admin->hasRoute('sidus_admin.post.read'));
        self::assertFalse($admin->hasRoute('sidus_admin.post.nope'));
        self::assertFalse($admin->hasRoute('sidus_admin.page.list'));
    }

    public function testCreateRouteUsesThePrefixThePathAndTheReservedDefaults(): void
    {
        $action = AdminFactory::admin(actions: ['read' => [
            'path' => '/{id}',
            'requirements' => ['id' => '\d+'],
            'methods' => ['GET'],
            'host' => 'example.com',
            'schemes' => ['https'],
            'condition' => "request.headers.get('x') == 'y'",
            'routeOptions' => ['utf8' => true],
        ]])->getAction('read');

        $route = $action->createRoute();

        self::assertSame('/posts/{id}', $route->getPath());
        self::assertSame(['_controller' => 'controller', '_admin' => 'post', '_action' => 'read'], $route->getDefaults());
        self::assertSame('\d+', $route->getRequirement('id'));
        self::assertSame(['GET'], $route->getMethods());
        self::assertSame('example.com', $route->getHost());
        self::assertSame(['https'], $route->getSchemes());
        self::assertSame("request.headers.get('x') == 'y'", $route->getCondition());
        self::assertTrue($route->getOption('utf8'));
        self::assertSame('sidus_admin.post.read', $action->getRouteName());
    }

    public function testCreateRouteReturnsANewRouteEachTime(): void
    {
        $action = AdminFactory::admin()->getAction('list');

        $first = $action->createRoute();
        $first->setPath('/mutated');
        $first->setDefault('_controller', 'other');
        $first->setMethods(['DELETE']);
        $first->setOption('x', 1);
        $second = $action->createRoute();

        self::assertNotSame($first, $second);
        self::assertSame('/posts/', $second->getPath());
        self::assertSame('controller', $second->getDefault('_controller'));
        self::assertSame([], $second->getMethods());
        self::assertFalse($second->hasOption('x'));
        self::assertSame('controller', $action->getRouteDefaults()['_controller']);
    }

    public function testRouteVariablesArePathPlaceholders(): void
    {
        $admin = AdminFactory::admin(prefix: '/{_locale}/posts');

        self::assertSame(['_locale'], $admin->getAction('list')->getRouteVariables());
        self::assertSame(['_locale', 'id', 'slug'], $admin->getAction('read')->getRouteVariables());
    }

    /**
     * @return iterable<string, array{class-string}>
     */
    public static function immutableClasses(): iterable
    {
        foreach ([Admin::class, Action::class, AdminRegistry::class, PermissionCheck::class, ActionLink::class, ActionResponse::class, RedirectActionResponse::class] as $class) {
            yield $class => [$class];
        }
    }

    /**
     * @param class-string $class
     */
    #[DataProvider('immutableClasses')]
    public function testClassIsImmutable(string $class): void
    {
        self::assertTrue(new \ReflectionClass($class)->isReadOnly());
        self::assertTrue(new \ReflectionClass($class)->isFinal());
    }

    public function testRedirectWithActionReturnsANewInstance(): void
    {
        $admin = AdminFactory::admin();
        $entity = new \stdClass();
        $original = new RedirectActionResponse($admin->getAction('list'), $entity, ['a' => 1], 2, 301);

        $copy = $original->withAction($admin->getAction('read'));

        self::assertNotSame($original, $copy);
        self::assertSame($admin->getAction('list'), $original->getAction());
        self::assertSame($admin->getAction('read'), $copy->getAction());
        self::assertSame($entity, $copy->getEntity());
        self::assertSame(['a' => 1], $copy->getParameters());
        self::assertSame(2, $copy->getReferenceType());
        self::assertSame(301, $copy->getStatus());
    }
}
