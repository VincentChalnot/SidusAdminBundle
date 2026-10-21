<?php

declare(strict_types=1);

namespace Sidus\AdminBundle\Action;

use Sidus\AdminBundle\Model\Action;
use Sidus\AdminBundle\Request\ActionResponseInterface;
use Sidus\AdminBundle\Routing\RoutingHelper;
use Sidus\AdminBundle\Templating\TemplatingHelper;
use Sidus\DataGridBundle\Factory\DataGridFactory;
use Sidus\FilterBundle\Provider\QueryHandlerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;

/**
 * Renders a datagrid created for this very request. The datagrid code is, by priority, the `datagrid` option of the
 * action, the `datagrid` option of the admin, the code of the admin.
 *
 * To restrict or alter the query for the current request (the user's tenant, a route parameter...), extend this class
 * (as a `readonly` class), override {@see self::customizeHandler()} and reference your class in the controller_pattern
 * or the defaults._controller of the action.
 */
#[AsController]
readonly class ListAction
{
    public function __construct(
        protected DataGridFactory $dataGridFactory,
        protected TemplatingHelper $templatingHelper,
        protected RoutingHelper $routingHelper,
    ) {
    }

    public function __invoke(Request $request, Action $action): ActionResponseInterface
    {
        $dataGrid = $this->dataGridFactory->create(
            $this->getDataGridCode($action),
            $request,
            ['action' => $this->routingHelper->getCurrentUri($action, $request)],
            fn (QueryHandlerInterface $handler): QueryHandlerInterface => $this->customizeHandler($handler, $request, $action),
        );

        return $this->templatingHelper->renderListAction($action, $dataGrid);
    }

    public function getDataGridCode(Action $action): string
    {
        return $action->getOption(
            'datagrid',
            $action->getAdmin()->getOption('datagrid', $action->getAdmin()->getCode()),
        );
    }

    /**
     * Called with the fresh, single-use query handler of the request: return the handler to use (handlers are
     * immutable, so return the one derived from the given one, e.g. `$handler->withCustomizer(...)` with Doctrine).
     */
    protected function customizeHandler(QueryHandlerInterface $handler, Request $request, Action $action): QueryHandlerInterface
    {
        return $handler;
    }
}
