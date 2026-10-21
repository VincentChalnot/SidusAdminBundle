<?php

declare(strict_types=1);

namespace Sidus\AdminBundle\Tests\Fixtures\Security;

use Sidus\AdminBundle\Tests\Fixtures\Entity\Post;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/**
 * What an application adds on top of the roles: an entity-level rule. A locked post is untouchable.
 */
final class PostVoter implements VoterInterface
{
    public const string UNLOCKED = 'POST_UNLOCKED';

    public function vote(TokenInterface $token, mixed $subject, array $attributes, ?Vote $vote = null): int
    {
        if (!\in_array(self::UNLOCKED, $attributes, true)) {
            return self::ACCESS_ABSTAIN;
        }
        // Only a real post can be locked, the class-level check (e.g. to show the menu) is granted
        if ($subject instanceof Post && Post::LOCKED === $subject->getStatus()) {
            return self::ACCESS_DENIED;
        }

        return self::ACCESS_GRANTED;
    }
}
