<?php
/*
 * This file is part of the Sidus/AdminBundle package.
 *
 * Copyright (c) 2015-2019 Vincent Chalnot
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sidus\AdminBundle\Doctrine;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManagerInterface;
use Sidus\AdminBundle\Admin\Action;
use Sidus\BaseBundle\Translator\TranslatableTrait;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Provides a simple way to access Doctrine utilities from a controller or an action
 */
class DoctrineHelper
{
    use TranslatableTrait;

    /** @var ManagerRegistry */
    protected $doctrine;

    /**
     * @param ManagerRegistry     $doctrine
     * @param TranslatorInterface $translator
     */
    public function __construct(ManagerRegistry $doctrine, TranslatorInterface $translator)
    {
        $this->doctrine = $doctrine;
        $this->translator = $translator;
    }

    /**
     * @param mixed $entity
     *
     * @throws \LogicException
     *
     * @return EntityManagerInterface
     */
    public function getManagerForEntity($entity): EntityManagerInterface
    {
        $class = ClassUtils::getClass($entity);
        $entityManager = $this->doctrine->getManagerForClass($class);
        if (!$entityManager instanceof EntityManagerInterface) {
            throw new \InvalidArgumentException("No manager found for class {$class}");
        }

        return $entityManager;
    }

    /**
     * @param Action|null           $action
     * @param mixed                 $entity
     * @param SessionInterface|null $session
     */
    public function saveEntity(Action $action, $entity, SessionInterface $session = null): void
    {
        $entityManager = $this->getManagerForEntity($entity);
        $entityManager->persist($entity);
        $entityManager->flush();

        $this->addFlash($action, $session);
    }

    /**
     * @param Action                $action
     * @param mixed                 $entity
     * @param SessionInterface|null $session
     */
    public function deleteEntity(Action $action, $entity, SessionInterface $session = null): void
    {
        $entityManager = $this->getManagerForEntity($entity);
        $entityManager->remove($entity);
        $entityManager->flush();

        $this->addFlash($action, $session);
    }

    /**
     * @param Action                $action
     * @param SessionInterface|null $session
     */
    protected function addFlash(Action $action, SessionInterface $session = null): void
    {
        if ($action && $session instanceof Session) {
            $session->getFlashBag()->add(
                'success',
                $this->tryTranslate(
                    [
                        "admin.{$action->getAdmin()->getCode()}.{$action->getCode()}.success",
                        "admin.flash.{$action->getCode()}.success",
                    ],
                    [],
                    ucfirst($action->getCode()).' success'
                )
            );
        }
    }
}
