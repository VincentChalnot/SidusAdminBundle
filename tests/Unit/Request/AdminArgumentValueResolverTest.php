<?php

declare(strict_types=1);

namespace Sidus\AdminBundle\Tests\Unit\Request;

use PHPUnit\Framework\TestCase;
use Sidus\AdminBundle\Model\Action;
use Sidus\AdminBundle\Model\Admin;
use Sidus\AdminBundle\Request\AdminArgumentValueResolver;
use Sidus\AdminBundle\Tests\Unit\AdminFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

final class AdminArgumentValueResolverTest extends TestCase
{
    public function testResolvesTheActionAndTheAdmin(): void
    {
        $admin = AdminFactory::admin();
        $action = $admin->getAction('read');
        $request = new Request(attributes: ['_admin' => $admin, '_action' => $action, '_route' => 'sidus_admin.post.read']);
        $resolver = new AdminArgumentValueResolver();

        self::assertSame([$action], $resolver->resolve($request, new ArgumentMetadata('action', Action::class, false, false, null)));
        self::assertSame([$admin], $resolver->resolve($request, new ArgumentMetadata('admin', Admin::class, false, false, null)));
    }

    public function testOtherTypesYieldNothing(): void
    {
        $admin = AdminFactory::admin();
        $request = new Request(attributes: ['_admin' => $admin, '_action' => $admin->getAction('list')]);
        $resolver = new AdminArgumentValueResolver();

        foreach ([Request::class, 'string', null, \stdClass::class] as $type) {
            self::assertSame([], [...$resolver->resolve($request, new ArgumentMetadata('x', $type, false, false, null))]);
        }
    }

    public function testMissingAttributeNamesTheRoute(): void
    {
        $request = new Request(attributes: ['_route' => 'app_custom']);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage("the request attribute '_action' of the route 'app_custom' is missing");

        new AdminArgumentValueResolver()->resolve($request, new ArgumentMetadata('action', Action::class, false, false, null));
    }

    public function testMissingAdminAttributeNamesTheRoute(): void
    {
        $request = new Request(attributes: ['_route' => 'app_custom']);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage("the request attribute '_admin' of the route 'app_custom' is missing");

        new AdminArgumentValueResolver()->resolve($request, new ArgumentMetadata('admin', Admin::class, false, false, null));
    }

    public function testWrongTypedAttributeIsRejected(): void
    {
        $request = new Request(attributes: ['_route' => 'app_custom', '_action' => 'list']);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage("the request attribute '_action' of the route 'app_custom' is not an ".Action::class);

        new AdminArgumentValueResolver()->resolve($request, new ArgumentMetadata('action', Action::class, false, false, null));
    }
}
