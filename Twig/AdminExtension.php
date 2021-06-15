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

namespace Sidus\AdminBundle\Twig;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Sidus\AdminBundle\Admin\Admin;
use Sidus\AdminBundle\Configuration\AdminRegistry;
use Sidus\AdminBundle\Entity\AdminEntityMatcher;
use Sidus\AdminBundle\Routing\AdminRouter;
use Sidus\BaseBundle\Translator\TranslatableTrait;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * Adds a few useful routing functions to twig templates
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class AdminExtension extends AbstractExtension
{
    use TranslatableTrait;

    public function __construct(
        protected AdminRegistry $adminRegistry,
        protected AdminEntityMatcher $adminEntityMatcher,
        protected AdminRouter $adminRouter,
        protected ManagerRegistry $managerRegistry,
        TranslatorInterface $translator,
    ) {
        $this->translator = $translator;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('get_admins', [$this->adminRegistry, 'getAdmins']),
            new TwigFunction('admin_path', [$this->adminRouter, 'generateAdminPath']),
            new TwigFunction('admin_entity_path', [$this->adminRouter, 'generateAdminEntityPath']),
            new TwigFunction('entity_path', [$this->adminRouter, 'generateEntityPath']),
            new TwigFunction('entity_admin', [$this->adminEntityMatcher, 'getAdminForEntity']),
            new TwigFunction('admin', [$this, 'getAdmin']),
            new TwigFunction('tryTrans', [$this, 'tryTrans'], ['is_safe' => ['html']]),
        ];
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('tostring', [$this, 'toString']),
        ];
    }

    public function getAdmin($code): Admin
    {
        if ($code instanceof Admin) {
            return $code;
        }

        return $this->adminRegistry->getAdmin($code);
    }

    public function tryTrans(
        string|array $tIds,
        array $parameters = [],
        ?string $fallback = null,
        bool $humanizeFallback = true
    ): ?string {
        return $this->tryTranslate($tIds, $parameters, $fallback, $humanizeFallback);
    }

    public function toString(mixed $data): string
    {
        if (is_scalar($data)) {
            return (string) $data;
        }
        if (is_array($data)) {
            return 'array[]';
        }

        if (!is_object($data)) {
            return gettype($data);
        }

        if ($data instanceof \Stringable) {
            return (string) $data;
        }

        $manager = $this->managerRegistry->getManagerForClass($data::class);
        if (!$manager instanceof EntityManagerInterface) {
            return $data::class;
        }

        $classMetadata = $manager->getClassMetadata($data::class);
        $identifiers = $classMetadata->getIdentifierValues($data);
        if (1 === count($identifiers)) {
            return $data::class.'#'.reset($identifiers);
        }

        try {
            return $data::class.json_encode($identifiers, JSON_THROW_ON_ERROR);
        } catch (\Exception) {
            return $data::class;
        }
    }

    public function getName(): string
    {
        return 'sidus_admin';
    }
}
