<?php

namespace Sidus\AdminBundle\Routing;

use Sidus\AdminBundle\Admin\Admin;
use Sidus\AdminBundle\Configuration\AdminConfigurationHandler;
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
    /** @var AdminConfigurationHandler */
    protected $adminConfigurationHandler;

    /** @var AdminEntityMatcher */
    protected $adminEntityMatcher;

    /** @var RouterInterface */
    protected $router;

    /** @var PropertyAccessorInterface */
    protected $accessor;

    /**
     * AdminExtension constructor.
     *
     * @param AdminConfigurationHandler $adminConfigurationHandler
     * @param AdminEntityMatcher        $adminEntityMatcher
     * @param RouterInterface           $router
     * @param PropertyAccessorInterface $accessor
     */
    public function __construct(
        AdminConfigurationHandler $adminConfigurationHandler,
        AdminEntityMatcher $adminEntityMatcher,
        RouterInterface $router,
        PropertyAccessorInterface $accessor
    ) {
        $this->adminConfigurationHandler = $adminConfigurationHandler;
        $this->adminEntityMatcher = $adminEntityMatcher;
        $this->router = $router;
        $this->accessor = $accessor;
    }

    /**
     * @param string|Admin $admin
     * @param string       $actionCode
     * @param array        $parameters
     * @param int          $referenceType
     *
     * @return string
     * @throws \Exception
     */
    public function generateAdminPath(
        $admin,
        $actionCode,
        array $parameters = [],
        $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH
    ) {
        $admin = $this->getAdmin($admin);
        $routeName = $admin->getAction($actionCode)->getRouteName();

        return $this->router->generate($routeName, $parameters, $referenceType);
    }

    /**
     * @param mixed  $entity
     * @param string $actionCode
     * @param array  $parameters
     * @param int    $referenceType
     *
     * @throws \Exception
     *
     * @return string
     */
    public function generateEntityPath(
        $entity,
        $actionCode,
        array $parameters = [],
        $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH
    ) {
        $admin = $this->adminEntityMatcher->getAdminForEntity($entity);

        return $this->generateAdminEntityPath($admin, $entity, $actionCode, $parameters, $referenceType);
    }

    /**
     * @param string|Admin $admin
     * @param mixed        $entity
     * @param string       $actionCode
     * @param array        $parameters
     * @param int          $referenceType
     *
     * @throws \Exception
     *
     * @return string
     */
    public function generateAdminEntityPath(
        $admin,
        $entity,
        $actionCode,
        array $parameters = [],
        $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH
    ) {
        $admin = $this->getAdmin($admin);
        $action = $admin->getAction($actionCode);

        $missingParams = $this->computeMissingRouteParameters($action->getRoute(), $parameters);
        foreach ($missingParams as $missingParam) {
            try {
                $parameters[$missingParam] = $this->accessor->getValue($entity, $missingParam);
            } catch (\Exception $e) {
                try {
                    // Fallback to array syntax
                    $parameters[$missingParam] = $this->accessor->getValue($entity, "[{$missingParam}]");
                } catch (\Exception $e) {
                    $contextParam = $this->router->getContext()->getParameter($missingParam);
                    if (null !== $contextParam) {
                        $parameters[$missingParam] = $contextParam;
                    }
                }
            }
        }

        return $this->router->generate($action->getRouteName(), $parameters, $referenceType);
    }

    /**
     * @param string|Admin $admin
     *
     * @throws \UnexpectedValueException
     *
     * @return Admin
     */
    protected function getAdmin($admin)
    {
        if (null === $admin) {
            return $this->adminConfigurationHandler->getCurrentAdmin();
        }
        if ($admin instanceof Admin) {
            return $admin;
        }

        return $this->adminConfigurationHandler->getAdmin($admin);
    }

    /**
     * @param Route $route
     * @param array $parameters
     *
     * @return array
     * @throws \LogicException
     */
    protected function computeMissingRouteParameters(Route $route, array $parameters)
    {
        $compiledRoute = $route->compile();
        $variables = array_flip($compiledRoute->getVariables());
        $mergedParams = array_replace($route->getDefaults(), $this->router->getContext()->getParameters(), $parameters);

        return array_flip(array_diff_key($variables, $mergedParams));
    }
}
