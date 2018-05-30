<?php
/*
 * This file is part of the Sidus/AdminBundle package.
 *
 * Copyright (c) 2015-2018 Vincent Chalnot
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sidus\AdminBundle\Controller;

use Sidus\AdminBundle\Admin\Admin;

/**
 * If a controller implements this interface, it will be injected with it's current matching admin
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
interface AdminInjectableInterface
{
    /**
     * @param Admin $admin
     */
    public function setAdmin(Admin $admin);
}
