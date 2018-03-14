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
    protected $admins = [];

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
    public function getAdmins(): array
    {
        return $this->admins;
    }

    /**
     * @param string $code
     *
     * @throws UnexpectedValueException
     *
     * @return Admin
     */
    public function getAdmin(string $code): Admin
    {
        if (empty($this->admins[$code])) {
            throw new UnexpectedValueException("No admin with code: {$code}");
        }

        return $this->admins[$code];
    }

    /**
     * @param string $code
     *
     * @return bool
     */
    public function hasAdmin(string $code): bool
    {
        return isset($this->admins[$code]);
    }


    /**
     * @return Admin|null
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
