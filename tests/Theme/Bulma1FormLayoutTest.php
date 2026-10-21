<?php

declare(strict_types=1);

namespace Sidus\AdminBundle\Tests\Theme;

use PHPUnit\Framework\TestCase;
use Sidus\AdminBundle\Tests\Support\TwigFormRenderer;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\ResetType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Forms;

final class Bulma1FormLayoutTest extends TestCase
{
    private const CHOICES = ['Red' => 'red', 'Green' => 'green', 'Blue' => 'blue'];

    public function testRowsLabelsHelpAndInputs(): void
    {
        $c = $this->crawl($this->form());

        self::assertCount(1, $c->filter('div.field > label.label[for="form_title"]'));
        self::assertCount(1, $c->filter('div.field > div.control > input.input#form_title[type="text"]'));
        self::assertCount(1, $c->filter('div.field > p.help#form_title_help'));
        self::assertSame('The title', trim($c->filter('#form_title_help')->text()));
        self::assertCount(1, $c->filter('div.control > textarea.textarea#form_body'));
        foreach (['email' => 'email', 'password' => 'password', 'amount' => 'text', 'birthday' => 'date', 'publishedAt' => 'datetime-local'] as $name => $type) {
            self::assertCount(1, $c->filter(\sprintf('div.control > input.input#form_%s[type="%s"]', $name, $type)), $name);
        }
        self::assertCount(0, $c->filter('input.input[type="hidden"]'));
        self::assertCount(1, $c->filter('input[type="hidden"]#form_token'));
        self::assertCount(0, $c->filter('input.input.is-danger'));
        self::assertCount(0, $c->filter('.help.is-danger'));
    }

    public function testChoices(): void
    {
        $c = $this->crawl($this->form());

        self::assertCount(1, $c->filter('div.control > div.select.is-fullwidth > select#form_color'));
        self::assertCount(0, $c->filter('div.select.is-multiple > select#form_color'));
        self::assertCount(4, $c->filter('#form_color option'), 'placeholder + 3 choices');
        self::assertCount(1, $c->filter('div.select.is-multiple > select#form_colors[multiple]'));
        self::assertCount(2, $c->filter('#form_group optgroup'));

        self::assertCount(3, $c->filter('div.radios#form_size label.radio > input[type="radio"]'));
        self::assertCount(3, $c->filter('div.checkboxes#form_sizes label.checkbox > input[type="checkbox"]'));
        self::assertStringContainsString('Red', $c->filter('div.radios#form_size label.radio')->first()->text());
    }

    public function testCheckbox(): void
    {
        $c = $this->crawl($this->form());

        self::assertCount(1, $c->filter('div.field > div.control > label.checkbox > input#form_agree[type="checkbox"]'));
        self::assertStringContainsString('I agree', $c->filter('label.checkbox:has(#form_agree)')->text());
        self::assertCount(0, $c->filter('label.label[for="form_agree"]'), 'the label is inside label.checkbox');
    }

    public function testDateAndTimeChoiceWidgets(): void
    {
        $c = $this->crawl($this->form());

        self::assertCount(3, $c->filter('div.field.is-grouped#form_dateChoice > div.control > div.select > select'));
        self::assertCount(2, $c->filter('div.field.is-grouped#form_timeChoice > div.control > div.select > select'));
        self::assertCount(5, $c->filter('div.field.is-grouped#form_dateTimeChoice div.select > select'));
    }

    public function testCollectionAndRepeated(): void
    {
        $c = $this->crawl($this->form());

        self::assertCount(2, $c->filter('#form_tags > div.field > div.control > input.input'));
        $prototype = $c->filter('#form_tags')->attr('data-prototype');
        self::assertNotNull($prototype);
        self::assertCount(1, $this->fragment($prototype)->filter('div.field > div.control > input.input'));

        self::assertCount(2, $c->filter('div.field > div.control > input.input[type="password"][id^="form_pass_"]'));
    }

    public function testFileInput(): void
    {
        $c = $this->crawl($this->form());

        self::assertCount(1, $c->filter('div.file > label.file-label > input.file-input[type="file"]#form_upload'));
        self::assertCount(1, $c->filter('div.file span.file-cta > span.file-label'));
        self::assertSame('Choose a file…', trim($c->filter('div.file span.file-label')->text()));
    }

    public function testButtons(): void
    {
        $c = $this->crawl($this->form());

        self::assertCount(1, $c->filter('div.field > div.control > button.button.is-primary[type="submit"]#form_save'));
        self::assertCount(1, $c->filter('button.button.is-light[type="reset"]#form_reset'));
        self::assertCount(1, $c->filter('button.is-info#form_custom'), 'an explicit Bulma color is kept');
        self::assertCount(0, $c->filter('button.is-primary#form_custom'));
        self::assertSame('is-info button', $c->filter('#form_custom')->attr('class'));
        self::assertSame('button is-primary', $c->filter('#form_save')->attr('class'), 'the button class is not duplicated');
        self::assertSame('Save', trim($c->filter('#form_save')->text()));
    }

    public function testErrors(): void
    {
        $form = $this->form();
        $form->get('title')->addError(new FormError('Too short'));
        $form->get('color')->addError(new FormError('Bad color'));
        $form->get('body')->addError(new FormError('Required'));
        $form->addError(new FormError('Global failure'));
        $c = $this->crawl($form);

        self::assertCount(1, $c->filter('input.input.is-danger#form_title'));
        self::assertCount(1, $c->filter('textarea.textarea.is-danger#form_body'));
        self::assertCount(1, $c->filter('div.select.is-danger > select#form_color'));
        self::assertCount(0, $c->filter('input.input.is-danger#form_email'));
        self::assertCount(1, $c->filter('div.field > p.help.is-danger#form_title_error1'));
        self::assertSame('Too short', trim($c->filter('#form_title_error1')->text()));
        self::assertCount(3, $c->filter('p.help.is-danger'));
        self::assertCount(1, $c->filter('div.notification.is-danger[role="alert"]'));
        self::assertStringContainsString('Global failure', $c->filter('div.notification.is-danger')->text());
    }

    public function testDisabled(): void
    {
        $form = Forms::createFormFactoryBuilder()->getFormFactory()->createBuilder(FormType::class, null, [])
            ->add('name', TextType::class, ['disabled' => true])
            ->add('ok', CheckboxType::class, ['disabled' => true, 'label' => 'OK'])
            ->add('pick', ChoiceType::class, ['choices' => self::CHOICES, 'disabled' => true])
            ->getForm()
        ;
        $c = $this->crawl($form);

        self::assertCount(1, $c->filter('input.input[disabled]'));
        self::assertCount(1, $c->filter('label.checkbox[disabled] > input[disabled]'));
        self::assertCount(1, $c->filter('div.select > select[disabled]'));
    }

    public function testNoBootstrapMarkup(): void
    {
        $form = $this->form();
        $form->get('title')->addError(new FormError('Too short'));
        $form->addError(new FormError('Global failure'));
        $html = TwigFormRenderer::render($form);

        self::assertDoesNotMatchRegularExpression('/\b(form-control|form-group|form-check|form-select|invalid-feedback|btn(-[a-z]+)?|col-[a-z0-9-]+)\b/', $html);
        self::assertStringNotContainsString('is-invalid', $html);
    }

    private function form(): FormInterface
    {
        return Forms::createFormFactoryBuilder()->getFormFactory()->createBuilder(FormType::class, null, ['attr' => ['novalidate' => 'novalidate']])
            ->add('title', TextType::class, ['help' => 'The title'])
            ->add('body', TextareaType::class)
            ->add('email', EmailType::class)
            ->add('password', PasswordType::class)
            ->add('amount', NumberType::class)
            ->add('birthday', DateType::class, ['widget' => 'single_text'])
            ->add('publishedAt', DateTimeType::class, ['widget' => 'single_text'])
            ->add('dateChoice', DateType::class, ['widget' => 'choice'])
            ->add('timeChoice', TimeType::class, ['widget' => 'choice'])
            ->add('dateTimeChoice', DateTimeType::class, ['widget' => 'choice'])
            ->add('color', ChoiceType::class, ['choices' => self::CHOICES, 'placeholder' => 'Pick one'])
            ->add('colors', ChoiceType::class, ['choices' => self::CHOICES, 'multiple' => true])
            ->add('group', ChoiceType::class, ['choices' => ['Warm' => ['Red' => 'red'], 'Cold' => ['Blue' => 'blue']]])
            ->add('size', ChoiceType::class, ['choices' => self::CHOICES, 'expanded' => true])
            ->add('sizes', ChoiceType::class, ['choices' => self::CHOICES, 'expanded' => true, 'multiple' => true])
            ->add('agree', CheckboxType::class, ['label' => 'I agree'])
            ->add('tags', CollectionType::class, ['entry_type' => TextType::class, 'allow_add' => true, 'allow_delete' => true, 'data' => ['a', 'b']])
            ->add('pass', RepeatedType::class, ['type' => PasswordType::class])
            ->add('upload', FileType::class)
            ->add('token', HiddenType::class)
            ->add('save', SubmitType::class, ['label' => 'Save'])
            ->add('reset', ResetType::class)
            ->add('custom', SubmitType::class, ['attr' => ['class' => 'is-info']])
            ->getForm()
        ;
    }

    private function crawl(FormInterface $form): Crawler
    {
        return $this->fragment(TwigFormRenderer::render($form));
    }

    private function fragment(string $html): Crawler
    {
        return new Crawler('<!DOCTYPE html><html><body>'.$html.'</body></html>');
    }
}
