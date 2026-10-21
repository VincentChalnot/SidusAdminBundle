<?php

declare(strict_types=1);

namespace Sidus\AdminBundle\Tests\Fixtures\Controller;

use Sidus\AdminBundle\Request\AdminContext;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Twig\Environment;

/**
 * A route that is not an admin route: it reports what the AdminContext tells about the request
 */
#[AsController]
final readonly class ProbeController
{
    public function __construct(private AdminContext $adminContext, private Environment $twig)
    {
    }

    public function __invoke(): JsonResponse
    {
        return new JsonResponse([
            'admin' => $this->adminContext->getAdmin()?->getCode(),
            'action' => $this->adminContext->getAction()?->getCode(),
            'twig' => $this->twig->createTemplate("{{ current_admin() ? current_admin().code ~ '.' ~ current_action().code : 'none' }}")->render(),
        ]);
    }
}
