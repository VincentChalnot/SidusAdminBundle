<?php

declare(strict_types=1);

namespace Sidus\AdminBundle\Tests\Unit\Registry;

use PHPUnit\Framework\TestCase;
use Sidus\AdminBundle\Registry\AdminRegistry;
use Sidus\AdminBundle\Tests\Unit\AdminFactory;

final class AdminRegistryTest extends TestCase
{
    public function testGetHasAndAll(): void
    {
        $post = AdminFactory::admin('post');
        $tag = AdminFactory::admin('tag', '/tags');
        $registry = new AdminRegistry(['post' => $post, 'tag' => $tag]);

        self::assertSame($post, $registry->get('post'));
        self::assertSame($tag, $registry->get('tag'));
        self::assertTrue($registry->has('tag'));
        self::assertFalse($registry->has('page'));
        self::assertSame(['post' => $post, 'tag' => $tag], $registry->all());
    }

    public function testUnknownCodeListsTheAvailableAdmins(): void
    {
        $registry = new AdminRegistry(['post' => AdminFactory::admin('post'), 'tag' => AdminFactory::admin('tag', '/tags')]);

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage("No admin with code 'page', available admins: post, tag");

        $registry->get('page');
    }
}
