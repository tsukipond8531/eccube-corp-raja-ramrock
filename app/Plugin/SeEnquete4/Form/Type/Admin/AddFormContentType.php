<?php

/*
 * Copyright(c) 2020 Shadow Enterprise, Inc. All rights reserved.
 * http://www.shadow-ep.co.jp/
 */

namespace Plugin\SeEnquete4\Form\Type\Admin;

use Doctrine\ORM\EntityRepository;
use Plugin\SeEnquete4\Repository\EnqueteConfigRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\OptionsResolver\OptionsResolver;
//use Symfony\Contracts\Translation\TranslatorInterface;

class AddFormContentType extends AbstractType
{

    public const _FORM_NAME_PREFIX_ = 'option_addform_';

    /**
     * @var EnqueteConfigRepository
     */
    protected $enqueteConfigRepository;

    /**
     * AddFormContentType constructor.
     *
     * @param EnqueteConfigRepository $enqueteConfigRepository
     */
    public function __construct(
        EnqueteConfigRepository $enqueteConfigRepository
    ) {
        $this->enqueteConfigRepository = $enqueteConfigRepository;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $enqueteConfig = $this->enqueteConfigRepository->findBy([
                'deleted' => 0
            ], [ 'sort_no' => 'asc' ]);

        $list_config = [];
        if ( $enqueteConfig ) {
            foreach ( $enqueteConfig as $key => $value ) {
                $list_config[ $value->getTitle() ] = $value->getId();
            }
        }

        $builder
            # フォームタイプ
            ->add(self::_FORM_NAME_PREFIX_ .$options['identification_id'] .'_child_form_type', ChoiceType::class, [
                'mapped' => false,
                'required' => false,
                'choices' => $list_config,
                'expanded' => false,
                'multiple' => false,
                'placeholder' => '----',
                'data' => ( isset($options['child_DBDatas']['config_id']) ) ? $options['child_DBDatas']['config_id'] : '' ,
            ]);

        // フォーム種によって項目を追加する
        if ( $enqueteConfig ) {
            foreach ( $enqueteConfig as $key => $value ) {
                switch ( $value->getKeyword() ) {
                    case 'text':
                        $builder
                            # 質問文
                            ->add(self::_FORM_NAME_PREFIX_ .$value->getKeyword() .'_' .$options['identification_id'] .'_question', TextareaType::class, [
                                'mapped' => false,
                                'required' => false,
                                'attr' => [
                                    'placeholder' => trans('se_enquete.common.addform_placeholder.question'),
                                ],
                                'data' => ( isset($options['child_DBDatas']['title_' .$value->getKeyword()]) ) ? $options['child_DBDatas']['title_' .$value->getKeyword()] : '' ,
                            ])
                            # プレースホルダ
                            ->add(self::_FORM_NAME_PREFIX_ .$value->getKeyword() .'_' .$options['identification_id'] .'_placeholder', TextType::class, [
                                'mapped' => false,
                                'required' => false,
                                'attr' => [
                                    'placeholder' => trans('se_enquete.common.addform_placeholder.placeholder'),
                                ],
                                'data' => ( isset($options['child_DBDatas']['placeholder_' .$value->getKeyword()]) ) ? $options['child_DBDatas']['placeholder_' .$value->getKeyword()] : '' ,
                            ])
                            # 必須の有無
                            ->add(self::_FORM_NAME_PREFIX_ .$value->getKeyword() .'_' .$options['identification_id'] .'_required', ChoiceType::class, [
                                'mapped' => false,
                                'required' => false,
                                'choices' => array_flip($options['list_required']),
                                'expanded' => true,
                                'multiple' => false,
                                'data' => ( isset($options['child_DBDatas']['ness_flg_' .$value->getKeyword()]) ) ? $options['child_DBDatas']['ness_flg_' .$value->getKeyword()] : 0 ,
                                'placeholder' => false,
                            ]);
                        break;
                    case 'textarea':
                        $builder
                            # 質問文
                            ->add(self::_FORM_NAME_PREFIX_ .$value->getKeyword() .'_' .$options['identification_id'] .'_question', TextareaType::class, [
                                'mapped' => false,
                                'required' => false,
                                'attr' => [
                                    'placeholder' => trans('se_enquete.common.addform_placeholder.question'),
                                ],
                                'data' => ( isset($options['child_DBDatas']['title_' .$value->getKeyword()]) ) ? $options['child_DBDatas']['title_' .$value->getKeyword()] : '' ,
                            ])
                            # プレースホルダ
                            ->add(self::_FORM_NAME_PREFIX_ .$value->getKeyword() .'_' .$options['identification_id'] .'_placeholder', TextType::class, [
                                'mapped' => false,
                                'required' => false,
                                'attr' => [
                                    'placeholder' => trans('se_enquete.common.addform_placeholder.placeholder'),
                                ],
                                'data' => ( isset($options['child_DBDatas']['placeholder_' .$value->getKeyword()]) ) ? $options['child_DBDatas']['placeholder_' .$value->getKeyword()] : '' ,
                            ])
                            # 必須の有無
                            ->add(self::_FORM_NAME_PREFIX_ .$value->getKeyword() .'_' .$options['identification_id'] .'_required', ChoiceType::class, [
                                'mapped' => false,
                                'required' => false,
                                'choices' => array_flip($options['list_required']),
                                'expanded' => true,
                                'multiple' => false,
                                'data' => ( isset($options['child_DBDatas']['ness_flg_' .$value->getKeyword()]) ) ? $options['child_DBDatas']['ness_flg_' .$value->getKeyword()] : '' ,
                                'placeholder' => false,
                            ]);
                        break;
                    case 'pulldown':
                        $builder
                            # 質問文
                            ->add(self::_FORM_NAME_PREFIX_ .$value->getKeyword() .'_' .$options['identification_id'] .'_question', TextareaType::class, [
                                'mapped' => false,
                                'required' => false,
                                'attr' => [
                                    'placeholder' => trans('se_enquete.common.addform_placeholder.question'),
                                ],
                                'data' => ( isset($options['child_DBDatas']['title_' .$value->getKeyword()]) ) ? $options['child_DBDatas']['title_' .$value->getKeyword()] : '' ,
                            ])
                            # 必須の有無
                            ->add(self::_FORM_NAME_PREFIX_ .$value->getKeyword() .'_' .$options['identification_id'] .'_required', ChoiceType::class, [
                                'mapped' => false,
                                'required' => false,
                                'choices' => array_flip($options['list_required']),
                                'expanded' => true,
                                'multiple' => false,
                                'data' => ( isset($options['child_DBDatas']['ness_flg_' .$value->getKeyword()]) ) ? $options['child_DBDatas']['ness_flg_' .$value->getKeyword()] : '' ,
                                'placeholder' => false,
                            ])
                            # 選択肢（エラー表示用）
                            ->add(self::_FORM_NAME_PREFIX_ .$value->getKeyword() .'_' .$options['identification_id'] .'_answer', HiddenType::class, [
                                'mapped' => false,
                                'required' => false,
                                'error_bubbling' => false,
                            ]);

                        $roopNum = $options['child_row'];
                        if ( isset($options['child_DBDatas']['children_' .$value->getKeyword()]) && ( count($options['child_DBDatas']['children_' .$value->getKeyword()]) != $roopNum ) ) {
                            $roopNum = count($options['child_DBDatas']['children_' .$value->getKeyword()]);
                        }
                        for ( $i=1; $i<=(int)$roopNum; $i++ ) {
                            $builder
                                # 複数選択肢
                                ->add(self::_FORM_NAME_PREFIX_ .$value->getKeyword() .'_' .$options['identification_id'] .'_answer_' .$i, TextType::class, [
                                    'mapped' => false,
                                    'required' => false,
                                    'attr' => [
                                        'placeholder' => trans('se_enquete.common.addform_placeholder.choice'),
                                    ],
                                    'data' => ( isset($options['child_DBDatas']['children_' .$value->getKeyword()][$i-1]['values']) ) ? $options['child_DBDatas']['children_' .$value->getKeyword()][$i-1]['values'] : '' ,
                                ]);
                        }

                        break;
                    case 'radio':
                        $builder
                            # 質問文
                            ->add(self::_FORM_NAME_PREFIX_ .$value->getKeyword() .'_' .$options['identification_id'] .'_question', TextareaType::class, [
                                'mapped' => false,
                                'required' => false,
                                'attr' => [
                                    'placeholder' => trans('se_enquete.common.addform_placeholder.question'),
                                ],
                                'data' => ( isset($options['child_DBDatas']['title_' .$value->getKeyword()]) ) ? $options['child_DBDatas']['title_' .$value->getKeyword()] : '' ,
                            ])
                            # 必須の有無
                            ->add(self::_FORM_NAME_PREFIX_ .$value->getKeyword() .'_' .$options['identification_id'] .'_required', ChoiceType::class, [
                                'mapped' => false,
                                'required' => false,
                                'choices' => array_flip($options['list_required']),
                                'expanded' => true,
                                'multiple' => false,
                                'data' => ( isset($options['child_DBDatas']['ness_flg_' .$value->getKeyword()]) ) ? $options['child_DBDatas']['ness_flg_' .$value->getKeyword()] : '' ,
                                'placeholder' => false,
                            ])
                            # 表示タイプ（エラー表示用）
                            ->add(self::_FORM_NAME_PREFIX_ .$value->getKeyword() .'_' .$options['identification_id'] .'_view_type', HiddenType::class, [
                                'mapped' => false,
                                'required' => false,
                                'error_bubbling' => false,
                            ]);

                        $roopNum = $options['child_row'];
                        if ( isset($options['child_DBDatas']['children_' .$value->getKeyword()]) && ( count($options['child_DBDatas']['children_' .$value->getKeyword()]) != $roopNum ) ) {
                            $roopNum = count($options['child_DBDatas']['children_' .$value->getKeyword()]);
                        }
                        for ( $i=1; $i<=(int)$roopNum; $i++ ) {
                            $builder
                                # 複数表示タイプ
                                ->add(self::_FORM_NAME_PREFIX_ .$value->getKeyword() .'_' .$options['identification_id'] .'_view_type_' .$i, ChoiceType::class, [
                                    'mapped' => false,
                                    'required' => false,
                                    'choices' => array_flip($options['list_radio']),
                                    'expanded' => false,
                                    'multiple' => false,
                                    'placeholder' => false,
                                    'data' => ( isset($options['child_DBDatas']['children_' .$value->getKeyword()][$i-1]) ) ? $options['child_DBDatas']['children_' .$value->getKeyword()][$i-1]['type'] : 0 ,
                                ])
                                # 複数選択肢：テキスト
                                ->add(self::_FORM_NAME_PREFIX_ .$value->getKeyword() .'_' .$options['identification_id'] .'_answer_text_' .$i, TextType::class, [
                                    'mapped' => false,
                                    'required' => false,
                                    'attr' => [
                                        'placeholder' => trans('se_enquete.common.addform_placeholder.choice'),
                                    ],
                                    'data' => ( isset($options['child_DBDatas']['children_' .$value->getKeyword()][$i-1]['values']) ) ? $options['child_DBDatas']['children_' .$value->getKeyword()][$i-1]['values'] : '' ,
                                ])
                                # 複数選択肢：サムネイル
                                ->add(self::_FORM_NAME_PREFIX_ .$value->getKeyword() .'_' .$options['identification_id'] .'_answer_thumbnail_' .$i, FileType::class, [
                                    'mapped' => false,
                                    'required' => false,
                                ])
                                # 複数選択肢：サムネイル(既存用Hidden)
                                ->add(self::_FORM_NAME_PREFIX_ .$value->getKeyword() .'_' .$options['identification_id'] .'_answer_hidden_' .$i, HiddenType::class, [
                                    'mapped' => false,
                                    'required' => false,
                                    'data' => ( isset($options['child_DBDatas']['children_' .$value->getKeyword()][$i-1]['values']) ) ? $options['child_DBDatas']['children_' .$value->getKeyword()][$i-1]['values'] : '' ,
                                ]);
                        }

                        break;
                    case 'check':
                        $builder
                            # 質問文
                            ->add(self::_FORM_NAME_PREFIX_ .$value->getKeyword() .'_' .$options['identification_id'] .'_question', TextareaType::class, [
                                'mapped' => false,
                                'required' => false,
                                'attr' => [
                                    'placeholder' => trans('se_enquete.common.addform_placeholder.question'),
                                ],
                                'data' => ( isset($options['child_DBDatas']['title_' .$value->getKeyword()]) ) ? $options['child_DBDatas']['title_' .$value->getKeyword()] : '' ,
                            ])
                            # 必須の有無
                            ->add(self::_FORM_NAME_PREFIX_ .$value->getKeyword() .'_' .$options['identification_id'] .'_required', ChoiceType::class, [
                                'mapped' => false,
                                'required' => false,
                                'choices' => array_flip($options['list_required']),
                                'expanded' => true,
                                'multiple' => false,
                                'data' => ( isset($options['child_DBDatas']['ness_flg_' .$value->getKeyword()]) ) ? $options['child_DBDatas']['ness_flg_' .$value->getKeyword()] : '' ,
                                'placeholder' => false,
                            ])
                            # 選択肢（エラー表示用）
                            ->add(self::_FORM_NAME_PREFIX_ .$value->getKeyword() .'_' .$options['identification_id'] .'_answer', HiddenType::class, [
                                'mapped' => false,
                                'required' => false,
                                'error_bubbling' => false,
                            ]);

                        $roopNum = $options['child_row'];
                        if ( isset($options['child_DBDatas']['children_' .$value->getKeyword()]) && ( count($options['child_DBDatas']['children_' .$value->getKeyword()]) != $roopNum ) ) {
                            $roopNum = count($options['child_DBDatas']['children_' .$value->getKeyword()]);
                        }
                        for ( $i=1; $i<=(int)$roopNum; $i++ ) {
                            $builder
                            # 複数選択肢
                                ->add(self::_FORM_NAME_PREFIX_ .$value->getKeyword() .'_' .$options['identification_id'] .'_answer_' .$i, TextType::class, [
                                    'mapped' => false,
                                    'required' => false,
                                    'attr' => [
                                        'placeholder' => trans('se_enquete.common.addform_placeholder.choice'),
                                    ],
                                    'data' => ( isset($options['child_DBDatas']['children_' .$value->getKeyword()][$i-1]['values']) ) ? $options['child_DBDatas']['children_' .$value->getKeyword()][$i-1]['values'] : '' ,
                                ]);
                        }

                        break;
                    case 'etc':
                        $builder
                            # 何か文章
                            ->add(self::_FORM_NAME_PREFIX_ .$value->getKeyword() .'_' .$options['identification_id'] .'_sentence', TextareaType::class, [
                                'mapped' => false,
                                'required' => false,
                                'attr' => [
                                    'placeholder' => trans('se_enquete.common.addform_placeholder.etc'),
                                ],
                                'data' => ( isset($options['child_DBDatas']['text_' .$value->getKeyword()]) ) ? $options['child_DBDatas']['text_' .$value->getKeyword()] : '' ,
                            ]);
                        break;
                }
            }
        }

    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {

        $resolver->setDefaults([
            'identification_id' => uniqid(),
            'allow_extra_fields' => true,
            'list_required' => [],
            'list_radio' => [],
            'child_row' => 3,
            'child_DBDatas' => [],
        ]);

    }

}
