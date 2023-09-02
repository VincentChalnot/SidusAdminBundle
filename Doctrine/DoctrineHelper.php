<?php
/*
 * This file is part of the Sidus/AdminBundle package.
 *
 * Copyright (c) 2015-2023 Vincent Chalnot
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
use Sidus\AdminBundle\Session\FlashHelper;
use Sidus\AdminBundle\Model\Action;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Provides a simple way to access Doctrine utilities from a controller or an action
 */
class DoctrineHelper
{
    public function __construct(
        protected FlashHelper $flashHelper,
        protected ?ManagerRegistry $managerRegistry = null,
    ) {
    }

    public function getManagerForEntity(object $entity): EntityManagerInterface
    {
        if (!$this->managerRegistry) {
            throw new InvalidArgumentException('Doctrine is not enabled for Sidus/AdminBundle');
        }
        $class = ClassUtils::getClass($entity);
        $entityManager = $this->managerRegistry->getManagerForClass($class);
        if (!$entityManager instanceof EntityManagerInterface) {
            throw new InvalidArgumentException("No manager found for class {$class}");
        }

        return $entityManager;
    }

    public function saveEntity(Action $action, object $entity, SessionInterface $session = null): void
    {
        if (!$this->managerRegistry) {
            throw new InvalidArgumentException('Doctrine is not enabled for Sidus/AdminBundle');
        }
        $entityManager = $this->getManagerForEntity($entity);
        $entityManager->persist($entity);
        $entityManager->flush();

        $this->flashHelper->addFlash($action, $session);
    }

    public function deleteEntity(Action $action, object $entity, SessionInterface $session = null): void
    {
        if (!$this->managerRegistry) {
            throw new InvalidArgumentException('Doctrine is not enabled for Sidus/AdminBundle');
        }
        $entityManager = $this->getManagerForEntity($entity);
        $entityManager->remove($entity);
        $entityManager->flush();

        $this->flashHelper->addFlash($action, $session);
    }

    public function entityToString(object $data): string
    {
        if (!$this->managerRegistry) {
            throw new InvalidArgumentException('Doctrine is not enabled for Sidus/AdminBundle');
        }
        $manager = $this->managerRegistry->getManagerForClass($data::class);
        if (!$manager instanceof EntityManagerInterface) {
            throw new InvalidArgumentException('Not a Doctrine entity');
        }

        $classMetadata = $manager->getClassMetadata($data::class);
        $identifiers = $classMetadata->getIdentifierValues($data);
        if (1 === count($identifiers)) {
            return $data::class.'#'.reset($identifiers);
        }

        return $data::class.json_encode($identifiers, JSON_THROW_ON_ERROR);
    }
}
