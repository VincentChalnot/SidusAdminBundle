<?php

declare(strict_types=1);

namespace Sidus\AdminBundle\Tests\Functional;

use Sidus\AdminBundle\Model\Action;
use Sidus\AdminBundle\Model\Admin;
use Sidus\AdminBundle\Registry\AdminRegistry;
use Sidus\AdminBundle\Request\AdminContext;
use Sidus\AdminBundle\Tests\App\TestKernel;
use Sidus\AdminBundle\Tests\Fixtures\Entity\Post;
use Sidus\AdminBundle\Tests\Support\AdminTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

/**
 * One booted kernel serving many requests in a row, like FrankenPHP's worker mode does: whatever a request does, the
 * next one must not see it (the admin, the action, the filters, the sort, the page, the datagrid, the query...).
 */
final class WorkerModeTest extends AdminTestCase
{
    public static function setUpBeforeClass(): void
    {
        // A third admin over the same entity: its own list action restricts the query to a status given by the query string
        static::bootKernel(TestKernel::admins() + [
            'by_status' => [
                'entity' => Post::class,
                'prefix' => '/by-status',
                'controller_pattern' => ['Sidus\AdminBundle\Tests\Fixtures\Restricted\{{Action}}Action'],
                'options' => ['datagrid' => 'readonly_posts'],
                'actions' => ['list' => ['path' => '/']],
            ],
        ]);
    }

    public function testTheQueryOfAListNeverLeaksIntoTheNextRequest(): void
    {
        self::assertSame(['Alpha', 'Bravo'], $this->titlesOf($this->ok('/posts/', 'viewer')), 'first page');
        self::assertSame(['Alpha', 'Bravo'], $this->titlesOf($this->ok('/posts/?filters[title]=a', 'viewer')), 'titles with an "a": Alpha, Bravo, Charlie, Delta');
        self::assertSame(['Echo', 'Delta'], $this->titlesOf($this->ok('/posts/?filters[sort]=-title', 'viewer')), 'sorted by title, descending');
        self::assertSame(['Charlie', 'Delta'], $this->titlesOf($this->ok('/posts/?page=2', 'viewer')), 'second page');
        self::assertSame(['Delta'], $this->titlesOf($this->ok('/posts/?filters[status]=locked', 'viewer')));

        $crawler = $this->ok('/posts/', 'viewer');
        self::assertSame(['Alpha', 'Bravo'], $this->titlesOf($crawler), 'the default list again: no filter, no sort, no page of the previous requests');
        self::assertSame('', (string) $crawler->filter('input[name="filters[title]"]')->attr('value'));
        self::assertSame('1', trim($crawler->filter('.page-item.active')->text()));
        self::assertSame('', (string) $crawler->filter('select[name="filters[status]"] option[selected]')->count() ? 'selected' : '');
    }

    public function testACustomizerOfTheListActionAppliesToItsRequestOnly(): void
    {
        self::assertSame(['Alpha', 'Charlie'], $this->titlesOf($this->ok('/by-status/?status=draft', 'viewer')));
        self::assertSame(['Bravo', 'Echo'], $this->titlesOf($this->ok('/by-status/?status=published', 'viewer')));
        self::assertSame(['Alpha', 'Bravo'], $this->titlesOf($this->ok('/posts/', 'viewer')), 'the list of another admin is not restricted');
        self::assertSame(['Alpha', 'Charlie'], $this->titlesOf($this->ok('/by-status/?status=draft', 'viewer')), 'the same request gives the same page');
    }

    public function testTheCurrentAdminAndActionAreThoseOfTheRequestBeingHandled(): void
    {
        $context = $this->service(AdminContext::class);

        self::assertSame(['admin' => null, 'action' => null, 'twig' => 'none'], $this->probe('viewer'));

        $this->ok('/posts/', 'viewer');
        self::assertNull($context->getAdmin(), 'nothing remains once the request is terminated');
        self::assertSame(['admin' => null, 'action' => null, 'twig' => 'none'], $this->probe('viewer'), 'a request to a route that is not an admin one');

        $this->ok('/tags/php/edit', 'viewer');
        self::assertSame(['admin' => null, 'action' => null, 'twig' => 'none'], $this->probe('viewer'));
        self::assertNull($context->getAction());
    }

    public function testEveryRequestShowsItsOwnAdminActionAndEntity(): void
    {
        $sections = [];
        foreach ([['/posts/1/edit', 'editor'], ['/tags/php/edit', 'viewer'], ['/posts/3/edit', 'editor'], ['/posts/2', 'viewer'], ['/tags/', 'viewer'], ['/posts/', 'viewer']] as [$uri, $user]) {
            $section = $this->ok($uri, $user)->filter('[data-admin]');
            $sections[] = $section->count() ? $section->attr('data-admin').'.'.$section->attr('data-action') : 'datagrid';
        }
        self::assertSame(['post.edit', 'tag.edit', 'post.edit', 'post.read', 'datagrid', 'datagrid'], $sections);

        self::assertSame('Alpha', $this->ok('/posts/1/edit', 'editor')->filter('input[name="form_post_edit[title]"]')->attr('value'));
        self::assertSame('PHP', $this->ok('/tags/php/edit', 'viewer')->filter('input[name="form_tag_edit[label]"]')->attr('value'));
        self::assertSame('Charlie', $this->ok('/posts/3/edit', 'editor')->filter('input[name="form_post_edit[title]"]')->attr('value'));
        self::assertSame('Alpha', $this->ok('/posts/1/edit', 'editor')->filter('input[name="form_post_edit[title]"]')->attr('value'), 'back to the first one');
    }

    public function testADeniedRequestDoesNotAffectTheNextOnes(): void
    {
        self::assertSame(403, $this->handle('/posts/4/edit', 'editor')->getStatusCode());
        self::assertSame(401, $this->handle('/posts/', null)->getStatusCode());
        self::assertSame(404, $this->handle('/posts/999', 'viewer')->getStatusCode());
        self::assertSame(['Alpha', 'Bravo'], $this->titlesOf($this->ok('/posts/', 'viewer')));
        self::assertSame(['admin' => null, 'action' => null, 'twig' => 'none'], $this->probe('viewer'));
    }

    public function testTheAdminsAreTheSameImmutableObjectsForAllTheRequests(): void
    {
        $registry = $this->service(AdminRegistry::class);
        $before = spl_object_id($registry->get('post'));
        $seen = [];
        self::kernel()->getContainer()->get('event_dispatcher')->addListener(
            \Symfony\Component\HttpKernel\KernelEvents::CONTROLLER,
            static function (\Symfony\Component\HttpKernel\Event\ControllerEvent $event) use (&$seen): void {
                $admin = $event->getRequest()->attributes->get('_admin');
                $action = $event->getRequest()->attributes->get('_action');
                if ($admin instanceof Admin && $action instanceof Action) {
                    $seen[] = [spl_object_id($admin), $action->getCode(), $action->getAdmin() === $admin];
                }
            },
        );

        $this->ok('/posts/', 'viewer');
        $this->ok('/posts/1/edit', 'editor');
        $this->ok('/tags/', 'viewer');

        self::assertSame([[$before, 'list', true], [$before, 'edit', true], [spl_object_id($registry->get('tag')), 'list', true]], $seen);
    }

    private function ok(string $uri, ?string $user): Crawler
    {
        $response = $this->handle($uri, $user);
        self::assertSame(Response::HTTP_OK, $response->getStatusCode(), $uri.': '.mb_substr(strip_tags((string) $response->getContent()), 0, 400));

        return new Crawler((string) $response->getContent());
    }

    /**
     * @return list<string> the titles in the rows of the datagrid
     */
    private function titlesOf(Crawler $crawler): array
    {
        return $crawler->filter('table tbody tr')->each(static fn (Crawler $row): string => trim($row->filter('td')->eq(1)->text()));
    }

    /**
     * What a route that is not an admin one, and its template, tell about the current admin and action
     *
     * @return array<string, string|null>
     */
    private function probe(string $user): array
    {
        $data = json_decode((string) $this->handle('/probe', $user)->getContent(), true, flags: \JSON_THROW_ON_ERROR);
        \assert(\is_array($data));

        return $data;
    }
}
