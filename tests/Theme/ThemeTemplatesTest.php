<?php

declare(strict_types=1);

namespace Sidus\AdminBundle\Tests\Theme;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Sidus\AdminBundle\Tests\Support\TwigFormRenderer;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Forms;
use Twig\Environment;
use Twig\TwigFilter;
use Twig\TwigFunction;

final class ThemeTemplatesTest extends TestCase
{
    /** @return iterable<string, array{string}> */
    public static function templates(): iterable
    {
        foreach (['base', 'Action/edit', 'Action/create', 'Action/clone', 'Action/read', 'Action/delete'] as $name) {
            yield $name => ['@SidusAdmin/theme/bulma_1/'.$name.'.html.twig'];
        }
        yield 'form layout' => [TwigFormRenderer::BULMA_1_LAYOUT];
    }

    #[DataProvider('templates')]
    public function testTemplateCompiles(string $name): void
    {
        self::assertSame($name, $this->twig()->load($name)->getTemplateName());
    }

    public function testEditRendersBulmaMarkup(): void
    {
        $html = $this->render('edit', flashes: ['success' => ['Saved'], 'error' => ['Oops'], 'warning' => ['Careful'], 'notice' => ['FYI']]);
        $c = new Crawler($html);

        self::assertCount(1, $c->filter('main.section > div.container > div.columns > div.column.is-6[data-admin="user"][data-action="edit"]'));
        self::assertSame('User - Edit', trim($c->filter('h1.title.is-4')->text()));
        self::assertCount(1, $c->filter('.notification.is-success'));
        self::assertCount(1, $c->filter('.notification.is-danger'));
        self::assertCount(1, $c->filter('.notification.is-warning'));
        self::assertCount(1, $c->filter('.notification.is-info[data-type="notice"]'));
        self::assertCount(1, $c->filter('form .notification.is-info'), 'the message of the action');
        self::assertCount(1, $c->filter('form div.field.is-grouped > div.control > a.button.is-light[href="/users"]'));
        self::assertCount(1, $c->filter('form div.field.is-grouped > div.control > button.button.is-primary[type="submit"]'));
        self::assertCount(0, $c->filter('link[rel="stylesheet"]'));
        self::assertDoesNotMatchRegularExpression('/\b(btn|form-group|col-[a-z0-9-]+)\b/', $html);
    }

    public function testCreateAndCloneExtendTheThemeEdit(): void
    {
        foreach (['create', 'clone'] as $action) {
            $c = new Crawler($this->render($action));
            self::assertCount(1, $c->filter(\sprintf('div.column.is-6[data-action="%s"] button.button.is-primary', $action)));
        }
    }

    public function testReadHasNoSubmitButton(): void
    {
        $c = new Crawler($this->render('read'));

        self::assertCount(0, $c->filter('button[type="submit"]'));
        self::assertCount(1, $c->filter('a.button.is-light'));
    }

    public function testDeleteIsDanger(): void
    {
        $c = new Crawler($this->render('delete'));

        self::assertCount(1, $c->filter('button.button.is-danger[type="submit"][data-severity="danger"]'));
        self::assertCount(1, $c->filter('div.notification.is-danger[role="alert"][data-severity="danger"]'));
    }

    public function testCssClassVariables(): void
    {
        $c = new Crawler($this->render('edit', ['form_container_class' => 'column is-12', 'submit_class' => 'button is-link', 'back_class' => 'button is-text', 'message_class' => 'is-warning']));

        self::assertCount(1, $c->filter('div.column.is-12'));
        self::assertCount(0, $c->filter('div.column.is-6'));
        self::assertCount(1, $c->filter('button.button.is-link'));
        self::assertCount(1, $c->filter('a.button.is-text'));
        self::assertCount(1, $c->filter('div.notification.is-warning'));
    }

    /**
     * @param array<string, mixed>               $extra
     * @param array<string, list<string>>        $flashes
     */
    private function render(string $action, array $extra = [], array $flashes = []): string
    {
        $form = Forms::createFormFactoryBuilder()->getFormFactory()->createBuilder(FormType::class, null, [])
            ->add('name', TextType::class)
            ->getForm()
        ;

        return $this->twig()->load('@SidusAdmin/theme/bulma_1/Action/'.$action.'.html.twig')->render($extra + [
            'app' => new class($flashes) {
                public ?object $request = null;

                /** @param array<string, list<string>> $flashes */
                public function __construct(public array $flashes)
                {
                }
            },
            'action' => ['code' => $action, 'baseTemplate' => '@SidusAdmin/theme/bulma_1/base.html.twig'],
            'admin' => ['code' => 'user'],
            'form' => $form->createView(),
            'data' => 'John',
            'listPath' => '/users',
        ]);
    }

    private function twig(): Environment
    {
        $twig = TwigFormRenderer::environment();
        $twig->addFunction(new TwigFunction('tryTrans', static fn (array $keys, array $params = [], ?string $fallback = null): ?string => 'message' === substr((string) end($keys), -7) ? ($fallback ?? 'A message') : $fallback));
        $twig->addFunction(new TwigFunction('render_datagrid', static fn (mixed $datagrid): string => ''));
        $twig->addFilter(new TwigFilter('tostring', static fn (mixed $value): string => (string) $value));

        return $twig;
    }
}
