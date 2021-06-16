<?php
/*
 * This file is part of the Sidus/AdminBundle package.
 *
 * Copyright (c) 2015-2021 Vincent Chalnot
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Sidus\AdminBundle\Templating;

use Sidus\AdminBundle\Admin\Action;
use Sidus\AdminBundle\Request\ActionResponse;
use Sidus\AdminBundle\Request\ActionResponseInterface;
use Sidus\AdminBundle\Routing\RoutingHelper;
use Sidus\DataGridBundle\Model\DataGrid;
use Symfony\Component\Form\FormInterface;

/**
 * Provides a simple way to access rendering utilities from a controller or an action
 */
class TemplatingHelper
{
    public function __construct(
        protected RoutingHelper $routingHelper,
    ) {
    }

    public function renderAction(Action $action, array $parameters = []): ActionResponseInterface
    {
        return new ActionResponse($action, $parameters);
    }

    public function renderListAction(
        Action $action,
        DataGrid $dataGrid,
        array $viewParameters = []
    ): ActionResponseInterface {
        $viewParameters = array_merge(
            $this->getViewParameters($action),
            ['datagrid' => $dataGrid],
            $viewParameters
        );

        return $this->renderAction($action, $viewParameters);
    }

    public function renderFormAction(
        Action $action,
        FormInterface $form,
        object|array|null $data = null,
        array $viewParameters = []
    ): ActionResponseInterface {
        $viewParameters = array_merge($this->getViewParameters($action, $form, $data), $viewParameters);

        return $this->renderAction($action, $viewParameters);
    }

    public function getViewParameters(
        Action $action,
        FormInterface $form = null,
        object|array|null $data = null,
        array $listRouteParameters = []
    ): array {
        $parameters = [
            'listPath' => $this->routingHelper->getAdminListPath($action->getAdmin(), $listRouteParameters),
            'action' => $action,
            'admin' => $action->getAdmin(),
        ];

        if ($form) {
            $parameters['form'] = $form->createView();
        }
        if (null !== $data) {
            $parameters['data'] = $data;
        }

        return $parameters;
    }
}
