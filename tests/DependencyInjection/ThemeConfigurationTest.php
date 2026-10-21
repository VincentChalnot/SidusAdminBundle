<?php

declare(strict_types=1);

namespace Sidus\AdminBundle\Tests\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Sidus\AdminBundle\Model\Admin;
use Sidus\AdminBundle\Registry\AdminRegistry;
use Sidus\AdminBundle\Tests\App\TestKernel;
use Sidus\AdminBundle\Tests\Fixtures\Entity\Post;

final class ThemeConfigurationTest extends TestCase
{
    private const string NEUTRAL = '@SidusAdmin/Action/{{action}}.{{format}}.twig';
    private const string BULMA = '@SidusAdmin/theme/bulma_1/Action/{{action}}.{{format}}.twig';

    /**
     * @param array<string, mixed> $admin
     * @param array<string, mixed> $config
     */
    private static function admin(array $admin = [], array $config = []): Admin
    {
        $admins = ['post' => $admin + [
            'entity' => Post::class,
            'prefix' => '/posts',
            'controller_pattern' => ['Sidus\AdminBundle\Action\{{Action}}Action'],
            'actions' => ['list' => ['path' => '/']],
        ]];
        $kernel = TestKernel::create($admins, $config);
        $kernel->boot();

        return $kernel->getContainer()->get('test.service_container')->get(AdminRegistry::class)->get('post');
    }

    public function testNoThemeIsNeutral(): void
    {
        $admin = self::admin(config: ['theme' => null]);

        self::assertSame('@SidusAdmin/base.html.twig', $admin->getBaseTemplate());
        self::assertSame([self::NEUTRAL], $admin->getTemplatePattern());
        self::assertSame('@SidusAdmin/base.html.twig', $admin->getAction('list')->getBaseTemplate());
    }

    public function testBulmaTheme(): void
    {
        $admin = self::admin(config: ['theme' => 'bulma_1']);

        self::assertSame('@SidusAdmin/theme/bulma_1/base.html.twig', $admin->getBaseTemplate());
        self::assertSame([self::BULMA, self::NEUTRAL], $admin->getTemplatePattern());
        self::assertSame('@SidusAdmin/theme/bulma_1/base.html.twig', $admin->getAction('list')->getBaseTemplate());
    }

    public function testGlobalBaseTemplateWinsOverTheTheme(): void
    {
        $admin = self::admin(config: ['theme' => 'bulma_1', 'base_template' => 'layout.html.twig']);

        self::assertSame('layout.html.twig', $admin->getBaseTemplate());
        self::assertSame('layout.html.twig', $admin->getAction('list')->getBaseTemplate());
    }

    public function testAdminBaseTemplateWinsOverTheGlobalOne(): void
    {
        $admin = self::admin(['base_template' => 'admin.html.twig'], ['theme' => 'bulma_1', 'base_template' => 'layout.html.twig']);

        self::assertSame('admin.html.twig', $admin->getBaseTemplate());
        self::assertSame('admin.html.twig', $admin->getAction('list')->getBaseTemplate());
    }

    public function testActionBaseTemplateWinsOverTheAdminOne(): void
    {
        $admin = self::admin([
            'base_template' => 'admin.html.twig',
            'actions' => [
                'list' => ['path' => '/', 'base_template' => 'action.html.twig'],
                'read' => ['path' => '/{id}'],
            ],
        ], ['theme' => 'bulma_1']);

        self::assertSame('action.html.twig', $admin->getAction('list')->getBaseTemplate());
        self::assertSame('admin.html.twig', $admin->getAction('read')->getBaseTemplate());
    }

    public function testBundleTemplatePatternsGetTheThemeVariantAndApplicationOnesAreUntouched(): void
    {
        $admin = self::admin(
            ['template_pattern' => ['Action/{{action}}.{{format}}.twig', '@SidusAdmin/Action/{{action}}.{{format}}.twig', '@App/{{admin}}.twig']],
            ['theme' => 'bulma_1'],
        );

        self::assertSame(
            ['Action/{{action}}.{{format}}.twig', self::BULMA, self::NEUTRAL, '@App/{{admin}}.twig'],
            $admin->getTemplatePattern(),
        );
    }

    public function testExplicitPatternIsUntouchedWithoutTheme(): void
    {
        $admin = self::admin(['template_pattern' => ['@SidusAdmin/Action/{{action}}.{{format}}.twig', 'x.twig']]);

        self::assertSame([self::NEUTRAL, 'x.twig'], $admin->getTemplatePattern());
    }
}
