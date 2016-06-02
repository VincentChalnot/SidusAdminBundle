<?php

namespace Sidus\AdminBundle\Entity;

use Sidus\AdminBundle\Admin\Admin;
use Sidus\AdminBundle\Configuration\AdminConfigurationHandler;

/**
 * Used to match an admin against a Doctrine entity, will return the first one matching
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class AdminEntityMatcher
{
    /** @var AdminConfigurationHandler */
    protected $adminConfigurationHandler;

    /**
     * AdminEntityMatcher constructor.
     * @param AdminConfigurationHandler $adminConfigurationHandler
     */
    public function __construct(AdminConfigurationHandler $adminConfigurationHandler)
    {
        $this->adminConfigurationHandler = $adminConfigurationHandler;
    }

    /**
     * @param mixed $entity
     * @return Admin
     * @throws \UnexpectedValueException
     */
    public function getAdminForEntity($entity)
    {
        foreach ($this->adminConfigurationHandler->getAdmins() as $admin) {
            if (is_a($entity, $admin->getEntity())) {
                return $admin;
            }
        }
        $class = get_class($entity);
        throw new \UnexpectedValueException("No admin matching for entity {$class}");
    }
}
