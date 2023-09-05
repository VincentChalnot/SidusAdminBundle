<?php
declare(strict_types=1);

namespace Sidus\AdminBundle\Templating;

use Sidus\AdminBundle\Configuration\AdminRegistry;
use Sidus\AdminBundle\Model\Action;
use Sidus\AdminBundle\Model\Admin;
use Sidus\AdminBundle\Model\ActionLink;
use Sidus\AdminBundle\Model\PermissionCheck;
use Sidus\AdminBundle\Routing\AdminRouter;
use Sidus\AdminBundle\Translator\TranslatorHelper;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Twig\Environment;

class ActionLinkHelper
{
    private const DEFAULTS = [
        'admin' => null,
        'entity' => null,
        'label' => null, // This will propagate to the title and the content by default
        'content' => true,
        'title' => true,
        'icon' => true,
        'icon_template' => '<i class="fas fa-{icon}"></i>',
        'class' => null,
        'attr' => [],
        'url' => null,
        'route_params' => [],
        'route_reference_type' => UrlGeneratorInterface::ABSOLUTE_PATH,
    ];

    public function __construct(
        protected AdminRegistry $adminRegistry,
        protected AuthorizationCheckerInterface $authorizationChecker,
        protected AdminRouter $adminRouter,
        protected TranslatorHelper $translatorHelper,
        protected Environment $environment,
        protected array $defaultOptions = [],
    ) {
        $this->defaultOptions = array_merge(self::DEFAULTS, $this->defaultOptions);
    }

    public function renderActionLink(array $options): string
    {
        try {
            $actionLink = $this->getActionLink($options);
        } catch (\Exception $e) {
            return '';
        }
        $subject = new PermissionCheck($actionLink->action, $actionLink->entity);
        if (!$this->authorizationChecker->isGranted(null, $subject)) {
            return '';
        }

        $attrs = [];
        foreach ($actionLink->attr as $key => $value) {
            $value = htmlentities($value);
            $attrs[] = "{$key}=\"{$value}\"";
        }
        $attrs = implode(' ', $attrs);

        return "<a {$attrs}>{$actionLink->content}</a>";
    }

    public function getActionLink(array $options): ActionLink
    {
        $resolver = new OptionsResolver();
        $this->configure($resolver);
        $options = $resolver->resolve($options);

        return new ActionLink(
            action: $options['action'],
            url: $options['url'],
            entity: $options['entity'],
            content: $options['content'],
            attr: $options['attr'],
        );
    }

    public function configure(OptionsResolver $resolver): void
    {
        $resolver->setDefaults($this->defaultOptions);
        $resolver->setRequired(['action']);
        $resolver->setAllowedTypes('admin', ['null', 'string', Admin::class]);
        $resolver->setAllowedTypes('action', ['string', Action::class]);
        $resolver->setAllowedTypes('entity', ['null', 'object']);
        $resolver->setAllowedTypes('label', ['null', 'string']);
        $resolver->setAllowedTypes('icon', ['bool', 'string']);
        $resolver->setAllowedTypes('icon_template', ['string']);
        $resolver->setAllowedTypes('title', ['bool', 'string']);
        $resolver->setAllowedTypes('content', ['bool', 'string']);
        $resolver->setAllowedTypes('class', ['null', 'string']);
        $resolver->setAllowedTypes('attr', ['array']);
        $resolver->setAllowedTypes('url', ['null', 'string']);
        $resolver->setAllowedTypes('route_params', ['array']);
        $resolver->setAllowedTypes('route_reference_type', ['int']);

        $resolver->addNormalizer(
            'admin',
            function (OptionsResolver $resolver, Admin|string|null $admin) {
                if (null === $admin) {
                    return null;
                }
                if ($admin instanceof Admin) {
                    return $admin;
                }

                return $this->adminRegistry->getAdmin($admin);
            },
        );

        $resolver->addNormalizer(
            'action',
            function (OptionsResolver $resolver, Action|string $action) {
                if ($action instanceof Action) {
                    return $action;
                }
                if (null === $admin = $resolver->offsetGet('admin')) {
                    throw new \UnexpectedValueException('Admin is required');
                }

                return $admin->getAction($action);
            },
        );

        $resolver->addNormalizer(
            'label',
            function (OptionsResolver $resolver, ?string $label) {
                if (null !== $label) {
                    return $label;
                }
                /** @var Action $action */
                $action = $resolver->offsetGet('action');

                return $this->translatorHelper->tryTranslate(
                    [
                        "sidus.admin.{$action->getAdmin()->getCode()}.action.{$action->getCode()}.label",
                        "sidus.admin.{$action->getCode()}.label",
                    ],
                    [],
                    ucfirst($action->getCode())
                );
            },
        );

        $resolver->addNormalizer(
            'title',
            function (OptionsResolver $resolver, bool|string $title) {
                if (false === $title) {
                    return false;
                }
                if (true === $title) {
                    return $resolver->offsetGet('label');
                }

                return $title;
            }
        );

        $resolver->addNormalizer(
            'icon',
            function (OptionsResolver $resolver, bool|string $icon) {
                if (false === $icon) {
                    return false;
                }
                if (true === $icon) {
                    return strtolower($resolver->offsetGet('action')->getCode());
                }

                return $icon;
            }
        );

        $resolver->addNormalizer(
            'attr',
            function (OptionsResolver $resolver, array $attr) {
                $class = $resolver->offsetGet('class');
                if (null !== $class && !array_key_exists('class', $attr)) {
                    $attr['class'] = $class;
                }
                $title = $resolver->offsetGet('title');
                if (false !== $title && !array_key_exists('title', $attr)) {
                    $attr['title'] = $title;
                }
                $attr['href'] = $resolver->offsetGet('url');

                return $attr;
            },
        );

        $resolver->addNormalizer(
            'content',
            function (OptionsResolver $resolver, bool|string $content) {
                $finalContent = [];
                $icon = $resolver->offsetGet('icon');
                if ($icon) {
                    $finalContent[] = strtr($resolver->offsetGet('icon_template'), ['{icon}' => $icon]);
                }
                if (true === $content) {
                    $finalContent[] = $resolver->offsetGet('label');
                } elseif (false !== $content) {
                    $finalContent[] = $content;
                }

                return implode(" ", $finalContent);
            },
        );

        $resolver->addNormalizer(
            'url',
            function (OptionsResolver $resolver, string|null $url) {
                if (null !== $url) {
                    return $url;
                }
                $action = $resolver->offsetGet('action');
                $entity = $resolver->offsetGet('entity');
                $routeParams = $resolver->offsetGet('route_params');
                $referenceType = $resolver->offsetGet('route_reference_type');
                if ($entity) {
                    return $this->adminRouter->generateAdminEntityPath(
                        admin: $action->getAdmin(),
                        entity: $entity,
                        actionCode: $action->getCode(),
                        parameters: $routeParams,
                        referenceType: $referenceType,
                    );
                }

                return $this->adminRouter->generateAdminPath(
                    admin: $action->getAdmin(),
                    actionCode: $action->getCode(),
                    parameters: $routeParams,
                    referenceType: $referenceType,
                );
            },
        );
    }
}
