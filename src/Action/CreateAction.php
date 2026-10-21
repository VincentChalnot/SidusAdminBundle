<?php

declare(strict_types=1);

namespace Sidus\AdminBundle\Action;

use Sidus\AdminBundle\Model\Action;
use Sidus\AdminBundle\Request\ActionResponseInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
readonly class CreateAction extends AbstractEditDelegatingAction
{
    public function __invoke(Request $request, Action $action): ActionResponseInterface
    {
        $class = $action->getAdmin()->getEntity();

        return $this->edit($request, $action, new $class());
    }
}
