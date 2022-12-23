<?php

declare(strict_types=1);

namespace Customize\Form\Extension\Admin;

use Eccube\Common\EccubeConfig;
use Eccube\Form\Type\Admin\ProductClassEditType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Eccube\Form\Type\PriceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

class ProductClassEditTypeExtension extends AbstractTypeExtension
{
    /**
     * @var EccubeConfig
     */
    protected $eccubeConfig;

    /**
     * ProductClassEditType constructor.
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
        return ProductClassEditType::class;
    }

    public function getExtendedTypes(): iterable
    {
        return [ProductClassEditType::class];
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
      $builder
        ->add('default_price', PriceType::class, [
          'required' => true,
          'label' => '月額利用料',
        ])
        ->add('initial_breakdown', TextType::class, [
            'required' => false,
        ])
        ->add('monthly_breakdown', TextType::class, [
            'required' => false,
        ])
        ->add('maintenance_pack', IntegerType::class, [
            'required' => false,
        ]);
    }
}