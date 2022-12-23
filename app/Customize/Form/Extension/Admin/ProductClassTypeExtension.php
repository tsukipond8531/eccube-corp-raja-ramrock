<?php

declare(strict_types=1);

namespace Customize\Form\Extension\Admin;

use Eccube\Common\EccubeConfig;
use Eccube\Form\Type\Admin\ProductClassType;
use Symfony\Component\Form\AbstractTypeExtension;
use Eccube\Form\Type\PriceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

class ProductClassTypeExtension extends AbstractTypeExtension
{
    /**
     * @var EccubeConfig
     */
    protected $eccubeConfig;

    /**
     * ProductClassType constructor.
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
        return ProductClassType::class;
    }

    public function getExtendedTypes(): iterable
    {
        return [ProductClassType::class];
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
        ]);
    }
}