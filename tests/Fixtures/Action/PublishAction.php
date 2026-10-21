<?php

declare(strict_types=1);

namespace Sidus\AdminBundle\Tests\Fixtures\Action;

use Doctrine\ORM\EntityManagerInterface;
use Sidus\AdminBundle\Attribute\AdminEntity;
use Sidus\AdminBundle\Model\Action;
use Sidus\AdminBundle\Model\Admin;
use Sidus\AdminBundle\Request\ActionResponseInterface;
use Sidus\AdminBundle\Request\RedirectActionResponse;
use Sidus\AdminBundle\Tests\Fixtures\Entity\Post;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;

/**
 * A custom action as an application writes it: a stateless service that is given the action and the admin it is
 * routed to as arguments.
 */
#[AsController]
final readonly class PublishAction
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function __invoke(
        Request $request,
        Action $action,
        Admin $admin,
        #[AdminEntity]
        Post $data,
    ): ActionResponseInterface {
        $data->setStatus(Post::PUBLISHED);
        $this->entityManager->flush();

        return new RedirectActionResponse(
            action: $admin->getAction($action->getOption('redirect_action', 'list')),
        );
    }
}
