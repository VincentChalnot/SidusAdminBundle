<?php

namespace Sidus\AdminBundle\Twig;

use Sidus\AdminBundle\Configuration\AdminConfigurationHandler;
use Sidus\AdminBundle\Entity\AdminEntityMatcher;
use Sidus\AdminBundle\Routing\AdminRouter;
use Twig_Extension;
use Twig_SimpleFunction;

class AdminExtension extends Twig_Extension
{
    /** @var AdminConfigurationHandler */
    protected $adminConfigurationHandler;

    /** @var AdminEntityMatcher */
    protected $adminEntityMatcher;

    /** @var AdminRouter */
    protected $adminRouter;

    /**
     * @param AdminConfigurationHandler $adminConfigurationHandler
     * @param AdminEntityMatcher $adminEntityMatcher
     * @param AdminRouter $adminRouter
     */
    public function __construct(AdminConfigurationHandler $adminConfigurationHandler, AdminEntityMatcher $adminEntityMatcher, AdminRouter $adminRouter)
    {
        $this->adminConfigurationHandler = $adminConfigurationHandler;
        $this->adminEntityMatcher = $adminEntityMatcher;
        $this->adminRouter = $adminRouter;
    }

    /**
     * @return array
     */
    public function getFunctions()
    {
        return [
            new Twig_SimpleFunction('admin_path', [$this->adminRouter, 'generateAdminPath']),
            new Twig_SimpleFunction('entity_path', [$this->adminRouter, 'generateEntityPath']),
            new Twig_SimpleFunction('entity_admin', [$this->adminEntityMatcher, 'getAdminForEntity']),
            new Twig_SimpleFunction('admin', [$this->adminConfigurationHandler, 'getAdmin']),
        ];
    }

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'sidus_admin';
    }
}
