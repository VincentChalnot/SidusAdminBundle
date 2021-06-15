<?php
/*
 * This file is part of the Sidus/AdminBundle package.
 *
 * Copyright (c) 2015-2021 Vincent Chalnot
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Sidus\AdminBundle\Entity;

use Sidus\AdminBundle\Admin\Admin;
use Sidus\AdminBundle\Configuration\AdminRegistry;
use UnexpectedValueException;
use function get_class;

/**
 * Used to match an admin against a Doctrine entity, will return the first one matching
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class AdminEntityMatcher
{
    protected array $cache = [];

    public function __construct(protected AdminRegistry $adminRegistry)
    {
    }

    public function getAdminForEntity(object $entity): Admin
    {
        $class = get_class($entity);

        if (array_key_exists($class, $this->cache)) {
            return $this->cache[$class];
        }

        foreach ($this->adminRegistry->getAdmins() as $admin) {
            if (is_a($entity, $admin->getEntity())) {
                $this->cache[$class] = $admin;

                return $admin;
            }
        }

        throw new UnexpectedValueException("No admin matching for entity '{$class}'");
    }
}
