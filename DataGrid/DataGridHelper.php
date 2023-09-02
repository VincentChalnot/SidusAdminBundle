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

namespace Sidus\AdminBundle\DataGrid;

use Sidus\AdminBundle\Model\Action;
use Sidus\AdminBundle\Routing\RoutingHelper;
use Sidus\DataGridBundle\Model\DataGrid;
use Sidus\DataGridBundle\Registry\DataGridRegistry;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides a simple way to access datagrid utilities from a controller or an action
 */
class DataGridHelper
{
    public function __construct(
        protected DataGridRegistry $dataGridRegistry,
        protected RoutingHelper $routingHelper,
        protected FormFactoryInterface $formFactory,
        protected string $method = 'GET',
    ) {
    }

    public function getDataGridConfigCode(Action $action): string
    {
        // Check if datagrid code is set in options
        return $action->getOption(
            'datagrid',
            $action->getAdmin()->getOption(
                'datagrid',
                $action->getAdmin()->getCode() // Fallback to admin code
            )
        );
    }

    public function getDataGrid(Action $action): DataGrid
    {
        return $this->dataGridRegistry->getDataGrid($this->getDataGridConfigCode($action));
    }

    public function bindDataGridRequest(
        Action $action,
        Request $request,
        DataGrid $dataGrid = null,
        array $formOptions = []
    ): DataGrid {
        $dataGrid = $this->buildDataGridForm($action, $request, $dataGrid, $formOptions);
        $dataGrid->handleRequest($request);

        return $dataGrid;
    }

    public function buildDataGridForm(
        Action $action,
        Request $request,
        DataGrid $dataGrid = null,
        array $formOptions = []
    ): DataGrid {
        if (null === $dataGrid) {
            $dataGrid = $this->getDataGrid($action);
        }
        $formOptions = array_merge(
            [
                'method' => $this->method,
                'csrf_protection' => false,
                'action' => $this->routingHelper->getCurrentUri($action, $request),
                'validation_groups' => ['filters'],
            ],
            $dataGrid->getFormOptions(),
            $formOptions
        );

        // Create form with filters
        $builder = $this->formFactory->createBuilder(FormType::class, null, $formOptions);
        $dataGrid->buildForm($builder);

        return $dataGrid;
    }
}
