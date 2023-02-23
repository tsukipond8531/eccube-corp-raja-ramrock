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
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

class PlanChangeType extends AbstractType
{
    /**
     * @var EccubeConfig
     */
    protected $eccubeConfig;

    /**
     * ContactType constructor.
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
            ->add('customer_id', TextType::class, [
                'required' => true,
            ])
            ->add('name', NameType::class, [
                'required' => true,
            ])
            ->add('kana', KanaType::class, [
                'required' => false,
            ])
            ->add('email', EmailType::class, [
                'constraints' => [
                    new Assert\NotBlank(),
                    new Email(['strict' => $this->eccubeConfig['eccube_rfc_email_check']]),
                ],
            ])
            ->add('payment_method', ChoiceType::class, [
                'required' => false,
                'choices' => [
                    'クレジットカード変更' => 'クレジットカード変更',
                    '⼝座情報変更' => '⼝座情報変更',
                    'クレジットカードから⼝座振替へ変更' => 'クレジットカードから⼝座振替へ変更',
                    '⼝座振替からクレジットカードへ変更' => '⼝座振替からクレジットカードへ変更',
                ],
                'expanded' => true,
                'multiple' => false,
            ])
            ->add('option1', ChoiceType::class, [
                'required' => false,
                'choices' => [
                    '検知オプション追加' => '検知オプション追加',
                    '検知オプション削除' => '検知オプション削除',
                ],
            ])
            ->add('option2_origin', ChoiceType::class, [
                'required' => true,
                'choices' => [
                    '' => '',
                    '1' => '1',
                    '2' => '2',
                    '3' => '3',
                    '4' => '4',
                    '5' => '5',
                    '6' => '6',
                    '7' => '7',
                    '8' => '8',
                    '9' => '9',
                    '10' => '10',
                ],
                'constraints' => [
                    new Assert\NotBlank(),
                ],
            ])
            ->add('option2', ChoiceType::class, [
                'required' => true,
                'choices' => [
                    '' => '',
                    '1' => '1',
                    '2' => '2',
                    '3' => '3',
                    '4' => '4',
                    '5' => '5',
                    '6' => '6',
                    '7' => '7',
                    '8' => '8',
                    '9' => '9',
                    '10' => '10',
                ],
                'constraints' => [
                    new Assert\NotBlank(),
                ],
            ])
            ->add('contents', TextareaType::class, [
                'required' => false,
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'plan_change';
    }
}
