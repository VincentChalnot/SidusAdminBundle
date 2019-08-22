<?php declare(strict_types=1);
/*
 * This file is part of the Sidus/AdminBundle package.
 *
 * Copyright (c) 2015-2019 Vincent Chalnot
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sidus\AdminBundle\Request\ParamConverter;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
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
    /** @var ManagerRegistry */
    protected $doctrine;

    /**
     * @param ManagerRegistry $doctrine
     */
    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * {@inheritdoc}
     */
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
        $id = $request->attributes->get($configuration->getOptions()['attribute'] ?? 'id');
        if (null === $id) {
            $m = "Unable to resolve request attribute for identifier, either use 'id' as a request parameter or set it";
            $m .= " manually in the 'attribute' option of your param converter configuration";
            throw new UnexpectedValueException($m);
        }
        $repository = $entityManager->getRepository($admin->getEntity());
        $entity = $repository->find($id);
        if (!$entity) {
            throw new NotFoundHttpException("No entity found for class {$admin->getEntity()} and id {$id}");
        }
        $request->attributes->set($configuration->getName(), $entity);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(ParamConverter $configuration): bool
    {
        return 'sidus_admin.entity' === $configuration->getConverter();
    }
}
