<?php

declare(strict_types=1);

namespace Sidus\AdminBundle\Tests\Fixtures\Restricted;

use Sidus\AdminBundle\Action\ListAction as BaseListAction;
use Sidus\AdminBundle\Model\Action;
use Sidus\DoctrineFilterBundle\Query\DoctrineQueryContext;
use Sidus\DoctrineFilterBundle\Query\DoctrineQueryHandler;
use Sidus\FilterBundle\Provider\QueryHandlerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;

/**
 * The extension point of the list: restricts the query to the status of the query string, for this request only
 */
#[AsController]
final readonly class ListAction extends BaseListAction
{
    protected function customizeHandler(QueryHandlerInterface $handler, Request $request, Action $action): QueryHandlerInterface
    {
        \assert($handler instanceof DoctrineQueryHandler);
        $status = (string) $request->query->get('status', 'draft');

        return $handler->withCustomizer(static function (DoctrineQueryContext $context) use ($status): void {
            $context->queryBuilder()->andWhere($context->rootAlias().'.status = '.$context->parameter($status));
        });
    }
}
