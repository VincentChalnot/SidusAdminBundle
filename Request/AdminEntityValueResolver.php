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

namespace Sidus\AdminBundle\Request;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Sidus\AdminBundle\Attribute\AdminEntity;
use Sidus\AdminBundle\Model\Action;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use UnexpectedValueException;

/**
 * Uses the admin configuration to convert an entity id to a real doctrine entity
 */
class AdminEntityValueResolver implements ValueResolverInterface
{
    public function __construct(
        protected AuthorizationCheckerInterface $authorizationChecker,
        protected ?ManagerRegistry $managerRegistry = null,
    ) {
    }

    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if (!$this->managerRegistry) {
            return;
        }
        if (empty($argument->getAttributes(AdminEntity::class))) {
            return;
        }

        $action = $request->attributes->get('_action');
        if (!$action instanceof Action) {
            throw new UnexpectedValueException("Unable to resolve entity without action");
        }
        $entityClass = $action->getAdmin()->getEntity();
        $entityManager = $this->managerRegistry->getManagerForClass($entityClass);
        if (!$entityManager instanceof EntityManagerInterface) {
            return;
        }

        yield $this->getArgumentValue($entityClass, $entityManager, $request);;
    }

    protected function getArgumentValue(
        string $entityClass,
        EntityManagerInterface $entityManager,
        Request $request,
    ): object {
        try {
            return $this->findByIdentifiers($entityClass, $entityManager, $request);
        } catch (UnexpectedValueException $e1) {
            try {
                return $this->findByUniqueAttribute($entityClass, $entityManager, $request, $e1);
            } catch (UnexpectedValueException $e2) {
                try {
                    return $this->findByUniqueConstraint($entityClass, $entityManager, $request, $e2);
                } catch (UnexpectedValueException $e3) {
                    throw new UnexpectedValueException(
                        'Unable to resolve any entity given request parameters',
                        0,
                        $e3
                    );
                }
            }
        }
    }

    protected function findByIdentifiers(
        string $entityClass,
        EntityManagerInterface $entityManager,
        Request $request,
    ): object {
        $classMetadata = $entityManager->getClassMetadata($entityClass);

        $identifiers = [];
        foreach ($classMetadata->getIdentifier() as $identifier) {
            if (!$request->attributes->has($identifier)) {
                $m = "Missing identifier request attribute for entity {$entityClass}: '{$identifier}'";
                throw new UnexpectedValueException($m);
            }
            $identifiers[$identifier] = $request->attributes->get($identifier);
        }

        $repository = $entityManager->getRepository($entityClass);
        $entity = $repository->findOneBy($identifiers);
        if (!$entity) {
            $flat = implode($identifiers);
            throw new NotFoundHttpException("No entity found for class {$entityClass} and identifiers {$flat}");
        }

        return $entity;
    }

    protected function findByUniqueAttribute(
        string $entityClass,
        EntityManagerInterface $entityManager,
        Request $request,
        \Throwable $previousException,
    ): object {
        $classMetadata = $entityManager->getClassMetadata($entityClass);
        $repository = $entityManager->getRepository($entityClass);

        foreach ($classMetadata->fieldMappings as $fieldMapping) {
            $fieldName = $fieldMapping['fieldName'];
            if ($classMetadata->isIdentifier($fieldName)
                || !$classMetadata->isUniqueField($fieldName)
                || !$request->attributes->has($fieldName)
            ) {
                continue;
            }

            $entity = $repository->findOneBy([$fieldName => $request->attributes->get($fieldName)]);
            if (!$entity) {
                throw new NotFoundHttpException(
                    "No entity found for class {$entityClass} and unique attribute {$fieldName}"
                );
            }

            return $entity;
        }

        throw new UnexpectedValueException('No unique attribute in request', 0, $previousException);
    }

    protected function findByUniqueConstraint(
        string $entityClass,
        EntityManagerInterface $entityManager,
        Request $request,
        \Throwable $previousException,
    ): object {
        $classMetadata = $entityManager->getClassMetadata($entityClass);
        $repository = $entityManager->getRepository($entityClass);

        if (!array_key_exists('uniqueConstraints', $classMetadata->table)) {
            throw new UnexpectedValueException(
                'No attribute matching a unique constraint in request',
                0,
                $previousException,
            );
        }

        foreach ($classMetadata->table['uniqueConstraints'] as $uniqueConstraint) {
            $criteria = [];
            foreach ($uniqueConstraint['columns'] as $fieldName) {
                if (!$request->attributes->has($fieldName)) {
                    continue 2;
                }
                $criteria[$fieldName] = $request->attributes->get($fieldName);
            }
            $entity = $repository->findOneBy($criteria);
            if (!$entity) {
                $fieldNames = implode(', ', array_keys($criteria));
                throw new NotFoundHttpException(
                    "No entity found for class {$entityClass} and unique attributes {$fieldNames}"
                );
            }

            return $entity;
        }

        throw new UnexpectedValueException('No unique constraint in request', 0, $previousException);
    }
}
