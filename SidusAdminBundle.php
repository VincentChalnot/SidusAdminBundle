<?php

namespace Sidus\AdminBundle;

use Sidus\AdminBundle\DependencyInjection\Compiler\GenericCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class SidusAdminBundle extends Bundle
{
    /**
     * Adding compiler passes to inject services into configuration handlers
     *
     * @param ContainerBuilder $container
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new GenericCompilerPass(
            'sidus_admin.configuration.admin.handler',
            'sidus.admin',
            'addAdmin'));
    }
}
