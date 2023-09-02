<?php
declare(strict_types=1);

namespace Sidus\AdminBundle\Session;

use Sidus\AdminBundle\Model\Action;
use Sidus\AdminBundle\Translator\TranslatableTrait;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class FlashHelper
{
    use TranslatableTrait;

    public function __construct(
        TranslatorInterface $translator,
    ) {
        $this->translator = $translator;
    }

    public function addFlash(Action $action, SessionInterface $session = null): void
    {
        if (!$session instanceof Session) {
            return;
        }
        $session->getFlashBag()->add(
            'success',
            $this->tryTranslate(
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
