<?php

declare(strict_types=1);

namespace Sidus\AdminBundle\Security\Voter;

use Sidus\AdminBundle\Model\Action;
use Sidus\AdminBundle\Model\PermissionCheck;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\CacheableVoterInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/**
 * Decides on {@see PermissionCheck} subjects: every permission of the admin and of the action must be granted on the
 * entity (or on the entity class when the check has no entity).
 */
final readonly class EntityVoter implements CacheableVoterInterface
{
    public function __construct(
        private AccessDecisionManagerInterface $accessDecisionManager,
    ) {
    }

    public function supportsAttribute(string $attribute): bool
    {
        return true;
    }

    public function supportsType(string $subjectType): bool
    {
        return PermissionCheck::class === $subjectType;
    }

    public function vote(TokenInterface $token, mixed $subject, array $attributes, ?Vote $vote = null): int
    {
        if (!$subject instanceof PermissionCheck) {
            return self::ACCESS_ABSTAIN;
        }
        $voteSubject = $subject->entity ?? $subject->action->getAdmin()->getEntity();

        return $this->doVote($token, $subject->action, $voteSubject);
    }

    private function doVote(TokenInterface $token, Action $action, object|string $subject): int
    {
        foreach ([...$action->getAdmin()->getPermissions(), ...$action->getPermissions()] as $permission) {
            if (!$this->accessDecisionManager->decide($token, [$permission], $subject)) {
                return VoterInterface::ACCESS_DENIED;
            }
        }

        return VoterInterface::ACCESS_GRANTED;
    }
}
