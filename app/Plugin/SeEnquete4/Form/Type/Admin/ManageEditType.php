<?php

/*
 * Copyright(c) 2020 Shadow Enterprise, Inc. All rights reserved.
 * http://www.shadow-ep.co.jp/
 */

namespace Plugin\SeEnquete4\Form\Type\Admin;

use Doctrine\ORM\EntityRepository;
use Plugin\SeEnquete4\Entity\Enquete;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class ManageEditType extends AbstractType
{

    /**
     * コンストラクタ
     */
    public function __construct() {
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder
            # タイトル
            ->add('title', TextType::class, [
                //'mapped' => false,
                'required' => true,
                'attr' => [
                    'placeholder' => trans('se_enquete.common.placeholder.title'),
                ],
            ])
            # サブタイトル 
            ->add('sub_title', TextareaType::class, [
                //'mapped' => false,
                'required' => false,
                'attr' => [
                    'placeholder' => trans('se_enquete.common.placeholder.sub_title'),
                ],
            ])
            # 会員専用
            ->add('member_flg', ChoiceType::class, [
                //'mapped' => false,
                'required' => true,
                'choices' => array_flip($options['list_memberFlg']),
                'expanded' => false,
                'multiple' => false,
                //'placeholder' => '----',
                //'data' => 0,
                'data_class' => null,
            ])
            # メール送信有無
            ->add('mail_flg', ChoiceType::class, [
                //'mapped' => false,
                'required' => true,
                'choices' => array_flip($options['list_mailFlg']),
                'expanded' => false,
                'multiple' => false,
                //'placeholder' => '----',
                //'data' => 0,
                'data_class' => null,
            ])
            # メール送信先
            ->add('address_list', TextareaType::class, [
                //'mapped' => false,
                'required' => false,
                'attr' => [
                    'placeholder' => trans('se_enquete.common.placeholder.address_list'),
                ],
            ])
            # サムネイル 
            ->add('thumbnail', FileType::class, [
                'mapped' => false,
                'required' => false,
                //'doc_path' => 'nurseryDocumentsPath',
                //'doc_name' => 'docUrl',
                'constraints' => [
                    new File([
                        'maxSize' => '1024k',
                        'mimeTypes' => [
                            'image/gif',
                            'image/png',
                            'image/jpg',
                            'image/jpeg',
                            'application/gif',
                            'application/png',
                            'application/jpeg',
                        ],
                        'mimeTypesMessage' => trans('se_enquete.common.message.possible_filetype', [ '%type%' => $options['mime_types']] ),
                    ])
                ],
                //'data_class' => null,
            ])
            # 個人情報の取得
            ->add('personal_flg', ChoiceType::class, [
                //'mapped' => false,
                'required' => true,
                'choices' => array_flip($options['list_personalFlg']),
                'expanded' => false,
                'multiple' => false,
                //'placeholder' => '----',
                //'data' => 0,
                'data_class' => null,
            ])
            # 同意するリンクのタイトル
            ->add('personal_title', TextType::class, [
                //'mapped' => false,
                'required' => false,
                'attr' => [
                    'placeholder' => trans('se_enquete.common.placeholder.personal_title'),
                ],
            ])
            # 個人情報の取扱いについて
            ->add('personal_text', TextareaType::class, [
                //'mapped' => false,
                'required' => false,
                'attr' => [
                    'placeholder' => trans('se_enquete.common.placeholder.personal_text'),
                ],
            ])
            # サブミットタイトル
            ->add('submit_title', TextType::class, [
                //'mapped' => false,
                'required' => true,
                'attr' => [
                    'placeholder' => trans('se_enquete.common.placeholder.submit_title'),
                ],
                'data' => trans('se_enquete.common.label.submit_title'),
            ])
            # 開始日
            ->add('start_date', DateType::class, [
                //'mapped' => false,
                'required' => true,
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
                //'mapped' => false,
                'required' => true,
                'input' => 'datetime',
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'placeholder' => [ 
                    'year' => '----', 
                    'month' => '--', 
                    'day' => '--' 
                ],
                //'data' => new \DateTime('2050-12-31 00:00:00', new \DateTimeZone('Asia/Tokyo')),
            ])
            # ステータス
            ->add('status', ChoiceType::class, [
                //'mapped' => false,
                'required' => true,
                'choices' => array_flip($options['list_status']),
                'expanded' => false,
                'multiple' => false,
                'placeholder' => '----',
                //'data_class' => null,
            ]);

    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {

        $resolver->setDefaults([
            'data_class' => 'Plugin\SeEnquete4\Entity\Enquete',
            'allow_extra_fields' => true,
            'list_memberFlg' => [],
            'list_mailFlg' => [],
            'list_status' => [],
            'mime_types' => '',
            'list_personalFlg' => [],
        ]);

    }

}
