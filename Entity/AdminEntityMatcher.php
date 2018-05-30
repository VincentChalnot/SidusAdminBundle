<?php
/*
 * This file is part of the Sidus/AdminBundle package.
 *
 * Copyright (c) 2015-2018 Vincent Chalnot
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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

    /** @var array */
    protected $cache = [];

    /**
     * AdminEntityMatcher constructor.
     *
     * @param AdminConfigurationHandler $adminConfigurationHandler
     */
    public function __construct(AdminConfigurationHandler $adminConfigurationHandler)
    {
        $this->adminConfigurationHandler = $adminConfigurationHandler;
    }

    /**
     * @param mixed $entity
     *
     * @throws \UnexpectedValueException
     *
     * @return Admin
     */
    public function getAdminForEntity($entity)
    {
        $class = get_class($entity);

        if (array_key_exists($class, $this->cache)) {
            return $this->cache[$class];
        }

        foreach ($this->adminConfigurationHandler->getAdmins() as $admin) {
            if (is_a($entity, $admin->getEntity())) {
                $this->cache[$class] = $admin;

                return $admin;
            }
        }

        throw new \UnexpectedValueException("No admin matching for entity '{$class}'");
    }
}
