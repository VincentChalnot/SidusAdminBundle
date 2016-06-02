<?php

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
