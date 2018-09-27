<?php
/*
 * This file is part of the Sidus/AdminBundle package.
 *
 * Copyright (c) 2015-2018 Vincent Chalnot
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sidus\AdminBundle\DataGrid;

use Sidus\AdminBundle\Admin\Action;
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
    /** @var DataGridRegistry */
    protected $dataGridRegistry;

    /** @var RoutingHelper */
    protected $routingHelper;

    /** @var FormFactoryInterface */
    protected $formFactory;

    /** @var string */
    protected $method;

    /**
     * @param DataGridRegistry     $dataGridRegistry
     * @param RoutingHelper        $routingHelper
     * @param FormFactoryInterface $formFactory
     * @param string               $method
     */
    public function __construct(
        DataGridRegistry $dataGridRegistry,
        RoutingHelper $routingHelper,
        FormFactoryInterface $formFactory,
        string $method = 'GET'
    ) {
        $this->dataGridRegistry = $dataGridRegistry;
        $this->routingHelper = $routingHelper;
        $this->formFactory = $formFactory;
        $this->method = $method;
    }

    /**
     * @param Action $action
     *
     * @return string
     */
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

    /**
     * @param Action $action
     *
     * @return DataGrid
     */
    public function getDataGrid(Action $action): DataGrid
    {
        return $this->dataGridRegistry->getDataGrid($this->getDataGridConfigCode($action));
    }

    /**
     * @param Action        $action
     * @param Request       $request
     * @param DataGrid|null $dataGrid
     * @param array         $formOptions
     *
     * @return DataGrid
     */
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

    /**
     * @param Action        $action
     * @param Request       $request
     * @param DataGrid|null $dataGrid
     * @param array         $formOptions
     *
     * @return DataGrid
     */
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
            $formOptions
        );

        // Create form with filters
        $builder = $this->formFactory->createBuilder(FormType::class, null, $formOptions);
        $dataGrid->buildForm($builder);

        return $dataGrid;
    }
}
