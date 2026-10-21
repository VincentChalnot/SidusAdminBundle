<?php

declare(strict_types=1);

namespace Sidus\AdminBundle\Tests\Functional;

use Sidus\AdminBundle\Registry\AdminRegistry;
use Sidus\AdminBundle\Routing\AdminRouteLoader;
use Sidus\AdminBundle\Routing\AdminRouter;
use Sidus\AdminBundle\Tests\Fixtures\Action\PublishAction;
use Sidus\AdminBundle\Tests\Fixtures\Entity\Post;
use Sidus\AdminBundle\Tests\Fixtures\Entity\Tag;
use Sidus\AdminBundle\Tests\Support\AdminTestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;

/**
 * The routes generated from the configuration, and the functions that generate paths for admins, actions and entities
 */
final class RoutingTest extends AdminTestCase
{
    public function testEveryActionIsARouteOfItsOwnWithTheDefaultsOfTheAdminAndTheAction(): void
    {
        $routes = $this->service(RouterInterface::class)->getRouteCollection();

        self::assertSame(
            [
                'sidus_admin.post.list', 'sidus_admin.post.create', 'sidus_admin.post.read', 'sidus_admin.post.edit', 'sidus_admin.post.clone',
                'sidus_admin.post.delete', 'sidus_admin.post.publish', 'sidus_admin.tag.list', 'sidus_admin.tag.edit', 'probe',
            ],
            array_keys($routes->all()),
        );

        $edit = $routes->get('sidus_admin.post.edit');
        self::assertSame('/posts/{id}/edit', $edit->getPath());
        self::assertSame(['id' => '\d+'], $edit->getRequirements());
        self::assertSame('post', $edit->getDefault('_admin'));
        self::assertSame('edit', $edit->getDefault('_action'));
        self::assertSame('Sidus\AdminBundle\Action\EditAction', $edit->getDefault('_controller'), 'the first controller of the pattern that exists');

        $publish = $routes->get('sidus_admin.post.publish');
        self::assertSame(PublishAction::class, $publish->getDefault('_controller'), 'the custom action wins over the built-in ones');
        self::assertSame(['POST'], $publish->getMethods());
        self::assertNull($routes->get('sidus_admin.post.publish')->getDefault('_controller_pattern'), 'the pattern is resolved at compile time and does not travel in the route');
    }

    public function testTheLoaderBuildsNewRoutesEachTimeItIsCalled(): void
    {
        $loader = $this->service(AdminRouteLoader::class);

        $first = $loader->load('.', 'sidus_admin');
        $second = $loader->load('.', 'sidus_admin');

        self::assertEquals($first->all(), $second->all());
        self::assertNotSame($first->get('sidus_admin.post.list'), $second->get('sidus_admin.post.list'));

        $first->addPrefix('/changed');
        $first->get('sidus_admin.post.list')->setDefault('_controller', 'x');
        self::assertSame('/posts/', $loader->load('.', 'sidus_admin')->get('sidus_admin.post.list')->getPath(), 'what the router does with a route never reaches the actions');
        self::assertSame('/posts/', $this->service(AdminRegistry::class)->get('post')->getAction('list')->createRoute()->getPath());
    }

    public function testPathsAreGeneratedForAdminsActionsAndEntities(): void
    {
        $router = $this->service(AdminRouter::class);
        $post = new Post();
        $post->setTitle('x');
        (new \ReflectionProperty($post, 'id'))->setValue($post, 12);
        $tag = new Tag();
        $tag->setSlug('php');

        self::assertSame('/posts/', $router->generateAdminPath('post', 'list'));
        self::assertSame('/posts/?page=2', $router->generateAdminPath('post', 'list', ['page' => 2]));
        self::assertSame('/posts/7', $router->generateAdminPath('post', 'read', ['id' => 7]));
        self::assertSame('/posts/12/edit', $router->generateEntityPath($post, 'edit'), 'the route parameters are read from the entity');
        self::assertSame('/posts/12/edit?from=list', $router->generateEntityPath($post, 'edit', ['from' => 'list']));
        self::assertSame('/posts/99/edit', $router->generateEntityPath($post, 'edit', ['id' => 99]), 'given parameters win');
        self::assertSame('/tags/php/edit', $router->generateAdminEntityPath('tag', $tag, 'edit'));
        self::assertSame('http://localhost/posts/', $router->generateAdminPath('post', 'list', [], UrlGeneratorInterface::ABSOLUTE_URL));
    }

    public function testTheAdminOfAnEntityIsTheFirstOneThatMatches(): void
    {
        $post = new Post();
        $post->setTitle('x');
        (new \ReflectionProperty($post, 'id'))->setValue($post, 3);

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage("No action with code 'nope' for admin 'post', available actions: list, create, read, edit, clone, delete, publish");

        $this->service(AdminRouter::class)->generateEntityPath($post, 'nope');
    }

    public function testTwigFunctionsGeneratePathsAndTellWhatTheUserMayDo(): void
    {
        $twig = $this->service(Environment::class);
        $render = static fn (string $template, array $context = []): string => $twig->createTemplate($template)->render($context);
        $post = new Post();
        (new \ReflectionProperty($post, 'id'))->setValue($post, 5);
        $tag = new Tag();
        $tag->setSlug('php');

        self::assertSame('/posts/', $render("{{ admin_path('post', 'list') }}"));
        self::assertSame('/posts/5/edit', $render("{{ entity_path(post, 'edit') }}", ['post' => $post]));
        self::assertSame('/tags/php/edit', $render("{{ admin_entity_path('tag', tag, 'edit') }}", ['tag' => $tag]));
        self::assertSame('tag', $render('{{ entity_admin(tag).code }}', ['tag' => $tag]));
        self::assertSame('post,tag', $render("{{ get_admins()|keys|join(',') }}"));
        self::assertSame('Post: id', $render("{{ admin('post').code|capitalize }}: {{ admin('post').action('read').routeVariables|join(',') }}"));
    }

    public function testTheLinkToAnActionIsOnlyRenderedForTheUsersGrantedTheAction(): void
    {
        $client = $this->client('editor');
        $crawler = $client->request('GET', '/posts/3/edit');
        $links = $crawler->filter('a')->each(static fn ($a): string => $a->attr('href') ?? '');

        self::assertContains('/posts/', $links);

        $twig = $this->service(Environment::class);
        $template = $twig->createTemplate("{{ action_link({action: 'delete', admin: 'post', entity: post}) }}|{{ action_link({action: 'edit', admin: 'post', entity: post, label: 'Change'}) }}");
        $post = $this->manager()->find(Post::class, 3);

        $this->authenticate('editor');
        self::assertSame('|<a title="Change" href="/posts/3/edit">Change</a>', $template->render(['post' => $post]), 'an editor may edit but not delete');

        $this->authenticate('admin');
        self::assertSame('<a title="Delete" href="/posts/3/delete">Delete</a>|<a title="Change" href="/posts/3/edit">Change</a>', $template->render(['post' => $post]));
    }
}
