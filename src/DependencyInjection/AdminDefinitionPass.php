<?php

declare(strict_types=1);

namespace Sidus\AdminBundle\DependencyInjection;

use Sidus\AdminBundle\Model\Admin;
use Sidus\AdminBundle\Registry\AdminRegistry;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Builds the admins once everything is registered: the raw configuration is validated and resolved by the pure
 * {@see AdminConfigurationResolver} (the controllers are looked up in the container, so that an action without any
 * controller is a compile error and the route carries its final `_controller`), then one immutable Admin service is
 * registered per admin and given to the {@see AdminRegistry}.
 */
final class AdminDefinitionPass implements CompilerPassInterface
{
    public const string RAW_PARAMETER = 'sidus_admin.raw_configuration';
    public const string ADMIN_TAG = 'sidus_admin.admin';

    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition(AdminRegistry::class) || !$container->hasParameter(self::RAW_PARAMETER)) {
            return;
        }
        /** @var array{theme: string|null, base_template: string|null, configurations: array<string, array<string, mixed>>} $raw */
        $raw = $container->getParameterBag()->resolveValue($container->getParameter(self::RAW_PARAMETER));
        $container->getParameterBag()->remove(self::RAW_PARAMETER);

        $resolver = new AdminConfigurationResolver(
            $raw['theme'],
            $raw['base_template'],
            fn (string $controller): bool => $this->controllerExists($container, $controller),
        );

        $references = [];
        foreach ($resolver->resolve($raw['configurations']) as $code => $arguments) {
            $id = 'sidus_admin.admin.'.$code;
            $container->register($id, Admin::class)
                ->setArguments($arguments)
                ->setPublic(false)
                ->addTag(self::ADMIN_TAG);
            $references[$code] = new Reference($id);
        }
        $container->getDefinition(AdminRegistry::class)->setArgument('$admins', $references);
    }

    private function controllerExists(ContainerBuilder $container, string $controller): bool
    {
        if ($container->has($controller)) {
            return true;
        }
        $class = explode('::', $controller, 2)[0];
        if ($container->has($class)) {
            return true;
        }
        if (!class_exists($class)) {
            return false;
        }

        // The controller resolver instantiates a class that is not a service, but only without constructor arguments
        return 0 === (new \ReflectionClass($class))->getConstructor()?->getNumberOfRequiredParameters();
    }
}
