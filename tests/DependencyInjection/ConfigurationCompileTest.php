<?php

declare(strict_types=1);

namespace Sidus\AdminBundle\Tests\DependencyInjection;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Sidus\AdminBundle\Tests\App\TestKernel;
use Sidus\AdminBundle\Tests\Fixtures\Controller\ProbeController;
use Sidus\AdminBundle\Tests\Fixtures\Entity\Post;
use Sidus\AdminBundle\Tests\Fixtures\Form\PostType;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Routing\RouterInterface;

final class ConfigurationCompileTest extends TestCase
{
    private const string BUNDLE_ACTIONS = 'Sidus\AdminBundle\Action\{{Action}}Action';

    /**
     * @param array<string, mixed> $admin    overrides of the valid "post" admin, a null value removes the key
     * @param array<string, mixed> $config   more sidus_admin configuration
     *
     * @return array<string, array<string, mixed>>
     */
    private static function post(array $admin = [], array $config = []): array
    {
        $valid = [
            'entity' => Post::class,
            'prefix' => '/posts',
            'controller_pattern' => [self::BUNDLE_ACTIONS],
            'actions' => ['list' => ['path' => '/']],
        ];

        $result = array_filter($admin + $valid, static fn (mixed $v): bool => null !== $v);

        return ['post' => $result];
    }

    /**
     * @return iterable<string, array{array<string, array<string, mixed>>, class-string<\Throwable>, string}>
     */
    public static function invalidConfigurations(): iterable
    {
        $e = InvalidConfigurationException::class;

        yield 'missing prefix' => [self::post(['prefix' => null]), $e, 'The child config "prefix" under "sidus_admin.configurations.post" must be configured'];
        yield 'missing entity' => [self::post(['entity' => null]), $e, 'The child config "entity" under "sidus_admin.configurations.post" must be configured'];
        yield 'entity is not a class' => [self::post(['entity' => 'App\\Nope\\Missing']), $e, "the entity 'App\\Nope\\Missing' is neither an existing class nor an interface"];
        yield 'unknown admin form type' => [self::post(['form_type' => 'App\\Nope\\Type']), $e, "sidus_admin.configurations.post\": the form_type 'App\\Nope\\Type' is not an existing class"];
        yield 'unknown action form type' => [
            self::post(['actions' => ['list' => ['path' => '/', 'form_type' => 'App\\Nope\\Type']]]),
            $e,
            "sidus_admin.configurations.post.actions.list\": the form_type 'App\\Nope\\Type' is not an existing class",
        ];
        yield 'unknown controller' => [
            self::post(['controller_pattern' => ['App\\Nope\\{{Admin}}\\{{Action}}Controller', 'App\\Other\\{{action}}'], 'actions' => ['list' => ['path' => '/']]]),
            $e,
            'sidus_admin.configurations.post.actions.list": no controller found for the controller_pattern [App\Nope\{{Admin}}\{{Action}}Controller, App\Other\{{action}}], tried: App\Nope\Post\ListController, App\Other\list',
        ];
        yield 'neither pattern nor controller' => [
            self::post(['controller_pattern' => null]),
            $e,
            "you must configure either the 'defaults._controller' option of the action or the 'controller_pattern' option of the admin",
        ];
        yield 'unknown variable in controller_pattern' => [
            self::post(['controller_pattern' => ['App\\{{foo}}Action']]),
            $e,
            'unknown variable {{foo}} in the controller_pattern',
        ];
        yield 'unknown variable in template_pattern' => [
            self::post(['template_pattern' => ['{{foo}}.html.twig']]),
            $e,
            'unknown variable {{foo}} in the template_pattern',
        ];
        yield 'unknown variable in action controller pattern' => [
            self::post(['actions' => ['list' => ['path' => '/', 'defaults' => ['_controller_pattern' => ['{{bar}}']]]]]),
            $e,
            'unknown variable {{bar}} in the defaults._controller_pattern',
        ];
        yield 'invalid admin code' => [['my-admin' => self::post()['post']], $e, 'the admin code must start with a letter'];
        yield 'invalid action code' => [self::post(['actions' => ['1list' => ['path' => '/']]]), $e, 'the action code must start with a letter'];
        yield 'path without slash' => [self::post(['actions' => ['list' => ['path' => 'list']]]), $e, "the path 'list' must start with a slash"];
        yield 'invalid route' => [self::post(['actions' => ['list' => ['path' => '/{1}']]]), $e, 'sidus_admin.configurations.post.actions.list": invalid route:'];
        yield 'reserved _admin default' => [
            self::post(['actions' => ['list' => ['path' => '/', 'defaults' => ['_admin' => 'x']]]]),
            $e,
            "the route default '_admin' is reserved",
        ];
        yield 'reserved _action default' => [
            self::post(['actions' => ['list' => ['path' => '/', 'defaults' => ['_action' => 'x']]]]),
            $e,
            "the route default '_action' is reserved",
        ];
        yield 'admin options not a map' => [self::post(['options' => 'nope']), $e, 'The options must be a map.'];
        yield 'action options not a map' => [self::post(['actions' => ['list' => ['path' => '/', 'options' => 'nope']]]), $e, 'The options must be a map.'];
        yield 'admin codes differing by case' => [
            ['post' => self::post()['post'], 'Post' => self::post(['prefix' => '/other'])['post']],
            $e,
            "The admins 'post' and 'Post' only differ by case",
        ];
        yield 'same route path in two admins' => [
            ['post' => self::post()['post'], 'page' => self::post()['post']],
            $e,
            "The action 'post.list' and the action 'page.list' have the same route path '/posts/'",
        ];
        yield 'same route path in two actions' => [
            self::post(['actions' => ['list' => ['path' => '/'], 'other' => ['path' => '/', 'defaults' => ['_controller' => ProbeController::class]]]]),
            $e,
            "The action 'post.list' and the action 'post.other' have the same route path",
        ];
        yield 'same route path, overlapping methods' => [
            self::post(['actions' => [
                'list' => ['path' => '/x', 'methods' => ['GET', 'POST']],
                'other' => ['path' => '/x', 'methods' => ['post'], 'defaults' => ['_controller' => ProbeController::class]],
            ]]),
            $e,
            "have the same route path '/posts/x'",
        ];
    }

    /**
     * @param array<string, array<string, mixed>> $admins
     * @param class-string<\Throwable>            $exception
     */
    #[DataProvider('invalidConfigurations')]
    public function testInvalidConfigurationFailsTheBoot(array $admins, string $exception, string $message): void
    {
        $this->expectException($exception);
        $this->expectExceptionMessage($message);

        TestKernel::create($admins)->boot();
    }

    public function testInvalidThemeIsRejected(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('The value "material" is not allowed for path "sidus_admin.theme"');

        TestKernel::create(self::post(), ['theme' => 'material'])->boot();
    }

    public function testDefaultAdminsBoot(): void
    {
        $kernel = TestKernel::create();
        $kernel->boot();

        $router = $kernel->getContainer()->get('router');
        self::assertInstanceOf(RouterInterface::class, $router);
        self::assertNotNull($router->getRouteCollection()->get('sidus_admin.post.publish'));
        self::assertNotNull($router->getRouteCollection()->get('sidus_admin.tag.edit'));
    }

    public function testExplicitControllerNeedsNoPattern(): void
    {
        $admins = self::post(['controller_pattern' => null, 'actions' => ['list' => ['path' => '/', 'defaults' => ['_controller' => ProbeController::class]]]]);

        self::assertSame(ProbeController::class, $this->controller($admins, 'sidus_admin.post.list'));
    }

    public function testSameRoutePathWithDisjointMethodsIsAccepted(): void
    {
        $admins = self::post(['actions' => [
            'list' => ['path' => '/x', 'methods' => ['GET']],
            'create' => ['path' => '/x', 'methods' => ['POST']],
        ]]);

        $kernel = TestKernel::create($admins);
        $kernel->boot();

        $routes = $kernel->getContainer()->get('router')->getRouteCollection();
        self::assertSame(['GET'], $routes->get('sidus_admin.post.list')?->getMethods());
        self::assertSame(['POST'], $routes->get('sidus_admin.post.create')?->getMethods());
    }

    public function testFirstExistingControllerOfThePatternListWins(): void
    {
        $admins = self::post(['controller_pattern' => ['App\\Nope\\{{Action}}Action', self::BUNDLE_ACTIONS]]);

        self::assertSame('Sidus\AdminBundle\Action\ListAction', $this->controller($admins, 'sidus_admin.post.list'));
    }

    public function testActionPatternOverridesTheAdminPattern(): void
    {
        $admins = self::post([
            'controller_pattern' => ['App\\Nope\\{{Action}}Action'],
            'actions' => [
                'list' => ['path' => '/', 'defaults' => ['_controller_pattern' => self::BUNDLE_ACTIONS]],
            ],
        ]);

        self::assertSame('Sidus\AdminBundle\Action\ListAction', $this->controller($admins, 'sidus_admin.post.list'));
    }

    public function testActionPatternDoesNotLeakToOtherActions(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('sidus_admin.configurations.post.actions.read": no controller found');

        TestKernel::create(self::post([
            'controller_pattern' => ['App\\Nope\\{{Action}}Action'],
            'actions' => [
                'list' => ['path' => '/', 'defaults' => ['_controller_pattern' => self::BUNDLE_ACTIONS]],
                'read' => ['path' => '/{id}'],
            ],
        ]))->boot();
    }

    public function testFormTypeIsInheritedFromTheAdmin(): void
    {
        $kernel = TestKernel::create(self::post(['form_type' => PostType::class]));
        $kernel->boot();

        $registry = $kernel->getContainer()->get('test.service_container')->get(\Sidus\AdminBundle\Registry\AdminRegistry::class);
        self::assertSame(PostType::class, $registry->get('post')->getAction('list')->getFormType());
    }

    /**
     * @param array<string, array<string, mixed>> $admins
     */
    private function controller(array $admins, string $route): mixed
    {
        $kernel = TestKernel::create($admins);
        $kernel->boot();

        $router = $kernel->getContainer()->get('router');
        self::assertInstanceOf(RouterInterface::class, $router);

        return $router->getRouteCollection()->get($route)?->getDefault('_controller');
    }
}
