<?php

declare(strict_types=1);

namespace Sidus\AdminBundle\DataGrid;

use LogicException;
use Sidus\AdminBundle\Entity\AdminEntityMatcher;
use Sidus\AdminBundle\Model\Admin;
use Sidus\AdminBundle\Model\PermissionCheck;
use Sidus\AdminBundle\Registry\AdminRegistry;
use Sidus\AdminBundle\Routing\AdminRouter;
use Sidus\AdminBundle\Translator\TranslatorHelper;
use Sidus\DataGridBundle\Action\ActionResolverInterface;
use Sidus\DataGridBundle\Definition\DataGridAction;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Links a datagrid action to an admin action: in the datagrid configuration,
 *
 *     actions:
 *         create:
 *             options:
 *                 admin_action: create      # the code of the admin action: this resolver completes the action
 *                 admin: post               # optional, the code of the admin, by default the admin of the current request
 *                 entity: App\Entity\Post   # optional, the admin is the one matching this class (when no admin is given)
 *                 on_denied: hide           # optional, hide (default) or disable when the user is not granted the action
 *
 * The `uri` is the path of the admin action (the `routeParameters` of the action are its route parameters) and the
 * label is the one of the admin action translations unless the action has its own. When the current user is not
 * granted the admin action, the action gets no uri and is hidden (the `hidden` attribute) or disabled. Stateless.
 */
final readonly class AdminActionResolver implements ActionResolverInterface
{
    public const string ON_DENIED_HIDE = 'hide';
    public const string ON_DENIED_DISABLE = 'disable';

    public function __construct(
        private AdminRegistry $adminRegistry,
        private AdminEntityMatcher $adminEntityMatcher,
        private AdminRouter $adminRouter,
        private AuthorizationCheckerInterface $authorizationChecker,
        private TranslatorHelper $translatorHelper,
    ) {
    }

    public function resolve(DataGridAction $action, array $context): DataGridAction
    {
        $actionCode = $action->option('admin_action');
        if (null === $actionCode) {
            return $action;
        }

        $admin = $this->getAdmin($action, $context);
        $adminAction = $admin->getAction((string) $actionCode);

        if (null === $action->label) {
            $action = $action->withLabel($this->translatorHelper->tryTranslate(
                [
                    "sidus.admin.{$admin->getCode()}.action.{$adminAction->getCode()}.label",
                    "sidus.admin.action.{$adminAction->getCode()}.label",
                ],
                [],
                ucfirst($adminAction->getCode()),
            ));
        }

        if (!$this->authorizationChecker->isGranted(null, new PermissionCheck($adminAction))) {
            return match ($action->option('on_denied', self::ON_DENIED_HIDE)) {
                self::ON_DENIED_HIDE => $action->withDisabled(true)->withAttr([...$action->attr, 'hidden' => true]),
                self::ON_DENIED_DISABLE => $action->withDisabled(true),
                default => throw new LogicException(\sprintf("Datagrid action '%s': invalid on_denied option '%s', expected hide or disable.", $action->code, (string) $action->option('on_denied'))),
            };
        }

        return $action->withUri($this->adminRouter->generateAdminPath($admin, $adminAction->getCode(), $action->routeParameters));
    }

    /**
     * @param array{request: ?\Symfony\Component\HttpFoundation\Request, datagrid: string, role: string} $context
     */
    private function getAdmin(DataGridAction $action, array $context): Admin
    {
        if (null !== $adminCode = $action->option('admin')) {
            return $this->adminRegistry->get((string) $adminCode);
        }
        if (null !== $entity = $action->option('entity')) {
            return $this->adminEntityMatcher->getAdminForClass((string) $entity);
        }
        $admin = $context['request']?->attributes->get('_admin');
        if (!$admin instanceof Admin) {
            throw new LogicException(\sprintf(
                "Datagrid '%s', action '%s': the option admin_action needs an admin, set the 'admin' or 'entity' option or use the datagrid in an admin request.",
                $context['datagrid'],
                $action->code,
            ));
        }

        return $admin;
    }
}
