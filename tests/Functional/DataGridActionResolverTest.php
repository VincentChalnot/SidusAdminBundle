<?php

declare(strict_types=1);

namespace Sidus\AdminBundle\Tests\Functional;

use PHPUnit\Framework\Attributes\DataProvider;
use Sidus\AdminBundle\DataGrid\AdminActionResolver;
use Sidus\AdminBundle\Registry\AdminRegistry;
use Sidus\AdminBundle\Tests\Support\AdminTestCase;
use Sidus\DataGridBundle\Definition\DataGridAction;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;

/**
 * The datagrid actions that point to an admin action (`options.admin_action`): the uri of the action when the user
 * is granted it, hidden or disabled when he is not
 */
final class DataGridActionResolverTest extends AdminTestCase
{
    /**
     * @param array<string, mixed> $options
     * @param array<string, mixed> $routeParameters
     *
     * @return array{DataGridAction, array{request: ?Request, datagrid: string, role: string}}
     */
    private static function link(array $options, array $routeParameters = [], ?string $label = null, bool $inAdminRequest = true): array
    {
        $request = Request::create('/posts/');
        if ($inAdminRequest) {
            $request->attributes->set('_admin', 'post');
        }

        return [new DataGridAction('act', DataGridAction::TYPE_LINK, $label, routeParameters: $routeParameters, options: $options), ['request' => $request, 'datagrid' => 'posts', 'role' => 'action']];
    }

    /**
     * @param array{DataGridAction, array{request: ?Request, datagrid: string, role: string}} $call
     */
    private function resolve(array $call): DataGridAction
    {
        [$action, $context] = $call;
        if ($context['request']?->attributes->get('_admin') === 'post') {
            $context['request']->attributes->set('_admin', $this->service(AdminRegistry::class)->get('post'));
        }

        return $this->service(AdminActionResolver::class)->resolve($action, $context);
    }

    public function testAGrantedActionGetsTheUriOfTheAdminActionAndItsLabel(): void
    {
        $this->authenticate('editor');

        $resolved = $this->resolve(self::link(['admin_action' => 'create']));

        self::assertSame('/posts/create', $resolved->uri);
        self::assertSame('Create', $resolved->label, 'the translation of the admin action');
        self::assertFalse($resolved->disabled);
        self::assertArrayNotHasKey('hidden', $resolved->attr);
    }

    public function testTheLabelOfTheActionIsKept(): void
    {
        $this->authenticate('editor');

        self::assertSame('New post', $this->resolve(self::link(['admin_action' => 'create'], label: 'New post'))->label);
    }

    public function testTheRouteParametersOfTheActionAreThoseOfTheRoute(): void
    {
        $this->authenticate('admin');

        self::assertSame('/posts/3/delete', $this->resolve(self::link(['admin_action' => 'delete'], ['id' => 3]))->uri);
        self::assertSame('/posts/?page=2', $this->resolve(self::link(['admin_action' => 'list'], ['page' => 2]))->uri, 'extra parameters end up in the query string');
    }

    public function testAnActionThatIsNotGrantedIsHiddenByDefault(): void
    {
        $this->authenticate('viewer');

        $resolved = $this->resolve(self::link(['admin_action' => 'create']));

        self::assertNull($resolved->uri, 'no way to know where the action is');
        self::assertTrue($resolved->disabled);
        self::assertTrue($resolved->attr['hidden']);
    }

    public function testAnActionThatIsNotGrantedCanBeDisabledInstead(): void
    {
        $this->authenticate('viewer');

        $resolved = $this->resolve(self::link(['admin_action' => 'create', 'on_denied' => 'disable']));

        self::assertNull($resolved->uri);
        self::assertTrue($resolved->disabled);
        self::assertArrayNotHasKey('hidden', $resolved->attr);
    }

    public function testTheAdminIsGivenByItsCodeOrByItsEntityOtherwiseItIsTheOneOfTheRequest(): void
    {
        $this->authenticate('viewer');

        self::assertSame('/tags/', $this->resolve(self::link(['admin_action' => 'list', 'admin' => 'tag'], inAdminRequest: false))->uri);
        self::assertSame('/tags/', $this->resolve(self::link(['admin_action' => 'list', 'entity' => \Sidus\AdminBundle\Tests\Fixtures\Entity\Tag::class], inAdminRequest: false))->uri);
        self::assertSame('/posts/', $this->resolve(self::link(['admin_action' => 'list']))->uri, 'the admin of the request');
    }

    public function testWithoutAnAdminThereIsNothingToLinkTo(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage("Datagrid 'posts', action 'act': the option admin_action needs an admin");

        $this->resolve(self::link(['admin_action' => 'list'], inAdminRequest: false));
    }

    public function testAnUnknownAdminActionIsReported(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage("No action with code 'nope' for admin 'post'");

        $this->resolve(self::link(['admin_action' => 'nope']));
    }

    public function testAnInvalidOnDeniedIsReported(): void
    {
        $this->authenticate('viewer');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage("invalid on_denied option 'maybe', expected hide or disable");

        $this->resolve(self::link(['admin_action' => 'create', 'on_denied' => 'maybe']));
    }

    public function testTheOtherActionsAreLeftAlone(): void
    {
        $action = new DataGridAction('plain', uri: '/somewhere');

        self::assertSame($action, $this->service(AdminActionResolver::class)->resolve($action, ['request' => null, 'datagrid' => 'posts', 'role' => 'action']));
    }

    /**
     * @return iterable<string, array{string, bool, bool, list<string>}>
     */
    public static function toolbars(): iterable
    {
        // user => the create link is visible, the delete link is enabled, the links of the toolbar that point somewhere
        yield 'admin' => ['admin', true, true, ['/posts/create', '/tags/', '/posts/1/delete']];
        yield 'editor' => ['editor', true, false, ['/posts/create', '/tags/']];
        yield 'viewer' => ['viewer', false, false, ['/tags/']];
    }

    /**
     * @param list<string> $links
     */
    #[DataProvider('toolbars')]
    public function testTheListPageRendersTheActionsTheUserMayRun(string $user, bool $create, bool $delete, array $links): void
    {
        $client = $this->client($user);
        $crawler = $client->request('GET', '/posts/');
        self::assertSame(200, $client->getResponse()->getStatusCode());

        $toolbar = $crawler->filter('.sidus-datagrid-toolbar-start a.sidus-datagrid-action');
        $hrefs = $toolbar->reduce(static fn (Crawler $a): bool => '#' !== $a->attr('href'))->each(static fn (Crawler $a): string => (string) $a->attr('href'));
        self::assertSame($links, $hrefs);

        $createLink = $toolbar->reduce(static fn (Crawler $a): bool => 'Create' === trim($a->text()));
        self::assertSame($create, null === $createLink->attr('hidden'), 'the create link is hidden for the users that cannot create');

        $deleteLink = $toolbar->reduce(static fn (Crawler $a): bool => 'Delete everything' === trim($a->text()));
        self::assertSame(!$delete, 'true' === $deleteLink->attr('aria-disabled'), 'the delete link is disabled (not hidden) for the users that cannot delete');
        self::assertNull($deleteLink->attr('hidden'));
    }
}
