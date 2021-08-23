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

namespace Sidus\AdminBundle\Request\ParamConverter;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Sidus\AdminBundle\Admin\Admin;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use UnexpectedValueException;

/**
 * Uses the admin configuration to convert an entity id to a real doctrine entity
 */
class AdminEntityParamConverter implements ParamConverterInterface
{
    public function __construct(protected ManagerRegistry $doctrine)
    {
    }

    public function apply(Request $request, ParamConverter $configuration): bool
    {
        if (!$request->attributes->has('_admin')) {
            throw new UnexpectedValueException('Missing _admin request attribute');
        }
        $admin = $request->attributes->get('_admin');
        if (!$admin instanceof Admin) {
            throw new UnexpectedValueException('_admin request attribute is not an Admin object');
        }
        $entityManager = $this->doctrine->getManagerForClass($admin->getEntity());
        if (!$entityManager instanceof EntityManagerInterface) {
            throw new UnexpectedValueException("Unable to find an EntityManager for class {$admin->getEntity()}");
        }

        try {
            $entity = $this->findByIdentifiers($admin, $entityManager, $request);
        } catch (UnexpectedValueException $e1) {
            try {
                $entity = $this->findByUniqueAttribute($admin, $entityManager, $request, $e1);
            } catch (UnexpectedValueException $e2) {
                try {
                    $entity = $this->findByUniqueConstraint($admin, $entityManager, $request, $e2);
                } catch (UnexpectedValueException $e3) {
                    throw new UnexpectedValueException(
                        'Unable to resolve any entity given request parameters',
                        0,
                        $e3
                    );
                }
            }
        }

        $request->attributes->set($configuration->getName(), $entity);

        return true;
    }

    public function supports(ParamConverter $configuration): bool
    {
        return 'sidus_admin.entity' === $configuration->getConverter();
    }

    protected function findByIdentifiers(
        Admin $admin,
        EntityManagerInterface $entityManager,
        Request $request,
    ): object {
        $classMetadata = $entityManager->getClassMetadata($admin->getEntity());

        $identifiers = [];
        foreach ($classMetadata->getIdentifier() as $identifier) {
            if (!$request->attributes->has($identifier)) {
                $m = "Missing identifier request attribute for entity {$admin->getEntity()}: '{$identifier}'";
                throw new UnexpectedValueException($m);
            }
            $identifiers[$identifier] = $request->attributes->get($identifier);
        }

        $repository = $entityManager->getRepository($admin->getEntity());
        $entity = $repository->findOneBy($identifiers);
        if (!$entity) {
            $flat = implode($identifiers);
            throw new NotFoundHttpException("No entity found for class {$admin->getEntity()} and identifiers {$flat}");
        }

        return $entity;
    }

    private function findByUniqueAttribute(
        Admin $admin,
        EntityManagerInterface $entityManager,
        Request $request,
        \Throwable $previousException,
    ): object {
        $classMetadata = $entityManager->getClassMetadata($admin->getEntity());
        $repository = $entityManager->getRepository($admin->getEntity());

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
                    "No entity found for class {$admin->getEntity()} and unique attribute {$fieldName}"
                );
            }

            return $entity;
        }

        throw new UnexpectedValueException('No unique attribute in request', 0, $previousException);
    }

    private function findByUniqueConstraint(
        Admin $admin,
        EntityManagerInterface $entityManager,
        Request $request,
        \Throwable $previousException,
    ): object {
        $classMetadata = $entityManager->getClassMetadata($admin->getEntity());
        $repository = $entityManager->getRepository($admin->getEntity());

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
                    "No entity found for class {$admin->getEntity()} and unique attributes {$fieldNames}"
                );
            }

            return $entity;
        }

        throw new UnexpectedValueException('No unique constraint in request', 0, $previousException);
    }
}
