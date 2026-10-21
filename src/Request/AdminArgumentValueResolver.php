<?php

declare(strict_types=1);

namespace Sidus\AdminBundle\Request;

use LogicException;
use Sidus\AdminBundle\Model\Action;
use Sidus\AdminBundle\Model\Admin;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

/**
 * Gives the arguments of a controller typed with Action or Admin the admin or the action the request is routed to:
 *
 *     public function __invoke(Request $request, Action $action, Admin $admin): ActionResponseInterface
 *
 * This is how an action knows what it is configured as, the actions being stateless services shared by all the
 * admins and all the requests.
 */
final readonly class AdminArgumentValueResolver implements ValueResolverInterface
{
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        $type = $argument->getType();
        if (Action::class !== $type && Admin::class !== $type) {
            return [];
        }

        $attribute = Action::class === $type ? '_action' : '_admin';
        $value = $request->attributes->get($attribute);
        if (!$value instanceof $type) {
            $route = $request->attributes->get('_route');
            throw new LogicException(\sprintf(
                "Unable to resolve the argument \$%s: the request attribute '%s' of the route '%s' is %s, this means the route was declared outside the admin configuration. Declare the action in sidus_admin.configurations.",
                $argument->getName(),
                $attribute,
                \is_string($route) ? $route : 'unknown',
                null === $value ? 'missing' : 'not an '.$type,
            ));
        }

        return [$value];
    }
}
