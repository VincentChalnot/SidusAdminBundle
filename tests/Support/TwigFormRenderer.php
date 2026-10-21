<?php

declare(strict_types=1);

namespace Sidus\AdminBundle\Tests\Support;

use Symfony\Bridge\Twig\Extension\FormExtension;
use Symfony\Bridge\Twig\Extension\TranslationExtension;
use Symfony\Bridge\Twig\Form\TwigRendererEngine;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormRenderer;
use Symfony\Component\Translation\Translator;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\RuntimeLoader\FactoryRuntimeLoader;

/**
 * Renders a form with a standalone Twig environment (no kernel): the Symfony form layouts, `@SidusAdmin` pointing at
 * the templates of the bundle, strict variables.
 */
final class TwigFormRenderer
{
    public const BULMA_1_LAYOUT = '@SidusAdmin/form/bulma_1_layout.html.twig';

    public static function render(FormInterface $form, string $theme = self::BULMA_1_LAYOUT, string $locale = 'en'): string
    {
        return self::environment($theme, $locale)
            ->createTemplate('{{ form_start(form) }}{{ form_widget(form) }}{{ form_end(form) }}')
            ->render(['form' => $form->createView()])
        ;
    }

    public static function environment(string $theme = self::BULMA_1_LAYOUT, string $locale = 'en'): Environment
    {
        $loader = new FilesystemLoader();
        $loader->addPath(\dirname((string) new \ReflectionClass(FormExtension::class)->getFileName(), 2).'/Resources/views/Form');
        $loader->addPath(\dirname(__DIR__, 2).'/templates', 'SidusAdmin');
        $twig = new Environment($loader, ['strict_variables' => true, 'cache' => false]);

        $renderer = new FormRenderer(new TwigRendererEngine([$theme], $twig));
        $twig->addRuntimeLoader(new FactoryRuntimeLoader([FormRenderer::class => static fn (): FormRenderer => $renderer]));
        $twig->addExtension(new FormExtension());
        $twig->addExtension(new TranslationExtension(new Translator($locale)));

        return $twig;
    }
}
