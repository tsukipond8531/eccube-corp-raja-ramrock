<?php

declare(strict_types=1);

namespace Customize\Form\Extension\Admin;

use Eccube\Common\EccubeConfig;
use Eccube\Form\Type\Admin\CustomerType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

class CustomerTypeExtension extends AbstractTypeExtension
{
    /**
     * @var EccubeConfig
     */
    protected $eccubeConfig;

    /**
     * CustomerType constructor.
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
        return CustomerType::class;
    }

    public function getExtendedTypes(): iterable
    {
        return [CustomerType::class];
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
      $builder
        ->add('password_tip_query', ChoiceType::class, [
            'choices' => [
                '母親の旧姓は？' => '母親の旧姓は？',
                'お気に入りのマンガは？' => 'お気に入りのマンガは？',
                '大好きなペットの名前は' => '大好きなペットの名前は',
                '初恋の人の名前は' => '初恋の人の名前は',
                '面白かった映画は' => '面白かった映画は',
                '尊敬していた先生の名前は' => '尊敬していた先生の名前は',
                '好きな食べ物は' => '好きな食べ物は',
            ]
        ])
        ->add('password_tip_answer', TextType::class, []);
    }
}