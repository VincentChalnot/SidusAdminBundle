<?php

declare(strict_types=1);

namespace Sidus\AdminBundle\Tests\Unit\Entity;

use PHPUnit\Framework\TestCase;
use Sidus\AdminBundle\Entity\AdminEntityMatcher;
use Sidus\AdminBundle\Registry\AdminRegistry;
use Sidus\AdminBundle\Tests\Fixtures\Entity\Post;
use Sidus\AdminBundle\Tests\Fixtures\Entity\Tag;
use Sidus\AdminBundle\Tests\Unit\AdminFactory;

final class AdminEntityMatcherTest extends TestCase
{
    public function testFirstMatchingAdminInConfigurationOrderWins(): void
    {
        $generic = AdminFactory::admin('generic', '/generic', entity: \stdClass::class);
        $post = AdminFactory::admin('post', entity: Post::class);
        $other = AdminFactory::admin('other', '/other', entity: Post::class);
        $matcher = new AdminEntityMatcher(new AdminRegistry(['post' => $post, 'other' => $other, 'generic' => $generic]));

        self::assertSame($post, $matcher->getAdminForClass(Post::class));
        self::assertSame($post, $matcher->getAdminForEntity(new Post()));
    }

    public function testSubclassesAndInterfacesMatch(): void
    {
        $post = AdminFactory::admin('post', entity: Post::class);
        $countable = AdminFactory::admin('countable', '/c', entity: \Countable::class);
        $matcher = new AdminEntityMatcher(new AdminRegistry(['post' => $post, 'countable' => $countable]));
        $subclass = new class extends Post {
        };

        self::assertSame($post, $matcher->getAdminForEntity($subclass));
        self::assertSame($countable, $matcher->getAdminForClass(\ArrayObject::class));
    }

    public function testUnknownEntityThrows(): void
    {
        $matcher = new AdminEntityMatcher(new AdminRegistry(['post' => AdminFactory::admin('post', entity: Post::class)]));

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage("No admin matching for entity '".Tag::class."'");

        $matcher->getAdminForEntity(new Tag());
    }
}
