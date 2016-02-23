<?php

namespace Sidus\AdminBundle\Controller;

use Sidus\AdminBundle\Admin\Admin;

interface AdminInjectable
{
    /**
     * @param Admin $admin
     */
    public function setAdmin(Admin $admin);
}
