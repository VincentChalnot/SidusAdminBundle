<?php

declare(strict_types=1);

namespace Sidus\AdminBundle\Tests\Functional;

use PHPUnit\Framework\Attributes\DataProvider;
use Sidus\AdminBundle\Tests\Fixtures\Entity\Post;
use Sidus\AdminBundle\Tests\Support\AdminTestCase;
use Symfony\Component\DomCrawler\Crawler;

/**
 * The built-in actions on the "post" admin, through the whole HTTP stack: forms, redirections, flash messages and
 * permissions (roles of the admin and of the actions, entity-level rule of a voter).
 */
final class CrudFlowTest extends AdminTestCase
{
    public function testTheListIsOnlyForTheUsersWithTheRoleOfTheAdmin(): void
    {
        $client = $this->client();
        $client->request('GET', '/posts/');
        self::assertSame(401, $client->getResponse()->getStatusCode(), 'anonymous: ask to log in');

        $client = $this->client('nobody');
        $client->request('GET', '/posts/');
        self::assertSame(403, $client->getResponse()->getStatusCode(), 'logged in without ROLE_VIEWER');

        $client = $this->client('viewer');
        $crawler = $client->request('GET', '/posts/');
        self::assertResponseIs(200, $client);
        self::assertSame(['Alpha', 'Bravo'], $this->column($crawler, 2), 'first page, sorted by id');
    }

    public function testCreateSavesTheEntityRedirectsToItsEditionAndFlashes(): void
    {
        $client = $this->client('editor');
        $crawler = $client->request('GET', '/posts/create');
        self::assertResponseIs(200, $client);

        $client->submit($crawler->selectButton('Submit')->form([
            'form_post_create[title]' => 'Foxtrot',
            'form_post_create[body]' => 'Some text',
            'form_post_create[status]' => Post::DRAFT,
        ]));
        self::assertTrue($client->getResponse()->isRedirect('/posts/6/edit'), 'the redirection goes to the edit action of the new entity: '.$client->getResponse()->headers->get('Location'));
        self::assertSame(['Alpha', 'Bravo', 'Charlie', 'Delta', 'Echo', 'Foxtrot'], $this->titles());

        $crawler = $client->followRedirect();
        self::assertSame(['The entity was created'], $crawler->filter('.sidus-admin-flash')->each(static fn (Crawler $flash): string => trim($flash->text())));
        self::assertSame('Foxtrot', $crawler->filter('input[name="form_post_edit[title]"]')->attr('value'));

        $client->request('GET', '/posts/6/edit');
        self::assertCount(0, $client->getCrawler()->filter('.sidus-admin-flash'), 'a flash message is shown once');
    }

    public function testAnInvalidSubmissionRendersTheFormAgainAndSavesNothing(): void
    {
        $client = $this->client('editor');
        $crawler = $client->request('GET', '/posts/create');
        $form = $crawler->selectButton('Submit')->form(['form_post_create[title]' => 'Foxtrot']);
        $values = $form->getPhpValues();
        $values['form_post_create']['status'] = 'nonsense';
        $client->request('POST', '/posts/create', $values);

        self::assertResponseIs(200, $client);
        self::assertSame(['Alpha', 'Bravo', 'Charlie', 'Delta', 'Echo'], $this->titles());
    }

    public function testReadShowsADisabledFormWithoutSubmitButton(): void
    {
        $client = $this->client('viewer');
        $crawler = $client->request('GET', '/posts/2');

        self::assertResponseIs(200, $client);
        self::assertSame('Bravo', $crawler->filter('input[name="form_post_read[title]"]')->attr('value'));
        self::assertNotNull($crawler->filter('input[name="form_post_read[title]"]')->attr('disabled'));
        self::assertCount(0, $crawler->filter('button[type=submit]'));
        self::assertSame('/posts/', $crawler->filter('a[href="/posts/"]')->attr('href'), 'the back link goes to the list');
    }

    public function testEditSavesAndStaysOnTheEditPage(): void
    {
        $client = $this->client('editor');
        $crawler = $client->request('GET', '/posts/3/edit');
        $client->submit($crawler->selectButton('Submit')->form(['form_post_edit[title]' => 'Charlie, edited']));

        self::assertTrue($client->getResponse()->isRedirect('/posts/3/edit'));
        self::assertSame(['Alpha', 'Bravo', 'Charlie, edited', 'Delta', 'Echo'], $this->titles());
        self::assertSame(['The entity was saved'], $client->followRedirect()->filter('.sidus-admin-flash')->each(static fn (Crawler $flash): string => trim($flash->text())));
    }

    public function testCloneSavesACopyAsANewEntity(): void
    {
        $client = $this->client('editor');
        $crawler = $client->request('GET', '/posts/1/clone');
        self::assertSame('Alpha', $crawler->filter('input[name="form_post_clone[title]"]')->attr('value'), 'the form starts from the original');

        $client->submit($crawler->selectButton('Submit')->form(['form_post_clone[title]' => 'Alpha again']));

        self::assertTrue($client->getResponse()->isRedirect('/posts/6/edit'), 'the copy has its own identity: '.$client->getResponse()->headers->get('Location'));
        self::assertSame(['Alpha', 'Bravo', 'Charlie', 'Delta', 'Echo', 'Alpha again'], $this->titles());
    }

    public function testDeleteAsksForAConfirmationThenDeletesAndRedirectsToTheList(): void
    {
        $client = $this->client('admin');
        $crawler = $client->request('GET', '/posts/2/delete');
        self::assertStringContainsString("Are you sure you want to delete the entity 'Bravo'?", $crawler->filter('.sidus-admin-message')->text());
        self::assertSame('danger', $crawler->filter('button[type=submit]')->attr('data-severity'));
        self::assertSame(['Alpha', 'Bravo', 'Charlie', 'Delta', 'Echo'], $this->titles(), 'viewing the confirmation deletes nothing');

        $client->submit($crawler->selectButton('Delete')->form());

        self::assertTrue($client->getResponse()->isRedirect('/posts/'));
        self::assertSame(['Alpha', 'Charlie', 'Delta', 'Echo'], $this->titles());
        self::assertSame(['The entity was deleted'], $client->followRedirect()->filter('.sidus-admin-flash')->each(static fn (Crawler $flash): string => trim($flash->text())));
    }

    public function testAnUnknownEntityIsNotFound(): void
    {
        $client = $this->client('viewer');
        $client->request('GET', '/posts/999');

        self::assertResponseIs(404, $client);
    }

    public function testACustomActionReceivesItsActionAndAdminAsArguments(): void
    {
        $client = $this->client('editor');
        $client->request('GET', '/posts/1/publish');
        self::assertResponseIs(405, $client);

        $client->request('POST', '/posts/1/publish');

        self::assertTrue($client->getResponse()->isRedirect('/posts/'), 'redirected to the list action of its admin');
        self::assertSame(Post::PUBLISHED, $this->manager()->getConnection()->fetchOne('SELECT status FROM post WHERE id = 1'));
    }

    /**
     * @return iterable<string, array{string, string, string, int}>
     */
    public static function permissions(): iterable
    {
        yield 'a viewer cannot create' => ['viewer', 'GET', '/posts/create', 403];
        yield 'an editor can create' => ['editor', 'GET', '/posts/create', 200];
        yield 'a viewer cannot edit' => ['viewer', 'GET', '/posts/1/edit', 403];
        yield 'an editor cannot delete' => ['editor', 'GET', '/posts/1/delete', 403];
        yield 'an admin can delete' => ['admin', 'GET', '/posts/1/delete', 200];
        yield 'an editor can edit an unlocked post' => ['editor', 'GET', '/posts/1/edit', 200];
        yield 'nobody can edit a locked post, not even an editor' => ['editor', 'GET', '/posts/4/edit', 403];
        yield 'nor an admin (entity-level rule)' => ['admin', 'GET', '/posts/4/edit', 403];
        yield 'cloning a locked post only needs ROLE_EDITOR' => ['admin', 'GET', '/posts/4/clone', 200];
        yield 'nobody can delete a locked post' => ['admin', 'GET', '/posts/4/delete', 403];
        yield 'everybody can read it' => ['viewer', 'GET', '/posts/4', 200];
        yield 'a viewer cannot run a custom action' => ['viewer', 'POST', '/posts/1/publish', 403];
        yield 'anonymous users are asked to log in' => ['', 'GET', '/posts/create', 401];
    }

    #[DataProvider('permissions')]
    public function testPermissions(string $user, string $method, string $uri, int $status): void
    {
        $client = $this->client('' === $user ? null : $user);
        $client->request($method, $uri);

        self::assertResponseIs($status, $client);
    }

    public function testADeniedActionNeverTouchesTheEntity(): void
    {
        $client = $this->client('editor');
        $client->request('POST', '/posts/4/edit', ['form_post_edit' => ['title' => 'Hacked', 'status' => Post::DRAFT]]);

        self::assertResponseIs(403, $client);
        self::assertSame(['Alpha', 'Bravo', 'Charlie', 'Delta', 'Echo'], $this->titles());
    }

    public function testResponsesOfTheAdminAreNeverCached(): void
    {
        $client = $this->client('viewer');
        $client->request('GET', '/posts/');

        $cacheControl = (string) $client->getResponse()->headers->get('Cache-Control');
        self::assertStringContainsString('no-store', $cacheControl);
        self::assertStringContainsString('private', $cacheControl);
    }

    private static function assertResponseIs(int $status, \Symfony\Bundle\FrameworkBundle\KernelBrowser $client): void
    {
        self::assertSame($status, $client->getResponse()->getStatusCode(), mb_substr(strip_tags((string) $client->getResponse()->getContent()), 0, 600));
    }

    /**
     * @return list<string> the text of the cells of a column (1-based) of the datagrid
     */
    private function column(Crawler $crawler, int $position): array
    {
        return $crawler->filter('table tbody tr')->each(static fn (Crawler $row): string => trim($row->filter('td')->eq($position - 1)->text()));
    }
}
