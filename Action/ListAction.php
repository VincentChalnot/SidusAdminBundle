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

namespace Sidus\AdminBundle\Action;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sidus\AdminBundle\Request\ActionResponseInterface;
use Sidus\AdminBundle\Templating\TemplatingHelper;
use Sidus\AdminBundle\DataGrid\DataGridHelper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;

/**
 * @Security("is_granted('list', _admin.getEntity())")
 */
class ListAction implements ActionInjectableInterface
{
    use ActionInjectableTrait;

    public function __construct(
        protected DataGridHelper $dataGridHelper,
        protected TemplatingHelper $templatingHelper,
        protected RouterInterface $router
    ) {
    }

    public function __invoke(Request $request): ActionResponseInterface
    {
        $dataGrid = $this->dataGridHelper->bindDataGridRequest($this->action, $request);

        return $this->templatingHelper->renderListAction($this->action, $dataGrid);
    }
}
