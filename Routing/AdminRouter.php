<?php

namespace Sidus\AdminBundle\Routing;


use Sidus\AdminBundle\Admin\Admin;
use Sidus\AdminBundle\Configuration\AdminConfigurationHandler;
use Sidus\AdminBundle\Entity\AdminEntityMatcher;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Routing\Exception\MissingMandatoryParametersException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouterInterface;

class AdminRouter
{
    /** @var AdminConfigurationHandler */
    protected $adminConfigurationHandler;

    /** @var AdminEntityMatcher */
    protected $adminEntityMatcher;

    /** @var RouterInterface */
    protected $router;

    /**
     * AdminExtension constructor.
     * @param AdminConfigurationHandler $adminConfigurationHandler
     * @param AdminEntityMatcher $adminEntityMatcher
     * @param RouterInterface $router
     */
    public function __construct(AdminConfigurationHandler $adminConfigurationHandler, AdminEntityMatcher $adminEntityMatcher, RouterInterface $router)
    {
        $this->adminConfigurationHandler = $adminConfigurationHandler;
        $this->adminEntityMatcher = $adminEntityMatcher;
        $this->router = $router;
    }

    /**
     * @param string|Admin $admin
     * @param string $actionCode
     * @param array $parameters
     * @param int $referenceType
     * @return string
     * @throws \Exception
     */
    public function generateAdminPath($admin, $actionCode, array $parameters = [], $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH)
    {
        if (null === $admin) {
            $admin = $this->adminConfigurationHandler->getCurrentAdmin();
        }
        if (!$admin instanceof Admin) {
            $admin = $this->adminConfigurationHandler->getAdmin($admin);
        }
        $routeName = $admin->getAction($actionCode)->getRouteName();
        return $this->router->generate($routeName, $parameters, $referenceType);
    }

    /**
     * @param mixed $entity
     * @param string $actionCode
     * @param array $parameters
     * @param int $referenceType
     * @return string
     * @throws \Exception
     */
    public function generateEntityPath($entity, $actionCode, array $parameters = [], $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH)
    {
        $admin = $this->adminEntityMatcher->getAdminForEntity($entity);
        $action = $admin->getAction($actionCode);

        $accessor = PropertyAccess::createPropertyAccessor();
        $missingParams = $this->computeMissingRouteParameters($action->getRoute(), $parameters);
        foreach ($missingParams as $missingParam) {
            try {
                $parameters[$missingParam] = $accessor->getValue($entity, $missingParam);
            } catch (\Exception $e) {
                $contextParam = $this->router->getContext()->getParameter($missingParam);
                if (null !== $contextParam) {
                    $parameters[$missingParam] = $contextParam;
                }
            }
        }
        return $this->router->generate($action->getRouteName(), $parameters, $referenceType);
    }

    /**
     * @param Route $route
     * @param array $parameters
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
