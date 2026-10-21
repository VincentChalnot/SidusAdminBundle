<?php
declare(strict_types=1);

namespace Sidus\AdminBundle\Security;

use Sidus\AdminBundle\Configuration\AdminRegistry;
use Sidus\AdminBundle\Model\PermissionCheck;

class PermissionCheckerHelper
{
    public function __construct(
        private readonly AdminRegistry $adminRegistry,
    ) {
    }

    public function createCheck(string $adminCode, string $actionCode): PermissionCheck
    {
        $admin = $this->adminRegistry->getAdmin($adminCode);

        return new PermissionCheck($admin->getAction($actionCode));
    }
}
