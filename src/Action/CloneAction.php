<?php

declare(strict_types=1);

namespace Sidus\AdminBundle\Action;

use Sidus\AdminBundle\Attribute\AdminEntity;
use Sidus\AdminBundle\Model\Action;
use Sidus\AdminBundle\Request\ActionResponseInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
readonly class CloneAction extends AbstractEditDelegatingAction
{
    public function __invoke(
        Request $request,
        Action $action,
        #[AdminEntity]
        object $data,
    ): ActionResponseInterface {
        return $this->edit($request, $action, clone $data);
    }
}
