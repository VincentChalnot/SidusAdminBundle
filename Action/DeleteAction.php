<?php

namespace Sidus\AdminBundle\Action;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sidus\AdminBundle\Doctrine\DoctrineHelper;
use Sidus\AdminBundle\Form\FormHelper;
use Sidus\AdminBundle\Routing\RoutingHelper;
use Sidus\AdminBundle\Templating\TemplatingHelper;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Security("is_granted('delete', data)")
 */
class DeleteAction extends AbstractEmptyFormAction
{
    /** @var RoutingHelper */
    protected $routingHelper;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /**
     * @param FormHelper       $formHelper
     * @param TemplatingHelper $templatingHelper
     * @param RoutingHelper    $routingHelper
     * @param DoctrineHelper   $doctrineHelper
     */
    public function __construct(
        FormHelper $formHelper,
        TemplatingHelper $templatingHelper,
        RoutingHelper $routingHelper,
        DoctrineHelper $doctrineHelper
    ) {
        parent::__construct($formHelper, $templatingHelper);
        $this->routingHelper = $routingHelper;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritdoc}
     */
    protected function applyAction(Request $request, FormInterface $form, $data): Response
    {
        $this->doctrineHelper->deleteEntity($this->action, $data, $request->getSession());

        return $this->routingHelper->redirectToAction(
            $this->action->getAdmin()->getAction(
                $this->action->getOption('redirect_action', 'list')
            )
        );
    }
}
