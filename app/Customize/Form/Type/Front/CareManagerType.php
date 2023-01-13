<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) EC-CUBE CO.,LTD. All Rights Reserved.
 *
 * http://www.ec-cube.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Customize\Form\Type\Front;

use Eccube\Common\EccubeConfig;
use Eccube\Form\Type\AddressType;
use Eccube\Form\Type\KanaType;
use Eccube\Form\Type\NameType;
use Eccube\Form\Type\PhoneNumberType;
use Eccube\Form\Type\PostalType;
use Eccube\Form\Validator\Email;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

class CareManagerType extends AbstractType
{
    /**
     * @var EccubeConfig
     */
    protected $eccubeConfig;

    /**
     * CareManagerType constructor.
     *
     * @param EccubeConfig $eccubeConfig
     */
    public function __construct(EccubeConfig $eccubeConfig)
    {
        $this->eccubeConfig = $eccubeConfig;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('company', TextType::class, [
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(),
                ],
            ])
            ->add('name', NameType::class, [
                'required' => true,
            ])
            ->add('kana', KanaType::class, [
                'required' => true,
            ])
            ->add('type', ChoiceType::class, [
                'required' => true,
                'choices' => [
                    '' => '',
                    'ケアマネージャー' => 'ケアマネージャー',
                    '福祉用具レンタル事業所' => '福祉用具レンタル事業所',
                ],
                'constraints' => [
                    new Assert\NotBlank(),
                ],
            ])
            ->add('postal_code', PostalType::class, [
                'required' => false,
            ])
            ->add('address', AddressType::class, [
                'required' => false,
            ])
            ->add('email', EmailType::class, [
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(),
                    new Email(['strict' => $this->eccubeConfig['eccube_rfc_email_check']]),
                ],
            ])
            ->add('contents', ChoiceType::class, [
                'required' => true,
                'choices' => [
                    '' => '',
                    '取り扱いがある貸与事業所の情報' => '取り扱いがある貸与事業所の情報',
                    '代理店様向け資料請求' => '代理店様向け資料請求',
                    '介護保険用資料' => '介護保険用資料',
                    'その他' => 'その他',
                ],
                'constraints' => [
                    new Assert\NotBlank(),
                ],
            ])
            ->add('delivery_method', ChoiceType::class, [
                'required' => false,
                'choices' => [
                    '郵送（ご連絡先の入力が必須）' => '郵送（ご連絡先の入力が必須）',
                    'データ送付' => 'データ送付',
                    '資料請求を希望しない' => '資料請求を希望しない',
                ],
                'expanded' => true,
                'multiple' => false,
            ])
            ->add('moment', ChoiceType::class, [
                'required' => true,
                'choices' => [
                    'インターネット検索' => 'インターネット検索',
                    'ご家族様からのご依頼' => 'ご家族様からのご依頼',
                    'ケアマネージャー様からのご依頼' => 'ケアマネージャー様からのご依頼',
                    '弊社営業担当' => '弊社営業担当',
                    'その他' => 'その他',
                ],
                'expanded' => true,
                'multiple' => false,
                'constraints' => [
                    new Assert\NotBlank(),
                ],
            ])
            ->add('keyword', TextareaType::class, [
                'required' => false,
            ])
            ->add('query', TextareaType::class, [
                'required' => false,
            ])
            ->add('prefix', HiddenType::class, [
                'required' => true,
                'empty_data' => 'care_manager',
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'care_manager';
    }
}
