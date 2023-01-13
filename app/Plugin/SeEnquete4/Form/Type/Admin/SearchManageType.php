<?php

/*
 * Copyright(c) 2020 Shadow Enterprise, Inc. All rights reserved.
 * http://www.shadow-ep.co.jp/
 */

namespace Plugin\SeEnquete4\Form\Type\Admin;

use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\OptionsResolver\OptionsResolver;
//use Symfony\Contracts\Translation\TranslatorInterface;

class SearchManageType extends AbstractType
{
    /**
     * コンストラクタ
     */
    public function __construct() {
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $list_status = [
            trans('se_enquete.common.label.invalid') => 0,
            trans('se_enquete.common.label.valid') => 1
        ];

        $builder
            # タイトル / サブタイトル
            ->add('keyword', TextType::class, [
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'placeholder' => trans('se_enquete.common.placeholder.keyword'),
                ],
            ])
            # ステータス
            ->add('status', ChoiceType::class, [
                'mapped' => false,
                'required' => false,
                'choices' => $list_status,
                'expanded' => false,
                'multiple' => false,
                'placeholder' => '----',
            ])
            # 開始日
            ->add('start_date', DateType::class, [
                'mapped' => false,
                'required' => false,
                'input' => 'datetime',
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'placeholder' => [ 
                    'year' => '----', 
                    'month' => '--', 
                    'day' => '--' 
                ],
            ])
            # 終了日
            ->add('end_date', DateType::class, [
                'mapped' => false,
                'required' => false,
                'input' => 'datetime',
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'placeholder' => [ 
                    'year' => '----', 
                    'month' => '--', 
                    'day' => '--' 
                ],
            ]);

    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {

//        $resolver->setDefaults([
//        ]);

    }

}
