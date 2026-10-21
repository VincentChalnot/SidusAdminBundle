<?php

declare(strict_types=1);

namespace Sidus\AdminBundle\Twig;

use Sidus\AdminBundle\Doctrine\DoctrineHelper;
use Sidus\AdminBundle\Entity\AdminEntityMatcher;
use Sidus\AdminBundle\Model\Action;
use Sidus\AdminBundle\Model\Admin;
use Sidus\AdminBundle\Model\PermissionCheck;
use Sidus\AdminBundle\Registry\AdminRegistry;
use Sidus\AdminBundle\Request\AdminContext;
use Sidus\AdminBundle\Routing\AdminRouter;
use Sidus\AdminBundle\Templating\ActionLinkHelper;
use Sidus\AdminBundle\Translator\TranslatorHelper;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * Adds a few useful routing functions to twig templates. Stateless: `current_admin()` and `current_action()` read the
 * current request each time they are called.
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
final class AdminExtension extends AbstractExtension
{
    public function __construct(
        private readonly AdminRegistry $adminRegistry,
        private readonly AdminContext $adminContext,
        private readonly AdminEntityMatcher $adminEntityMatcher,
        private readonly AdminRouter $adminRouter,
        private readonly DoctrineHelper $doctrineHelper,
        private readonly AuthorizationCheckerInterface $authorizationChecker,
        private readonly ActionLinkHelper $actionLinkHelper,
        private readonly TranslatorHelper $translatorHelper,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('get_admins', $this->adminRegistry->all(...)),
            new TwigFunction('admin_path', $this->adminRouter->generateAdminPath(...)),
            new TwigFunction('admin_entity_path', $this->adminRouter->generateAdminEntityPath(...)),
            new TwigFunction('entity_path', $this->adminRouter->generateEntityPath(...)),
            new TwigFunction('entity_admin', $this->adminEntityMatcher->getAdminForEntity(...)),
            new TwigFunction('is_action_granted', $this->isActionGranted(...)),
            new TwigFunction('admin', $this->getAdmin(...)),
            new TwigFunction('current_admin', $this->adminContext->getAdmin(...)),
            new TwigFunction('current_action', $this->adminContext->getAction(...)),
            new TwigFunction('tryTrans', $this->translatorHelper->tryTranslate(...), ['is_safe' => ['html']]),
            new TwigFunction('action_link', $this->actionLinkHelper->renderActionLink(...), ['is_safe' => ['html']]),
            new TwigFunction('action_link_fallback', $this->renderActionLinkFallback(...), ['is_safe' => ['html']]),
        ];
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('tostring', $this->toString(...)),
        ];
    }

    public function getAdmin(Admin|string $code): Admin
    {
        return $code instanceof Admin ? $code : $this->adminRegistry->get($code);
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

        try {
            return $this->doctrineHelper->entityToString($data);
        } catch (\Exception) {
            return $data::class;
        }
    }

    public function isActionGranted(Action $action, ?object $entity = null): bool
    {
        return $this->authorizationChecker->isGranted(null, new PermissionCheck($action, $entity));
    }

    /**
     * Renders the first of the links that is rendered (the ones the user is granted)
     *
     * @param array<string, mixed> $defaultOptions
     * @param array<string, mixed> ...$optionsCollection
     */
    public function renderActionLinkFallback(array $defaultOptions, array ...$optionsCollection): string
    {
        foreach ($optionsCollection as $options) {
            $result = $this->actionLinkHelper->renderActionLink(array_merge($defaultOptions, $options));
            if ($result) {
                return $result;
            }
        }

        return '';
    }
}
