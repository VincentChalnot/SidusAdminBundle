<?php

declare(strict_types=1);

namespace Sidus\AdminBundle\Tests\Functional;

use PHPUnit\Framework\Attributes\DataProvider;
use Sidus\AdminBundle\Templating\TemplateResolverInterface;
use Sidus\AdminBundle\Tests\App\TestKernel;
use Sidus\AdminBundle\Tests\Support\AdminTestCase;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Out of the box, the admin pages are plain semantic HTML: no class of any CSS framework, and everything a theme or
 * an application needs to style it is a block, a data attribute or a class of the bundle
 */
final class NeutralTemplatesTest extends AdminTestCase
{
    /** Classes of Bootstrap and Bulma that would show a framework leaking into the neutral templates */
    private const array FRAMEWORK_CLASSES = [
        'btn', 'btn-primary', 'btn-danger', 'btn-default', 'form-control', 'form-group', 'form-label', 'row', 'container', 'alert', 'alert-info', 'alert-danger',
        'card', 'col-lg-4', 'pull-right', 'btn-group', 'table',
        'button', 'is-primary', 'is-danger', 'is-info', 'is-light', 'is-success', 'field', 'control', 'columns', 'column', 'notification', 'section',
        'title', 'help', 'label', 'input', 'textarea', 'select', 'checkbox', 'radio', 'delete',
    ];

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function pages(): iterable
    {
        yield 'create' => ['editor', '/posts/create'];
        yield 'edit' => ['editor', '/posts/3/edit'];
        yield 'clone' => ['editor', '/posts/3/clone'];
        yield 'read' => ['viewer', '/posts/3'];
        yield 'delete' => ['admin', '/posts/3/delete'];
    }

    #[DataProvider('pages')]
    public function testNoClassOfACssFrameworkIsRendered(string $user, string $uri): void
    {
        $client = $this->client($user);
        $crawler = $client->request('GET', $uri);
        self::assertSame(200, $client->getResponse()->getStatusCode());

        self::assertSame([], array_intersect(self::classes($crawler), self::FRAMEWORK_CLASSES), 'framework classes in '.$uri);
        self::assertSame(['sidus-admin-actions', 'sidus-admin-action'], array_values(array_intersect(['sidus-admin-actions', 'sidus-admin-action'], self::classes($crawler))), 'the classes of the bundle are the hooks to style the page');
    }

    public function testTheFormIsSemanticHtmlWithBlocksToOverride(): void
    {
        $client = $this->client('editor');
        $crawler = $client->request('GET', '/posts/3/edit');

        $section = $crawler->filter('main > section.sidus-admin-action');
        self::assertSame('post', $section->attr('data-admin'));
        self::assertSame('edit', $section->attr('data-action'));
        self::assertSame('post - edit', strtolower(trim($section->filter('h1')->text())), 'no translation: the humanized codes');
        self::assertSame('post - edit', strtolower(trim($crawler->filter('title')->text())), 'the title of the page is the one of the heading');
        self::assertSame('en', $crawler->filter('html')->attr('lang'));
        self::assertCount(1, $section->filter('form[method=post]'));
        self::assertSame('Back', trim($section->filter('p.sidus-admin-actions a')->text()));
        self::assertSame('Submit', trim($section->filter('p.sidus-admin-actions button[type=submit]')->text()));
    }

    public function testTheFlashMessagesAreRenderedByTheLayout(): void
    {
        $client = $this->client('editor');
        $crawler = $client->request('GET', '/posts/3/edit');
        $client->submit($crawler->selectButton('Submit')->form());
        $crawler = $client->followRedirect();

        $flash = $crawler->filter('p.sidus-admin-flash');
        self::assertSame('success', $flash->attr('data-type'));
        self::assertSame('The entity was saved', trim($flash->text()));
        self::assertSame('status', $flash->attr('role'));
    }

    public function testTheTemplatesAreTranslatedInTheLocaleOfTheApplication(): void
    {
        $client = $this->client('admin');
        $crawler = $client->request('GET', '/posts/3/delete');
        self::assertStringContainsString("Are you sure you want to delete the entity 'Charlie'?", $crawler->filter('.sidus-admin-message')->text());

        $this->rebootKernel(null, [], static fn (ContainerConfigurator $container) => $container->extension('framework', ['default_locale' => 'fr']));
        $crawler = $this->client('admin')->request('GET', '/posts/3/delete');

        self::assertStringContainsString("Êtes-vous sûr de vouloir supprimer l'entité 'Charlie' ?", $crawler->filter('.sidus-admin-message')->text());
        self::assertSame('Retour', trim($crawler->filter('.sidus-admin-actions a')->text()));
        self::assertSame('Supprimer', trim($crawler->filter('.sidus-admin-actions button')->text()));
        self::assertSame('fr', $crawler->filter('html')->attr('lang'));
    }

    public function testTheBaseTemplateIsTheOneOfTheConfiguration(): void
    {
        $this->rebootKernel(null, ['base_template' => 'custom_base.html.twig']);

        $client = $this->client('viewer');
        $crawler = $client->request('GET', '/posts/2');

        self::assertSame('/custom.css', $crawler->filter('head link[rel=stylesheet]')->attr('href'));
        self::assertCount(1, $crawler->filter('main > section.sidus-admin-action'), 'the blocks of the layout are the same');
    }

    public function testTheFirstTemplateOfThePatternThatExistsIsUsed(): void
    {
        $admins = TestKernel::admins();
        $admins['post']['template_pattern'] = ['Action/{{action}}.{{format}}.twig', '@SidusAdmin/Action/{{action}}.{{format}}.twig'];
        $this->rebootKernel($admins);
        $client = $this->client('viewer');

        $crawler = $client->request('GET', '/posts/2');
        self::assertSame('Custom read: Bravo', $crawler->filter('h1#custom-heading')->text(), 'the application has a read template');

        $crawler = $client->request('GET', '/posts/');
        self::assertSame(200, $client->getResponse()->getStatusCode(), 'it has no list template: the neutral one');
        self::assertCount(1, $crawler->filter('.sidus-datagrid'));
    }

    public function testAnActionWithoutAnyTemplateIsReported(): void
    {
        $admins = TestKernel::admins();
        $admins['post']['template_pattern'] = ['nothing/{{action}}.{{format}}.twig', 'nowhere/{{Admin}}.twig'];
        $this->rebootKernel($admins);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unable to resolve any valid template for the template_pattern configuration: nothing/{{action}}.{{format}}.twig, nowhere/{{Admin}}.twig');

        $admin = $this->service(\Sidus\AdminBundle\Registry\AdminRegistry::class)->get('post');
        $this->service(TemplateResolverInterface::class)->getTemplate($admin->getAction('read'));
    }

    /**
     * @return list<string>
     */
    private static function classes(Crawler $crawler): array
    {
        $classes = [];
        foreach ($crawler->filter('[class]') as $element) {
            foreach (preg_split('/\s+/', trim($element->getAttribute('class'))) ?: [] as $class) {
                $classes[$class] = true;
            }
        }

        return array_keys($classes);
    }
}
