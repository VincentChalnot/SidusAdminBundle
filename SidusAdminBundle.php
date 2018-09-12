<?php
/*
 * This file is part of the Sidus/AdminBundle package.
 *
 * Copyright (c) 2015-2018 Vincent Chalnot
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sidus\AdminBundle;

use Sidus\BaseBundle\DependencyInjection\Compiler\GenericCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Sidus\AdminBundle\Configuration\AdminRegistry;

/**
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class SidusAdminBundle extends Bundle
{
    /**
     * Adding compiler passes to inject services into configuration handlers
     *
     * @param ContainerBuilder $container
     */
    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(
            new GenericCompilerPass(
                AdminRegistry::class,
                'sidus.admin',
                'addAdmin'
            )
        );
    }
}
