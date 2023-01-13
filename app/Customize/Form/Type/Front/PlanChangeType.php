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
            ->add('option1', ChoiceType::class, [
                'required' => false,
                'choices' => [
                    '検知オプション追加' => '検知オプション追加',
                    '検知オプション削除' => '検知オプション削除',
                ],
            ])
            ->add('option2', ChoiceType::class, [
                'required' => false,
                'choices' => [
                    '+1GB［+1,100円（税込）］' => '+1GB［+1,100円（税込）］',
                    '+2GB［+2,200円（税込）］' => '+2GB［+2,200円（税込）］',
                    '+3GB［+3,300円（税込）］' => '+3GB［+3,300円（税込）］',
                    '+4GB［+4,400円（税込）］' => '+4GB［+4,400円（税込）］',
                    '-1GB［-1,100円（税込）］' => '-1GB［-1,100円（税込）］',
                    '-2GB［-2,200円（税込）］' => '-2GB［-2,200円（税込）］',
                    '-3GB［-3,300円（税込）］' => '-3GB［-3,300円（税込）］',
                    '-4GB［-4,400円（税込）］' => '-4GB［-4,400円（税込）］',
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
