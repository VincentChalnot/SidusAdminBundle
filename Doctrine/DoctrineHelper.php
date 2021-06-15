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

namespace Sidus\AdminBundle\Doctrine;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use InvalidArgumentException;
use Sidus\AdminBundle\Admin\Action;
use Sidus\BaseBundle\Translator\TranslatableTrait;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Provides a simple way to access Doctrine utilities from a controller or an action
 */
class DoctrineHelper
{
    use TranslatableTrait;

    public function __construct(
        protected ManagerRegistry $doctrine,
        TranslatorInterface $translator,
    ) {
        $this->translator = $translator;
    }

    public function getManagerForEntity(object $entity): EntityManagerInterface
    {
        $class = ClassUtils::getClass($entity);
        $entityManager = $this->doctrine->getManagerForClass($class);
        if (!$entityManager instanceof EntityManagerInterface) {
            throw new InvalidArgumentException("No manager found for class {$class}");
        }

        return $entityManager;
    }

    public function saveEntity(Action $action, object $entity, SessionInterface $session = null): void
    {
        $entityManager = $this->getManagerForEntity($entity);
        $entityManager->persist($entity);
        $entityManager->flush();

        $this->addFlash($action, $session);
    }

    public function deleteEntity(Action $action, object $entity, SessionInterface $session = null): void
    {
        $entityManager = $this->getManagerForEntity($entity);
        $entityManager->remove($entity);
        $entityManager->flush();

        $this->addFlash($action, $session);
    }

    public function addFlash(Action $action, SessionInterface $session = null): void
    {
        if ($action && $session instanceof Session) {
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
}
