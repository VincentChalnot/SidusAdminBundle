<?php
declare(strict_types=1);

namespace Sidus\AdminBundle\Model;

class PermissionCheck
{
    public function __construct(
        public readonly Action $action,
        public readonly ?object $entity = null,
    ) {
    }
}
