<?php

declare(strict_types=1);

namespace Sidus\AdminBundle\Tests\Fixtures\Form;

use Sidus\AdminBundle\Tests\Fixtures\Entity\Post;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class PostType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class)
            ->add('body', TextareaType::class, ['required' => false])
            ->add('status', ChoiceType::class, [
                'choices' => ['Draft' => Post::DRAFT, 'Published' => Post::PUBLISHED, 'Locked' => Post::LOCKED],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Post::class]);
    }
}
