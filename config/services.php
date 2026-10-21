<?php

declare(strict_types=1);

use Sidus\AdminBundle\Action\CloneAction;
use Sidus\AdminBundle\Action\CreateAction;
use Sidus\AdminBundle\Action\DeleteAction;
use Sidus\AdminBundle\Action\EditAction;
use Sidus\AdminBundle\Action\ListAction;
use Sidus\AdminBundle\Action\ReadAction;
use Sidus\AdminBundle\DataGrid\AdminActionResolver;
use Sidus\AdminBundle\Doctrine\DoctrineHelper;
use Sidus\AdminBundle\Entity\AdminEntityMatcher;
use Sidus\AdminBundle\Event\ActionRedirectionSubscriber;
use Sidus\AdminBundle\Event\ActionRendererSubscriber;
use Sidus\AdminBundle\Event\ActionSecurityVoterSubscriber;
use Sidus\AdminBundle\Event\AdminResolverSubscriber;
use Sidus\AdminBundle\Event\CacheSubscriber;
use Sidus\AdminBundle\Form\FormHelper;
use Sidus\AdminBundle\Form\Type\ActionLinkType;
use Sidus\AdminBundle\Registry\AdminRegistry;
use Sidus\AdminBundle\Request\AdminArgumentValueResolver;
use Sidus\AdminBundle\Request\AdminContext;
use Sidus\AdminBundle\Request\AdminEntityValueResolver;
use Sidus\AdminBundle\Routing\AdminRouteLoader;
use Sidus\AdminBundle\Routing\AdminRouter;
use Sidus\AdminBundle\Routing\RoutingHelper;
use Sidus\AdminBundle\Security\PermissionCheckerHelper;
use Sidus\AdminBundle\Security\Voter\EntityVoter;
use Sidus\AdminBundle\Session\FlashHelper;
use Sidus\AdminBundle\Templating\ActionLinkHelper;
use Sidus\AdminBundle\Templating\TemplateResolver;
use Sidus\AdminBundle\Templating\TemplateResolverInterface;
use Sidus\AdminBundle\Templating\TemplatingHelper;
use Sidus\AdminBundle\Translator\TranslatorHelper;
use Sidus\AdminBundle\Twig\AdminExtension;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $services = $container->services()->defaults()->autowire()->autoconfigure();

    // The admins: filled by the AdminDefinitionPass, once the whole configuration is known
    $services->set(AdminRegistry::class)->args(['$admins' => []]);

    // Stateless helpers: no service keeps anything from a request
    $services->set(AdminContext::class);
    $services->set(AdminEntityMatcher::class);
    $services->set(AdminRouter::class);
    $services->set(RoutingHelper::class);
    $services->set(FormHelper::class);
    $services->set(TemplatingHelper::class);
    $services->set(TemplateResolver::class);
    $services->alias(TemplateResolverInterface::class, TemplateResolver::class);
    $services->set(TranslatorHelper::class);
    $services->set(FlashHelper::class);
    $services->set(DoctrineHelper::class);
    $services->set(PermissionCheckerHelper::class);
    $services->set(ActionLinkHelper::class)->args(['$defaultOptions' => []]);
    $services->set(ActionLinkType::class);

    // Routing, security, request/response flow
    $services->set(AdminRouteLoader::class)->tag('routing.loader');
    $services->set(EntityVoter::class);
    $services->set(AdminResolverSubscriber::class);
    $services->set(ActionSecurityVoterSubscriber::class);
    $services->set(ActionRendererSubscriber::class);
    $services->set(ActionRedirectionSubscriber::class);
    $services->set(CacheSubscriber::class);
    $services->set(AdminArgumentValueResolver::class);
    $services->set(AdminEntityValueResolver::class);

    $services->set(AdminExtension::class);
    $services->set(AdminActionResolver::class);

    // The built-in actions, to be referenced by the controller_pattern of the admins
    foreach ([ListAction::class, ReadAction::class, CreateAction::class, EditAction::class, CloneAction::class, DeleteAction::class] as $action) {
        $services->set($action);
    }
};
