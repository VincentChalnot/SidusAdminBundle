<?php

declare(strict_types=1);

namespace Sidus\AdminBundle\Entity;

use Sidus\AdminBundle\Model\Admin;
use Sidus\AdminBundle\Registry\AdminRegistry;
use UnexpectedValueException;

/**
 * Used to match an admin against an entity, will return the first one (in configuration order) matching
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
final readonly class AdminEntityMatcher
{
    public function __construct(private AdminRegistry $adminRegistry)
    {
    }

    public function getAdminForEntity(object $entity): Admin
    {
        return $this->getAdminForClass($entity::class);
    }

    public function getAdminForClass(string $class): Admin
    {
        foreach ($this->adminRegistry->all() as $admin) {
            if (is_a($class, $admin->getEntity(), true)) {
                return $admin;
            }
        }

        throw new UnexpectedValueException("No admin matching for entity '{$class}'");
    }
}
