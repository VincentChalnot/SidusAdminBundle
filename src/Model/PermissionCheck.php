<?php

declare(strict_types=1);

namespace Sidus\AdminBundle\Model;

/**
 * The subject to pass to the authorization checker to ask "may the current user run this action (on this entity)?":
 *
 *     $authorizationChecker->isGranted(null, new PermissionCheck($action, $entity));
 *
 * Without entity, the permissions are checked against the entity class of the admin.
 */
final readonly class PermissionCheck
{
    public function __construct(
        public Action $action,
        public ?object $entity = null,
    ) {
    }
}
