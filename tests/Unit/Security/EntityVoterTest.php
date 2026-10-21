<?php

declare(strict_types=1);

namespace Sidus\AdminBundle\Tests\Unit\Security;

use PHPUnit\Framework\TestCase;
use Sidus\AdminBundle\Model\PermissionCheck;
use Sidus\AdminBundle\Security\Voter\EntityVoter;
use Sidus\AdminBundle\Tests\Fixtures\Entity\Post;
use Sidus\AdminBundle\Tests\Unit\AdminFactory;
use Symfony\Component\Security\Core\Authentication\Token\NullToken;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

final class EntityVoterTest extends TestCase
{
    /**
     * A decision manager that records the questions asked and grants the permissions not in $denied.
     *
     * @param list<string> $denied
     */
    private static function manager(array $denied = []): object
    {
        return new class($denied) implements AccessDecisionManagerInterface {
            /** @var list<array{list<string>, mixed}> */
            public array $asked = [];

            /** @param list<string> $denied */
            public function __construct(private readonly array $denied)
            {
            }

            public function decide(\Symfony\Component\Security\Core\Authentication\Token\TokenInterface $token, array $attributes, mixed $object = null, bool|\Symfony\Component\Security\Core\Authorization\AccessDecision|null $accessDecision = null): bool
            {
                $this->asked[] = [$attributes, $object];

                return [] === array_intersect($attributes, $this->denied);
            }
        };
    }

    public function testAbstainsOnOtherSubjects(): void
    {
        $voter = new EntityVoter(self::manager());

        self::assertSame(VoterInterface::ACCESS_ABSTAIN, $voter->vote(new NullToken(), new Post(), ['ROLE_X']));
        self::assertSame(VoterInterface::ACCESS_ABSTAIN, $voter->vote(new NullToken(), null, ['ROLE_X']));
        self::assertSame(VoterInterface::ACCESS_ABSTAIN, $voter->vote(new NullToken(), Post::class, ['ROLE_X']));
    }

    public function testGrantsWhenThereIsNoPermission(): void
    {
        $manager = self::manager();
        $action = AdminFactory::admin()->getAction('list');

        self::assertSame(VoterInterface::ACCESS_GRANTED, new EntityVoter($manager)->vote(new NullToken(), new PermissionCheck($action), []));
        self::assertSame([], $manager->asked);
    }

    public function testGrantsWhenAllPermissionsAreGranted(): void
    {
        $manager = self::manager();
        $admin = AdminFactory::admin(actions: ['list' => ['path' => '/', 'permissions' => ['A_ACTION']]], permissions: ['A_ADMIN']);

        $result = new EntityVoter($manager)->vote(new NullToken(), new PermissionCheck($admin->getAction('list')), []);

        self::assertSame(VoterInterface::ACCESS_GRANTED, $result);
        self::assertSame([['A_ADMIN'], Post::class], $manager->asked[0]);
        self::assertSame([['A_ACTION'], Post::class], $manager->asked[1]);
    }

    public function testDeniesWhenAnAdminPermissionIsDenied(): void
    {
        $admin = AdminFactory::admin(actions: ['list' => ['path' => '/', 'permissions' => ['A_ACTION']]], permissions: ['A_ADMIN']);

        $result = new EntityVoter(self::manager(['A_ADMIN']))->vote(new NullToken(), new PermissionCheck($admin->getAction('list')), []);

        self::assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testDeniesWhenAnActionPermissionIsDenied(): void
    {
        $admin = AdminFactory::admin(actions: ['list' => ['path' => '/', 'permissions' => ['A_ACTION', 'B_ACTION']]], permissions: ['A_ADMIN']);

        $result = new EntityVoter(self::manager(['B_ACTION']))->vote(new NullToken(), new PermissionCheck($admin->getAction('list')), []);

        self::assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testTheSubjectIsTheEntityWhenThereIsOne(): void
    {
        $manager = self::manager();
        $admin = AdminFactory::admin(permissions: ['A_ADMIN']);
        $post = new Post();

        new EntityVoter($manager)->vote(new NullToken(), new PermissionCheck($admin->getAction('list'), $post), []);

        self::assertSame([['A_ADMIN'], $post], $manager->asked[0]);
    }

    public function testTheSubjectIsTheEntityClassWithoutEntity(): void
    {
        $manager = self::manager();
        $admin = AdminFactory::admin(permissions: ['A_ADMIN']);

        new EntityVoter($manager)->vote(new NullToken(), new PermissionCheck($admin->getAction('list')), []);

        self::assertSame([['A_ADMIN'], Post::class], $manager->asked[0]);
    }
}
