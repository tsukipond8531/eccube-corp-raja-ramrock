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
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class ContactLightType extends AbstractType
{
    /**
     * @var EccubeConfig
     */
    protected $eccubeConfig;

    /**
     * ContactLightType constructor.
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
            ->add('name', NameType::class, [
                'required' => true,
            ])
            ->add('kana', KanaType::class, [
                'required' => true,
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
            ->add('company', TextType::class, [
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(),
                ],
            ])
            ->add('department', TextType::class, [
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(),
                ],
            ])
            ->add('query_type', ChoiceType::class, [
                'required' => true,
                'choices' => [
                    '' => '',
                    '資料請求' => '資料請求',
                    '料金について' => '料金について',
                    '無償貸出について' => '無償貸出について',
                    '代理店希望' => '代理店希望',
                    'その他' => 'その他',
                ],
                'constraints' => [
                    new Assert\NotBlank(),
                ],
            ])
            ->add('delivery_method', ChoiceType::class, [
                'required' => true,
                'choices' => [
                    '郵送' => '郵送',
                    'データ' => 'データ',
                    '資料請求を希望しない' => '資料請求を希望しない',
                ],
                'expanded' => true,
                'multiple' => false,
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
            ->add('phone_number', PhoneNumberType::class, [
                'required' => true,
            ])
            ->add('email', EmailType::class, [
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(),
                    new Email(['strict' => $this->eccubeConfig['eccube_rfc_email_check']]),
                ],
            ])
            ->add('moment', ChoiceType::class, [
                'required' => true,
                'choices' => [
                    'インターネット検索' => 'インターネット検索',
                    'DM(ダイレクトメール)' => 'DM(ダイレクトメール)',
                    '新聞・広告・チラシ' => '新聞・広告・チラシ',
                    '紹介' => '紹介',
                    'SNS' => 'SNS',
                    'FAX' => 'FAX',
                    'DM' => 'DM',
                    'その他' => 'その他',
                ],
                'expanded' => true,
                'multiple' => true,
                'constraints' => [
                    new Assert\NotBlank(),
                ],
            ])
            ->add('keyword', TextareaType::class, [
                'required' => false,
            ])
            ->add('contents', TextareaType::class, [
                'required' => false,
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

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'contact_light';
    }
}
