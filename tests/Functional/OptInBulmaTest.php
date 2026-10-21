<?php

declare(strict_types=1);

namespace Sidus\AdminBundle\Tests\Functional;

use Sidus\AdminBundle\Tests\Support\AdminTestCase;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Bulma 1 is strictly opt-in, in two independent parts: `sidus_admin.theme: bulma_1` (layout and action templates) and
 * the Symfony form layout the application adds to its own twig.form_themes.
 */
final class OptInBulmaTest extends AdminTestCase
{
    private const string FORM_LAYOUT = '@SidusAdmin/form/bulma_1_layout.html.twig';

    private function boot(bool $theme, bool $formLayout): void
    {
        $this->rebootKernel(
            null,
            $theme ? ['theme' => 'bulma_1'] : [],
            $formLayout ? static fn (ContainerConfigurator $container) => $container->extension('twig', ['form_themes' => [self::FORM_LAYOUT]]) : null,
        );
    }

    public function testTheThemeAndTheFormLayoutStyleTheWholePage(): void
    {
        $this->boot(true, true);
        $client = $this->client('editor');
        $crawler = $client->request('GET', '/posts/3/edit');
        self::assertSame(200, $client->getResponse()->getStatusCode());

        // The theme: layout and action
        self::assertCount(1, $crawler->filter('main.section > .container .columns > .column.is-6[data-admin=post][data-action=edit]'));
        self::assertSame('title is-4', $crawler->filter('h1')->attr('class'));
        self::assertSame('Post - Edit', trim($crawler->filter('h1')->text()));
        self::assertCount(1, $crawler->filter('.field.is-grouped .control a.button.is-light'), 'back button');
        self::assertCount(1, $crawler->filter('.field.is-grouped .control button.button.is-primary[type=submit]'));

        // The form layout
        self::assertCount(3, $crawler->filter('form .field:not(.is-grouped)'), 'a row per field: title, body, status');
        self::assertCount(1, $crawler->filter('.field .control input.input[name="form_post_edit[title]"]'));
        self::assertCount(1, $crawler->filter('.field .control textarea.textarea[name="form_post_edit[body]"]'));
        self::assertCount(1, $crawler->filter('.field .control .select select[name="form_post_edit[status]"]'));
        self::assertCount(3, $crawler->filter('label.label'));
    }

    public function testTheDeleteActionIsADangerInTheTheme(): void
    {
        $this->boot(true, true);
        $crawler = $this->client('admin')->request('GET', '/posts/3/delete');

        self::assertCount(1, $crawler->filter('.notification.is-danger[role=alert]'));
        self::assertCount(1, $crawler->filter('button.button.is-danger[data-severity=danger]'));
    }

    public function testTheFlashMessagesAreNotificationsInTheTheme(): void
    {
        $this->boot(true, true);
        $client = $this->client('editor');
        $crawler = $client->request('GET', '/posts/3/edit');
        $client->submit($crawler->selectButton('Submit')->form());
        $crawler = $client->followRedirect();

        self::assertSame('The entity was saved', trim($crawler->filter('.notification.is-success[data-type=success]')->text()));
    }

    public function testTheErrorsOfAFieldAreShownTheBulmaWay(): void
    {
        $this->boot(true, true);
        $client = $this->client('editor');
        $crawler = $client->request('GET', '/posts/3/edit');
        $values = $crawler->selectButton('Submit')->form()->getPhpValues();
        $values['form_post_edit']['status'] = 'nonsense';
        $crawler = $client->request('POST', '/posts/3/edit', $values);

        self::assertSame(200, $client->getResponse()->getStatusCode());
        self::assertSame('The selected choice is invalid.', trim($crawler->filter('.field p.help.is-danger')->text()));
        self::assertCount(1, $crawler->filter('.field .select.is-danger select[name="form_post_edit[status]"]'));
        self::assertSame(['Alpha', 'Bravo', 'Charlie', 'Delta', 'Echo'], $this->titles());
    }

    public function testTheThemeAloneDoesNotRegisterTheFormLayout(): void
    {
        $this->boot(true, false);
        $crawler = $this->client('editor')->request('GET', '/posts/3/edit');

        self::assertCount(1, $crawler->filter('.columns .column'), 'the theme is there');
        self::assertCount(0, $crawler->filter('input.input, textarea.textarea, label.label, .select'), 'the form layout is not');
        self::assertCount(0, $crawler->filter('form .field:not(.is-grouped)'));
    }

    public function testTheFormLayoutAloneDoesNotChangeTheActionTemplates(): void
    {
        $this->boot(false, true);
        $crawler = $this->client('editor')->request('GET', '/posts/3/edit');

        self::assertCount(1, $crawler->filter('main > section.sidus-admin-action'), 'neutral action template');
        self::assertSame([], self::classesOutsideTheForm($crawler));
        self::assertCount(0, $crawler->filter('.sidus-admin-actions [class], .sidus-admin-actions .field, .sidus-admin-actions .button'), 'the buttons of the action stay neutral');
        self::assertCount(1, $crawler->filter('input.input[name="form_post_edit[title]"]'), 'the form layout is there');
    }

    public function testWithNeitherOfThemNothingIsStyled(): void
    {
        $this->boot(false, false);
        $crawler = $this->client('editor')->request('GET', '/posts/3/edit');

        self::assertCount(0, $crawler->filter('input.input, textarea.textarea, label.label, .select, .field, .control, .columns, .section'));
    }

    public function testAnActionOfTheThemeFallsBackToTheNeutralTemplateWhenTheThemeHasNone(): void
    {
        $this->boot(true, false);
        $client = $this->client('viewer');
        $crawler = $client->request('GET', '/posts/');

        // The list has no template in the theme: the neutral one, inside the layout of the theme
        self::assertSame(200, $client->getResponse()->getStatusCode());
        self::assertCount(1, $crawler->filter('main.section .container .sidus-datagrid'));
    }

    /**
     * @return list<string> the classes used outside the form of the page, they would be the framework's
     */
    private static function classesOutsideTheForm(Crawler $crawler): array
    {
        $classes = [];
        foreach ($crawler->filterXPath('//body//*[@class][not(ancestor::form) and not(self::form)]') as $element) {
            foreach (preg_split('/\s+/', trim($element->getAttribute('class'))) ?: [] as $class) {
                if (!str_starts_with($class, 'sidus-admin-')) {
                    $classes[$class] = $class;
                }
            }
        }

        return array_values($classes);
    }
}
