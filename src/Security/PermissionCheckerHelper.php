<?php

declare(strict_types=1);

namespace Sidus\AdminBundle\Security;

use Sidus\AdminBundle\Model\PermissionCheck;
use Sidus\AdminBundle\Registry\AdminRegistry;

final readonly class PermissionCheckerHelper
{
    public function __construct(
        private AdminRegistry $adminRegistry,
    ) {
    }

    public function createCheck(string $adminCode, string $actionCode, ?object $entity = null): PermissionCheck
    {
        return new PermissionCheck($this->adminRegistry->get($adminCode)->getAction($actionCode), $entity);
    }
}
