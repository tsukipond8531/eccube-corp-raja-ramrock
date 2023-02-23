<?php

declare(strict_types=1);

namespace Customize\Form\Extension\Shopping;

use Eccube\Common\EccubeConfig;
use Eccube\Form\Type\Shopping\OrderType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;

class OrderTypeExtension extends AbstractTypeExtension
{
    /**
     * @var EccubeConfig
     */
    protected $eccubeConfig;

    /**
     * OrderType constructor.
     *
     * @param EccubeConfig $eccubeConfig
     */
    public function __construct(
      EccubeConfig $eccubeConfig
    ) {
        $this->eccubeConfig = $eccubeConfig;
    }

    public function getExtendedType(): string
    {
        return OrderType::class;
    }

    public function getExtendedTypes(): iterable
    {
        return [OrderType::class];
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
      $builder
        ->add('watch_target', ChoiceType::class, [
            'required' => true,
            'choices' => [
                '見守り対象者がいます' => 1,
                '見守り対象者がいません' => 0,
            ],
            'expanded' => true,
            'multiple' => false,
            'mapped' => false,
            'constraints' => [
                new Assert\NotBlank(),
            ],
        ])
        ->add('image0', TextType::class, [
            'required' => true,
            'mapped' => false,
            'constraints' => [
                new Assert\NotBlank(),
            ],
        ])
        ->add('customer_image', FileType::class, [
            'multiple' => true,
            'required' => false,
            'mapped' => false,
        ])
        // 画像
        ->add('images', CollectionType::class, [
            'entry_type' => HiddenType::class,
            'prototype' => true,
            'mapped' => false,
            'allow_add' => true,
            'allow_delete' => true,
        ])
        ->add('add_images', CollectionType::class, [
            'entry_type' => HiddenType::class,
            'prototype' => true,
            'mapped' => false,
            'allow_add' => true,
            'allow_delete' => true,
        ])
        ->add('delete_images', CollectionType::class, [
            'entry_type' => HiddenType::class,
            'prototype' => true,
            'mapped' => false,
            'allow_add' => true,
            'allow_delete' => true,
        ]);
    }
}