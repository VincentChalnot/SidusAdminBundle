<?php

declare(strict_types=1);

namespace Sidus\AdminBundle\Tests\Unit\Request;

use PHPUnit\Framework\TestCase;
use Sidus\AdminBundle\Request\AdminContext;
use Sidus\AdminBundle\Tests\Unit\AdminFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

final class AdminContextTest extends TestCase
{
    public function testWithoutRequestThereIsNothing(): void
    {
        $context = new AdminContext(new RequestStack());

        self::assertNull($context->getAdmin());
        self::assertNull($context->getAction());
    }

    public function testRequireAdminThrowsWithoutAdmin(): void
    {
        $context = new AdminContext(new RequestStack());

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('There is no current admin');

        $context->requireAdmin();
    }

    public function testRequireActionThrowsWithoutAction(): void
    {
        $stack = new RequestStack();
        $stack->push(new Request());

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('There is no current action');

        new AdminContext($stack)->requireAction();
    }

    public function testReturnsTheAttributesOfTheCurrentRequest(): void
    {
        $admin = AdminFactory::admin();
        $action = $admin->getAction('read');
        $stack = new RequestStack();
        $stack->push(new Request(attributes: ['_admin' => $admin, '_action' => $action]));
        $context = new AdminContext($stack);

        self::assertSame($admin, $context->getAdmin());
        self::assertSame($admin, $context->requireAdmin());
        self::assertSame($action, $context->getAction());
        self::assertSame($action, $context->requireAction());
    }

    public function testNonObjectAttributesAreIgnored(): void
    {
        $stack = new RequestStack();
        $stack->push(new Request(attributes: ['_admin' => 'post', '_action' => 'list']));
        $context = new AdminContext($stack);

        self::assertNull($context->getAdmin());
        self::assertNull($context->getAction());
    }

    public function testFallsBackToTheMainRequest(): void
    {
        $admin = AdminFactory::admin();
        $stack = new RequestStack();
        $stack->push(new Request(attributes: ['_admin' => $admin, '_action' => $admin->getAction('list')]));
        $stack->push(new Request());
        $context = new AdminContext($stack);

        self::assertSame($admin, $context->getAdmin());
        self::assertSame($admin->getAction('list'), $context->getAction());
    }

    public function testTheCurrentRequestWinsOverTheMainOne(): void
    {
        $main = AdminFactory::admin('post');
        $sub = AdminFactory::admin('tag', '/tags');
        $stack = new RequestStack();
        $stack->push(new Request(attributes: ['_admin' => $main]));
        $stack->push(new Request(attributes: ['_admin' => $sub]));

        self::assertSame($sub, new AdminContext($stack)->getAdmin());
    }

    public function testNothingIsRememberedAfterTheRequestIsPopped(): void
    {
        $admin = AdminFactory::admin();
        $stack = new RequestStack();
        $context = new AdminContext($stack);

        $stack->push(new Request(attributes: ['_admin' => $admin, '_action' => $admin->getAction('list')]));
        self::assertSame($admin, $context->getAdmin());

        $stack->pop();

        self::assertNull($context->getAdmin());
        self::assertNull($context->getAction());

        $stack->push(new Request());
        self::assertNull($context->getAdmin());
    }
}
