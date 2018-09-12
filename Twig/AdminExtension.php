<?php
/*
 * This file is part of the Sidus/AdminBundle package.
 *
 * Copyright (c) 2015-2018 Vincent Chalnot
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sidus\AdminBundle\Twig;

use Sidus\AdminBundle\Admin\Admin;
use Sidus\AdminBundle\Configuration\AdminRegistry;
use Sidus\AdminBundle\Entity\AdminEntityMatcher;
use Sidus\AdminBundle\Routing\AdminRouter;
use Twig_Extension;
use Twig_SimpleFunction;
use UnexpectedValueException;

/**
 * Adds a few useful routing functions to twig templates
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class AdminExtension extends Twig_Extension
{
    /** @var AdminRegistry */
    protected $adminRegistry;

    /** @var AdminEntityMatcher */
    protected $adminEntityMatcher;

    /** @var AdminRouter */
    protected $adminRouter;

    /**
     * @param AdminRegistry      $adminRegistry
     * @param AdminEntityMatcher $adminEntityMatcher
     * @param AdminRouter        $adminRouter
     */
    public function __construct(
        AdminRegistry $adminRegistry,
        AdminEntityMatcher $adminEntityMatcher,
        AdminRouter $adminRouter
    ) {
        $this->adminRegistry = $adminRegistry;
        $this->adminEntityMatcher = $adminEntityMatcher;
        $this->adminRouter = $adminRouter;
    }

    /**
     * @return array
     */
    public function getFunctions(): array
    {
        return [
            new Twig_SimpleFunction('get_admins', [$this->adminRegistry, 'getAdmins']),
            new Twig_SimpleFunction('admin_path', [$this->adminRouter, 'generateAdminPath']),
            new Twig_SimpleFunction('admin_entity_path', [$this->adminRouter, 'generateAdminEntityPath']),
            new Twig_SimpleFunction('entity_path', [$this->adminRouter, 'generateEntityPath']),
            new Twig_SimpleFunction('entity_admin', [$this->adminEntityMatcher, 'getAdminForEntity']),
            new Twig_SimpleFunction('admin', [$this, 'getAdmin']),
        ];
    }

    /**
     * @param string $code
     *
     * @throws UnexpectedValueException
     *
     * @return Admin
     */
    public function getAdmin($code): Admin
    {
        if ($code instanceof Admin) {
            return $code;
        }

        return $this->adminRegistry->getAdmin($code);
    }

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName(): string
    {
        return 'sidus_admin';
    }
}
