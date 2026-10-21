<?php

declare(strict_types=1);

namespace Sidus\AdminBundle\Tests\App;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Psr\Log\NullLogger;
use Sidus\AdminBundle\SidusAdminBundle;
use Sidus\AdminBundle\Tests\Fixtures\Action\PublishAction;
use Sidus\AdminBundle\Tests\Fixtures\Controller\ProbeController;
use Sidus\AdminBundle\Tests\Fixtures\Entity\Post;
use Sidus\AdminBundle\Tests\Fixtures\Entity\Tag;
use Sidus\AdminBundle\Tests\Fixtures\Form\PostType;
use Sidus\AdminBundle\Tests\Fixtures\Restricted\ListAction as RestrictedListAction;
use Sidus\AdminBundle\Tests\Fixtures\Form\TagType;
use Sidus\AdminBundle\Tests\Fixtures\Security\PostVoter;
use Sidus\DataGridBundle\SidusDataGridBundle;
use Sidus\DoctrineFilterBundle\SidusDoctrineFilterBundle;
use Sidus\FilterBundle\SidusFilterBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

/**
 * Boots the Admin bundle on top of the DataGrid and Filter bundles, with Doctrine on a SQLite file, a security
 * firewall with HTTP basic authentication (stateless: a user is a pair of server variables) and the admins given
 * by the test, in an isolated cache directory wiped before the first boot (every boot compiles the container).
 *
 * Users (password is "pw"): admin (ROLE_ADMIN), editor (ROLE_EDITOR), viewer (ROLE_VIEWER), nobody (no role).
 */
final class TestKernel extends Kernel
{
    use MicroKernelTrait;

    private const array POSTS_LIST = [
        'list' => [
            'provider' => 'doctrine',
            'options' => ['entity' => Post::class],
            'pagination' => ['per_page' => 2, 'max_per_page' => 10],
            'filters' => [
                'title' => ['type' => 'text'],
                'status' => ['type' => 'choice', 'options' => ['choices' => [Post::DRAFT, Post::PUBLISHED, Post::LOCKED]]],
            ],
        ],
        'columns' => ['id' => ['sortable' => true], 'title' => ['sortable' => true], 'status' => []],
        'default_sort' => [['column' => 'id', 'direction' => 'asc']],
    ];

    /**
     * @param \Closure(ContainerConfigurator): void|null $configure
     */
    public function __construct(
        private readonly ?\Closure $configure = null,
        private readonly string $id = 'default',
    ) {
        parent::__construct('test', true);
        // Every kernel has its own directory (cache, logs, database): remove it when the run is over
        $directory = $this->directory();
        register_shutdown_function(static fn () => new Filesystem()->remove($directory));
    }

    /**
     * The "post" admin: a list (read by everybody who is logged in), create and edit for editors, clone for editors,
     * delete for admins, a custom `publish` action; an entity-level rule (POST_UNLOCKED) protects edit, clone and delete.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function admins(): array
    {
        $unlocked = ['ROLE_EDITOR', PostVoter::UNLOCKED];

        return [
            'post' => [
                'entity' => Post::class,
                'prefix' => '/posts',
                'form_type' => PostType::class,
                'controller_pattern' => [
                    'Sidus\AdminBundle\Tests\Fixtures\Action\{{Action}}Action',
                    'Sidus\AdminBundle\Action\{{Action}}Action',
                ],
                'permissions' => ['ROLE_VIEWER'],
                'options' => ['datagrid' => 'posts'],
                'actions' => [
                    'list' => ['path' => '/'],
                    'create' => ['path' => '/create', 'permissions' => ['ROLE_EDITOR']],
                    'read' => ['path' => '/{id}', 'requirements' => ['id' => '\d+']],
                    'edit' => ['path' => '/{id}/edit', 'requirements' => ['id' => '\d+'], 'permissions' => $unlocked],
                    'clone' => ['path' => '/{id}/clone', 'requirements' => ['id' => '\d+'], 'permissions' => ['ROLE_EDITOR']],
                    'delete' => ['path' => '/{id}/delete', 'requirements' => ['id' => '\d+'], 'permissions' => ['ROLE_ADMIN', PostVoter::UNLOCKED], 'options' => ['redirect_action' => 'list']],
                    'publish' => ['path' => '/{id}/publish', 'requirements' => ['id' => '\d+'], 'methods' => ['POST'], 'permissions' => ['ROLE_EDITOR']],
                ],
            ],
            'tag' => [
                'entity' => Tag::class,
                'prefix' => '/tags',
                'form_type' => TagType::class,
                'controller_pattern' => ['Sidus\AdminBundle\Action\{{Action}}Action'],
                'actions' => [
                    'list' => ['path' => '/'],
                    'edit' => ['path' => '/{slug}/edit'],
                ],
            ],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function datagrids(): array
    {
        return [
            'tag' => [
                'list' => [
                    'provider' => 'doctrine',
                    'options' => ['entity' => Tag::class],
                    'filters' => ['label' => ['type' => 'text']],
                ],
                'columns' => ['slug' => ['sortable' => true], 'label' => []],
                'default_sort' => [['column' => 'slug', 'direction' => 'asc']],
            ],
            'posts' => self::POSTS_LIST + [
                'actions' => [
                    'create' => ['options' => ['admin_action' => 'create']],
                    'tags' => ['options' => ['admin_action' => 'list', 'admin' => 'tag']],
                    'delete_all' => ['label' => 'Delete everything', 'options' => ['admin_action' => 'delete', 'on_denied' => 'disable', 'admin' => 'post'], 'route_parameters' => ['id' => 1]],
                ],
            ],
            // The same list without actions: for the admins that do not have a create action
            'readonly_posts' => self::POSTS_LIST,
        ];
    }

    /**
     * @param array<string, array<string, mixed>>|null   $admins     sidus_admin.configurations, the default admins when null
     * @param array<string, mixed>                       $config     more `sidus_admin` configuration (theme, base_template...)
     * @param \Closure(ContainerConfigurator): void|null $services
     * @param \Closure(RoutingConfigurator): void|null   $routes     replaces the routes (the admin routes and the probe)
     */
    public static function create(?array $admins = null, array $config = [], ?\Closure $services = null, ?string $id = null): self
    {
        return new self(static function (ContainerConfigurator $container) use ($admins, $config, $services): void {
            $container->extension('sidus_admin', ['configurations' => $admins ?? self::admins()] + $config);
            if (null !== $services) {
                $services($container);
            }
        }, $id ?? uniqid('k', true));
    }

    public function registerBundles(): iterable
    {
        yield new FrameworkBundle();
        yield new TwigBundle();
        yield new SecurityBundle();
        yield new DoctrineBundle();
        yield new SidusFilterBundle();
        yield new SidusDoctrineFilterBundle();
        yield new SidusDataGridBundle();
        yield new SidusAdminBundle();
    }

    public function boot(): void
    {
        if (!$this->booted) {
            new Filesystem()->remove($this->getCacheDir());
        }
        parent::boot();
    }

    public function getProjectDir(): string
    {
        return __DIR__;
    }

    public function getCacheDir(): string
    {
        return $this->directory().'/cache';
    }

    public function getLogDir(): string
    {
        return $this->directory().'/log';
    }

    public function getDatabasePath(): string
    {
        return $this->directory().'/db.sqlite';
    }

    private function directory(): string
    {
        return sys_get_temp_dir().'/sidus-admin-bundle-tests/'.$this->id;
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $routes->import('.', 'sidus_admin');
        $routes->add('probe', '/probe')->controller(ProbeController::class);
    }

    protected function configureContainer(ContainerConfigurator $container): void
    {
        $container->extension('framework', [
            'test' => true,
            'secret' => 'test',
            'http_method_override' => false,
            'default_locale' => 'en',
            'router' => ['utf8' => true],
            'csrf_protection' => ['enabled' => true],
            'form' => ['enabled' => true],
            'session' => ['storage_factory_id' => 'session.storage.factory.mock_file'],
            'validation' => ['enabled' => false],
        ]);
        $container->extension('twig', ['strict_variables' => true, 'debug' => true]);
        $container->extension('doctrine', [
            'dbal' => ['connections' => ['default' => ['driver' => 'pdo_sqlite', 'path' => $this->getDatabasePath()]]],
            'orm' => ['mappings' => ['Fixtures' => [
                'type' => 'attribute',
                'is_bundle' => false,
                'dir' => \dirname(__DIR__).'/Fixtures/Entity',
                'prefix' => 'Sidus\AdminBundle\Tests\Fixtures\Entity',
            ]]],
        ]);
        $container->extension('sidus_data_grid', ['configurations' => self::datagrids()]);
        $container->extension('security', [
            'password_hashers' => ['Symfony\Component\Security\Core\User\InMemoryUser' => 'plaintext'],
            'role_hierarchy' => ['ROLE_ADMIN' => ['ROLE_EDITOR'], 'ROLE_EDITOR' => ['ROLE_VIEWER']],
            'providers' => ['users' => ['memory' => ['users' => [
                'admin' => ['password' => 'pw', 'roles' => ['ROLE_ADMIN']],
                'editor' => ['password' => 'pw', 'roles' => ['ROLE_EDITOR']],
                'viewer' => ['password' => 'pw', 'roles' => ['ROLE_VIEWER']],
                'nobody' => ['password' => 'pw', 'roles' => []],
            ]]]],
            'firewalls' => ['main' => ['stateless' => true, 'provider' => 'users', 'http_basic' => ['realm' => 'test']]],
        ]);

        $services = $container->services()->defaults()->autowire()->autoconfigure();
        $services->set('logger', NullLogger::class);
        $services->set(PostVoter::class);
        $services->set(PublishAction::class);
        $services->set(RestrictedListAction::class);
        $services->set(ProbeController::class);

        if (null !== $this->configure) {
            ($this->configure)($container);
        }
    }
}
