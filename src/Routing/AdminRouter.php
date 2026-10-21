<?php
/*
 * This file is part of the Sidus/AdminBundle package.
 *
 * Copyright (c) 2015-2023 Vincent Chalnot
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Sidus\AdminBundle\Routing;

use Exception;
use Sidus\AdminBundle\Model\Admin;
use Sidus\AdminBundle\Configuration\AdminRegistry;
use Sidus\AdminBundle\Entity\AdminEntityMatcher;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouterInterface;

/**
 * Generated path for admins and actions
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class AdminRouter
{
    public function __construct(
        protected AdminRegistry $adminRegistry,
        protected AdminEntityMatcher $adminEntityMatcher,
        protected RouterInterface $router,
        protected PropertyAccessorInterface $accessor
    ) {
    }

    public function generateAdminPath(
        Admin|string $admin,
        string $actionCode,
        array $parameters = [],
        int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH
    ): string {
        $admin = $this->getAdmin($admin);
        $action = $admin->getAction($actionCode);

        $missingParams = $this->computeMissingRouteParameters($action->getRoute(), $parameters);
        foreach ($missingParams as $missingParam) {
            if ($this->router->getContext()->hasParameter($missingParam)) {
                $parameters[$missingParam] = $this->router->getContext()->getParameter($missingParam);
            }
        }

        return $this->router->generate($action->getRouteName(), $parameters, $referenceType);
    }

    public function generateEntityPath(
        object $entity,
        string $actionCode,
        array $parameters = [],
        int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH
    ): string {
        $admin = $this->adminEntityMatcher->getAdminForEntity($entity);

        return $this->generateAdminEntityPath($admin, $entity, $actionCode, $parameters, $referenceType);
    }

    public function generateAdminEntityPath(
        Admin|string $admin,
        object $entity,
        string $actionCode,
        array $parameters = [],
        int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH
    ): string {
        $admin = $this->getAdmin($admin);
        $action = $admin->getAction($actionCode);

        $missingParams = $this->computeMissingRouteParameters($action->getRoute(), $parameters);
        foreach ($missingParams as $missingParam) {
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

    protected function getAdmin(Admin|string $admin): Admin
    {
        if (null === $admin) {
            return $this->adminRegistry->getCurrentAdmin();
        }
        if ($admin instanceof Admin) {
            return $admin;
        }

        return $this->adminRegistry->getAdmin($admin);
    }

    protected function computeMissingRouteParameters(Route $route, array $parameters): array
    {
        $compiledRoute = $route->compile();
        if (null === $compiledRoute) {
            return [];
        }
        $variables = array_flip($compiledRoute->getVariables());
        $mergedParams = array_replace($route->getDefaults(), $this->router->getContext()->getParameters(), $parameters);

        return array_flip(array_diff_key($variables, $mergedParams));
    }
}
