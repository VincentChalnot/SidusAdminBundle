<?php

namespace Sidus\AdminBundle\Configuration;

use Sidus\AdminBundle\Admin\Admin;
use UnexpectedValueException;

/**
 * Keep tracks of all services tagged as admins
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class AdminConfigurationHandler
{
    /** @var Admin[] */
    protected $admins;

    /** @var Admin */
    protected $currentAdmin;

    /**
     * @param Admin $admin
     */
    public function addAdmin(Admin $admin)
    {
        $this->admins[$admin->getCode()] = $admin;
    }

    /**
     * @return Admin[]
     */
    public function getAdmins()
    {
        return $this->admins;
    }

    /**
     * @param string $code
     * @return Admin
     * @throws UnexpectedValueException
     */
    public function getAdmin($code)
    {
        if (empty($this->admins[$code])) {
            throw new UnexpectedValueException("No admin with code: {$code}");
        }

        return $this->admins[$code];
    }

    /**
     * @param string $code
     * @return bool
     * @throws UnexpectedValueException
     */
    public function hasAdmin($code)
    {
        return isset($this->admins[$code]);
    }


    /**
     * @return Admin
     */
    public function getCurrentAdmin()
    {
        return $this->currentAdmin;
    }

    /**
     * @param Admin $currentAdmin
     */
    public function setCurrentAdmin(Admin $currentAdmin)
    {
        $this->currentAdmin = $currentAdmin;
    }
}
