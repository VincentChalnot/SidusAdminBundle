<?php

declare(strict_types=1);

namespace Sidus\AdminBundle\Routing;

use Exception;
use Sidus\AdminBundle\Entity\AdminEntityMatcher;
use Sidus\AdminBundle\Model\Action;
use Sidus\AdminBundle\Model\Admin;
use Sidus\AdminBundle\Registry\AdminRegistry;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * Generated path for admins and actions
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
final readonly class AdminRouter
{
    public function __construct(
        private AdminRegistry $adminRegistry,
        private AdminEntityMatcher $adminEntityMatcher,
        private RouterInterface $router,
        private PropertyAccessorInterface $accessor,
    ) {
    }

    /**
     * @param array<string, mixed> $parameters
     */
    public function generateAdminPath(
        Admin|string $admin,
        string $actionCode,
        array $parameters = [],
        int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH,
    ): string {
        $action = $this->getAdmin($admin)->getAction($actionCode);

        foreach ($this->computeMissingRouteParameters($action, $parameters) as $missingParam) {
            if ($this->router->getContext()->hasParameter($missingParam)) {
                $parameters[$missingParam] = $this->router->getContext()->getParameter($missingParam);
            }
        }

        return $this->router->generate($action->getRouteName(), $parameters, $referenceType);
    }

    /**
     * @param array<string, mixed> $parameters
     */
    public function generateEntityPath(
        object $entity,
        string $actionCode,
        array $parameters = [],
        int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH,
    ): string {
        $admin = $this->adminEntityMatcher->getAdminForEntity($entity);

        return $this->generateAdminEntityPath($admin, $entity, $actionCode, $parameters, $referenceType);
    }

    /**
     * @param array<string, mixed> $parameters
     */
    public function generateAdminEntityPath(
        Admin|string $admin,
        object $entity,
        string $actionCode,
        array $parameters = [],
        int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH,
    ): string {
        $action = $this->getAdmin($admin)->getAction($actionCode);

        foreach ($this->computeMissingRouteParameters($action, $parameters) as $missingParam) {
            try {
                $parameters[$missingParam] = $this->accessor->getValue($entity, $missingParam);
            } catch (Exception) {
                try {
                    // Fallback to array syntax
                    $parameters[$missingParam] = $this->accessor->getValue($entity, "[{$missingParam}]");
                } catch (Exception) {
                    $contextParam = $this->router->getContext()->getParameter($missingParam);
                    if (null !== $contextParam) {
                        $parameters[$missingParam] = $contextParam;
                    }
                }
            }
        }

        return $this->router->generate($action->getRouteName(), $parameters, $referenceType);
    }

    private function getAdmin(Admin|string $admin): Admin
    {
        return $admin instanceof Admin ? $admin : $this->adminRegistry->get($admin);
    }

    /**
     * The placeholders of the route that are neither given, nor defaulted, nor in the routing context
     *
     * @param array<string, mixed> $parameters
     *
     * @return list<string>
     */
    private function computeMissingRouteParameters(Action $action, array $parameters): array
    {
        $known = array_replace($action->getRouteDefaults(), $this->router->getContext()->getParameters(), $parameters);

        return array_values(array_filter(
            $action->getRouteVariables(),
            static fn (string $variable): bool => !array_key_exists($variable, $known),
        ));
    }
}
