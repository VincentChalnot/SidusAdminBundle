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
        $request->attributes->set($configuration->getName(), $entity);

        return true;
    }

    public function supports(ParamConverter $configuration): bool
    {
        return 'sidus_admin.entity' === $configuration->getConverter();
    }
}
