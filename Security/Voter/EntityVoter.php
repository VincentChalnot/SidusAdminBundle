<?php
/*
 * This file is part of the Sidus/AdminBundle package.
 *
 * Copyright (c) 2015-2023 Vincent Chalnot
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Sidus\AdminBundle\Security\Voter;

use Sidus\AdminBundle\Model\Action;
use Sidus\AdminBundle\Model\PermissionCheck;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\CacheableVoterInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class EntityVoter implements CacheableVoterInterface
{
    public function __construct(
        protected readonly AccessDecisionManagerInterface $accessDecisionManager,
    ) {
    }

    public function supportsAttribute(string $attribute): bool
    {
        return true;
    }

    public function supportsType(string $subjectType): bool
    {
        return $subjectType === PermissionCheck::class;
    }

    public function vote(TokenInterface $token, mixed $subject, array $attributes): int
    {
        if (!$subject instanceof PermissionCheck) {
            return self::ACCESS_ABSTAIN;
        }
        $voteSubject = $subject->entity ?? $subject->action->getAdmin()->getEntity();

        return $this->doVote($token, $subject->action, $voteSubject);
    }

    protected function doVote(
        TokenInterface $token,
        Action $action,
        object|string|null $subject
    ): int {
        foreach ($action->getAdmin()->getPermissions() as $permission) {
            if (!$this->accessDecisionManager->decide($token, [$permission], $subject)) {
                return VoterInterface::ACCESS_DENIED;
            }
        }
        foreach ($action->getPermissions() as $permission) {
            if (!$this->accessDecisionManager->decide($token, [$permission], $subject)) {
                return VoterInterface::ACCESS_DENIED;
            }
        }

        return VoterInterface::ACCESS_GRANTED;
    }
}
