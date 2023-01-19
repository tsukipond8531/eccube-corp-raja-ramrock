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
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\FormEvents;

use Eccube\Form\Type\Front\ContactType as BaseType;

class ContactType extends BaseType
{
    public function __construct(EccubeConfig $eccubeConfig)
    {
        parent::__construct($eccubeConfig);
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', NameType::class, [
                'required' => true,
            ])
            ->add('kana', KanaType::class, [
                'required' => false,
            ])
            ->add('age', ChoiceType::class, [
                'required' => true,
                'choices' => [
                    '' => '',
                    '20代未満' => '20代未満',
                    '20代' => '20代',
                    '30代' => '30代',
                    '40代' => '40代',
                    '50代' => '50代',
                    '60代以降' => '60代以降',
                ],
                'constraints' => [
                    new Assert\NotBlank(),
                ],
            ])
            ->add('client', ChoiceType::class, [
                'required' => true,
                'choices' => [
                    'その他' => 'その他',
                    'ケアマネージャー' => 'ケアマネージャー',
                    '貸与事業所' => '貸与事業所',
                ],
                'expanded' => true,
                'multiple' => false,
                'constraints' => [
                    new Assert\NotBlank(),
                ],
            ])
            ->add('company', TextType::class, [
                'required' => false,
            ])
            ->add('product', ChoiceType::class, [
                'required' => true,
                'choices' => [
                    '一般レンタル' => '一般レンタル',
                    '介護保険' => '介護保険',
                    'まだわからない' => 'まだわからない',
                ],
                'expanded' => true,
                'multiple' => false,
                'constraints' => [
                    new Assert\NotBlank(),
                ],
            ])
            ->add('care', ChoiceType::class, [
                'required' => true,
                'choices' => [
                    '' => '',
                    '要介護２以上' => '要介護２以上',
                    '要介護１' => '要介護１',
                    '要支援２' => '要支援２',
                    '要支援１' => '要支援１',
                    '該当なし' => '該当なし',
                    '不明' => '不明',
                ],
                'constraints' => [
                    new Assert\NotBlank(),
                ],
            ])
            ->add('query_type', ChoiceType::class, [
                'required' => true,
                'choices' => [
                    '' => '',
                    '資料請求' => '資料請求',
                    '取り扱いのある貸与事業所の情報' => '取り扱いのある貸与事業所の情報',
                    'その他' => 'その他',
                ],
                'constraints' => [
                    new Assert\NotBlank(),
                ],
            ])
            ->add('usage_type', ChoiceType::class, [
                'required' => true,
                'choices' => [
                    '一般レンタル' => '一般レンタル',
                    '介護保険レンタル' => '介護保険レンタル',
                    '代理店契約' => '代理店契約',
                    'その他' => 'その他',
                ],
                'expanded' => true,
                'multiple' => false,
                'constraints' => [
                    new Assert\NotBlank(),
                ],
            ])
            ->add('delivery_method', ChoiceType::class, [
                'required' => false,
                'expanded' => true,
                'multiple' => false,
                'choices' => [
                    '郵送' => '郵送',
                    'データ' => 'データ',
                ],
            ])
            ->add('postal_code', PostalType::class, [
                'required' => false,
            ])
            ->add('address', AddressType::class, [
                'required' => false,
            ])
            ->add('phone_number', PhoneNumberType::class)
            ->add('email', EmailType::class, [
                'constraints' => [
                    new Assert\NotBlank(),
                    new Email(['strict' => $this->eccubeConfig['eccube_rfc_email_check']]),
                ],
            ])
            ->add('moment', ChoiceType::class, [
                'required' => true,
                'expanded' => true,
                'multiple' => true,
                'choices' => [
                    'インターネット検索' => 'インターネット検索',
                    '新聞・広告・チラシ' => '新聞・広告・チラシ',
                    '紹介' => '紹介',
                    'SNS' => 'SNS',
                    'その他' => 'その他',
                ],
                'constraints' => [
                    new Assert\NotBlank(),
                ],
            ])
            ->add('keyword', TextareaType::class, [
                'required' => false,
            ])
            ->add('contents', TextareaType::class, [
                'required' => false,
                'constraints' => [
                    new Assert\NotBlank(),
                ],
            ])
            ->add('user_policy_check', CheckboxType::class, [
                'required' => true,
                'label' => null,
                'mapped' => false,
                'constraints' => [
                    new Assert\NotBlank(),
                ],
            ]);
    }
}
