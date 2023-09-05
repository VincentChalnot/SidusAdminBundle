<?php
declare(strict_types=1);

namespace Sidus\AdminBundle\Session;

use Sidus\AdminBundle\Model\Action;
use Sidus\AdminBundle\Translator\TranslatorHelper;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class FlashHelper
{
    public function __construct(
        protected TranslatorHelper $translatorHelper,
    ) {
    }

    public function addFlash(Action $action, SessionInterface $session = null): void
    {
        if (!$session instanceof Session) {
            return;
        }
        $session->getFlashBag()->add(
            'success',
            $this->translatorHelper->tryTranslate(
                [
                    "sidus.admin.{$action->getAdmin()->getCode()}.{$action->getCode()}.success",
                    "sidus.admin.flash.{$action->getCode()}.success",
                ],
                [],
                ucfirst($action->getCode()).' success'
            )
        );
    }
}
