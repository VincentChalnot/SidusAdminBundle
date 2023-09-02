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

use Sidus\AdminBundle\Entity\AdminEntityMatcher;
use Sidus\AdminBundle\Model\PermissionCheck;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\CacheableVoterInterface;

class LegacyVoter implements CacheableVoterInterface
{
    public function __construct(
        protected readonly AdminEntityMatcher $adminEntityMatcher,
        protected readonly AccessDecisionManagerInterface $accessDecisionManager,
    ) {
    }

    public function supportsAttribute(string $attribute): bool
    {
        return !str_starts_with($attribute, 'ROLE_');
    }

    public function supportsType(string $subjectType): bool
    {
        if ('string' === $subjectType) {
            return true;
        }
        try {
            return (bool) $this->adminEntityMatcher->getAdminForClass($subjectType);
        } catch (\Exception) {
            return false;
        }
    }

    public function vote(TokenInterface $token, mixed $subject, array $attributes): int
    {
        // Trigger deprecation error:
        trigger_deprecation(
            'sidus/admin-bundle',
            '5.0',
            'Using is_granted() with an action code and a entity is deprecated, use is_granted on a PermissionCheck instead'
        );
        try {
            if (is_string($subject)) {
                $admin = $this->adminEntityMatcher->getAdminForClass($subject);
            } else {
                $admin = $this->adminEntityMatcher->getAdminForEntity($subject);
            }
        } catch (\UnexpectedValueException) {
            return self::ACCESS_ABSTAIN;
        }

        foreach ($attributes as $attribute) {
            $voteSubject = new PermissionCheck(
                $admin->getAction($attribute),
                is_object($subject) ? $subject : null,
            );

            $result = $this->accessDecisionManager->decide($token, [], $voteSubject);
            if (!$result) {
                return self::ACCESS_DENIED;
            }
        }

        return self::ACCESS_GRANTED;
    }
}
