<?php

declare(strict_types=1);

namespace Sidus\AdminBundle\Tests\Support;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Tools\SchemaTool;
use PHPUnit\Framework\TestCase;
use Sidus\AdminBundle\Tests\App\TestKernel;
use Sidus\AdminBundle\Tests\Fixtures\Entity\Post;
use Sidus\AdminBundle\Tests\Fixtures\Entity\Tag;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\User\InMemoryUser;

/**
 * One booted kernel per test class (like a worker), a fresh database with the same posts before each test.
 *
 * The posts, by id: 1 "Alpha" draft, 2 "Bravo" published, 3 "Charlie" draft, 4 "Delta" locked, 5 "Echo" published.
 */
abstract class AdminTestCase extends TestCase
{
    protected static ?TestKernel $kernel = null;

    /**
     * @param array<string, array<string, mixed>>|null $admins null: the default admins
     * @param array<string, mixed>                     $config more `sidus_admin` configuration
     * @param \Closure(ContainerConfigurator): void|null $services
     */
    protected static function bootKernel(?array $admins = null, array $config = [], ?\Closure $services = null): TestKernel
    {
        self::$kernel = TestKernel::create($admins, $config, $services);
        self::$kernel->boot();

        return self::$kernel;
    }

    public static function setUpBeforeClass(): void
    {
        static::bootKernel();
    }

    public static function tearDownAfterClass(): void
    {
        self::$kernel?->shutdown();
        self::$kernel = null;
    }

    protected function setUp(): void
    {
        $this->resetDatabase();
    }

    /**
     * Replaces the booted kernel by another one, with its own (fresh) database
     *
     * @param array<string, array<string, mixed>>|null $admins
     * @param array<string, mixed>                     $config
     * @param \Closure(ContainerConfigurator): void|null $services
     */
    protected function rebootKernel(?array $admins = null, array $config = [], ?\Closure $services = null): void
    {
        self::$kernel?->shutdown();
        static::bootKernel($admins, $config, $services);
        $this->resetDatabase();
    }

    private function resetDatabase(): void
    {
        // A new entity manager: the previous test may have left entities in the identity map (or closed it)
        $this->service(ManagerRegistry::class)->resetManager();
        $manager = $this->manager();
        $metadata = $manager->getMetadataFactory()->getAllMetadata();
        $tool = new SchemaTool($manager);
        $tool->dropSchema($metadata);
        $tool->createSchema($metadata);

        foreach (['Alpha' => Post::DRAFT, 'Bravo' => Post::PUBLISHED, 'Charlie' => Post::DRAFT, 'Delta' => Post::LOCKED, 'Echo' => Post::PUBLISHED] as $title => $status) {
            $post = new Post();
            $post->setTitle($title);
            $post->setStatus($status);
            $manager->persist($post);
        }
        $tag = new Tag();
        $tag->setSlug('php');
        $tag->setLabel('PHP');
        $manager->persist($tag);
        $manager->flush();
        $manager->clear();
    }

    protected static function kernel(): TestKernel
    {
        return self::$kernel ?? throw new \LogicException('The kernel is not booted.');
    }

    protected function manager(): EntityManagerInterface
    {
        $manager = $this->service(ManagerRegistry::class)->getManager();
        \assert($manager instanceof EntityManagerInterface);

        return $manager;
    }

    /**
     * A private service of the kernel
     *
     * @template T of object
     *
     * @param class-string<T> $id
     *
     * @return T
     */
    protected function service(string $id): object
    {
        return self::kernel()->getContainer()->get('test.service_container')->get($id);
    }

    /**
     * Logs a user of the kernel in the token storage, for what is called directly and not through a request
     */
    protected function authenticate(string $user): void
    {
        $roles = ['admin' => ['ROLE_ADMIN'], 'editor' => ['ROLE_EDITOR'], 'viewer' => ['ROLE_VIEWER'], 'nobody' => []][$user];
        $this->service(TokenStorageInterface::class)->setToken(new UsernamePasswordToken(new InMemoryUser($user, 'pw', $roles), 'main', $roles));
    }

    /**
     * A browser on the booted kernel that keeps it booted between requests (like a worker does).
     */
    protected function client(?string $user = null): KernelBrowser
    {
        $client = new KernelBrowser(self::kernel());
        $client->disableReboot();
        if (null !== $user) {
            $client->setServerParameters(['PHP_AUTH_USER' => $user, 'PHP_AUTH_PW' => 'pw']);
        }

        return $client;
    }

    /**
     * One request handled like the runtime of a long-running worker does: the same kernel, no browser, nothing kept
     * between two calls but what the kernel itself keeps.
     *
     * @param array<string, mixed> $parameters
     */
    protected function handle(string $uri, ?string $user = null, string $method = 'GET', array $parameters = []): Response
    {
        $request = Request::create($uri, $method, $parameters, [], [], null === $user ? [] : ['PHP_AUTH_USER' => $user, 'PHP_AUTH_PW' => 'pw']);
        $response = self::kernel()->handle($request);
        self::kernel()->terminate($request, $response);

        return $response;
    }

    /**
     * @return list<string> the titles of the posts, by id
     */
    protected function titles(): array
    {
        return array_column($this->manager()->getConnection()->fetchAllAssociative('SELECT title FROM post ORDER BY id'), 'title');
    }
}
