<?php

namespace Sidus\AdminBundle\Action;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sidus\AdminBundle\Templating\TemplatingHelper;
use Sidus\AdminBundle\Admin\Action;
use Sidus\AdminBundle\DataGrid\DataGridHelper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;

/**
 * @Security("is_granted('list', _admin.getEntity())")
 */
class ListAction implements ActionInjectableInterface
{
    /** @var DataGridHelper */
    protected $dataGridHelper;

    /** @var TemplatingHelper */
    protected $templatingHelper;

    /** @var RouterInterface */
    protected $router;

    /** @var Action */
    protected $action;

    /**
     * @param DataGridHelper   $dataGridHelper
     * @param TemplatingHelper $templatingHelper
     * @param RouterInterface  $router
     */
    public function __construct(
        DataGridHelper $dataGridHelper,
        TemplatingHelper $templatingHelper,
        RouterInterface $router
    ) {
        $this->dataGridHelper = $dataGridHelper;
        $this->templatingHelper = $templatingHelper;
        $this->router = $router;
    }

    /**
     * @param Request $request
     *
     * @throws \Exception
     *
     * @return Response
     */
    public function __invoke(Request $request)
    {
        $dataGrid = $this->dataGridHelper->bindDataGridRequest($this->action, $request);

        return $this->templatingHelper->renderListAction($this->action, $dataGrid);
    }

    /**
     * @param Action $action
     */
    public function setAction(Action $action): void
    {
        $this->action = $action;
    }
}
