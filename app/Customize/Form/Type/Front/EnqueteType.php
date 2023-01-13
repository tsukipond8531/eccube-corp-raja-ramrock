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
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

class EnqueteType extends AbstractType
{
    /**
     * @var EccubeConfig
     */
    protected $eccubeConfig;

    /**
     * EnqueteType constructor.
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
            ->add('query1', ChoiceType::class, [
                'required' => true,
                'choices' => [
                    '1人暮らしの高齢者の見守り' => '1人暮らしの高齢者の見守り',
                    '同居の高齢者の見守り' => '同居の高齢者の見守り',
                    'ペットの見守り' => 'ペットの見守り',
                    '子供の見守り' => '子供の見守り',
                    '防犯' => '防犯',
                    'その他' => 'その他',
                ],
                'expanded' => true,
                'multiple' => true,
                'constraints' => [
                    new Assert\NotBlank(),
                ],
            ])
            ->add('query1_other', TextareaType::class, [
                'required' => false,
            ])
            ->add('query2', ChoiceType::class, [
                'required' => true,
                'choices' => [
                    'ネット検索' => 'ネット検索',
                    '広告・新聞・雑誌' => '広告・新聞・雑誌',
                    '知人' => '知人',
                    '新聞・雑誌' => '新聞・雑誌',
                    'Facebook' => 'Facebook',
                    'Instagram' => 'Instagram',
                    'LINE' => 'LINE',
                    'その他' => 'その他',
                ],
                'expanded' => true,
                'multiple' => true,
                'constraints' => [
                    new Assert\NotBlank(),
                ],
            ])
            ->add('query2_other', TextareaType::class, [
                'required' => false,
            ])
            ->add('query3', ChoiceType::class, [
                'required' => true,
                'choices' => [
                    '徘徊' => '徘徊',
                    'きちんと食事をとっているか' => 'きちんと食事をとっているか',
                    '倒れたりしていないか' => '倒れたりしていないか',
                    '何をしているのか' => '何をしているのか',
                    '不審者が入ってきていないか' => '不審者が入ってきていないか',
                    'その他' => 'その他',
                ],
                'expanded' => true,
                'multiple' => true,
                'constraints' => [
                    new Assert\NotBlank(),
                ],
            ])
            ->add('query3_other', TextareaType::class, [
                'required' => false,
            ])
            ->add('query4', ChoiceType::class, [
                'required' => true,
                'choices' => [
                    '1週間以内' => '1週間以内',
                    '１ヶ月以内' => '１ヶ月以内',
                    '３ヶ月以内' => '３ヶ月以内',
                    '半年以上前' => '半年以上前',
                    'その他' => 'その他',
                ],
                'expanded' => true,
                'multiple' => false,
                'constraints' => [
                    new Assert\NotBlank(),
                ],
            ])
            ->add('query4_other', TextareaType::class, [
                'required' => false,
            ])
            ->add('query5', ChoiceType::class, [
                'required' => true,
                'choices' => [
                    'ある' => 'ある',
                    'ない' => 'ない',
                ],
                'expanded' => true,
                'multiple' => false,
                'constraints' => [
                    new Assert\NotBlank(),
                ],
            ])
            ->add('query5_other', TextareaType::class, [
                'required' => false,
            ])
            ->add('query6_1', ChoiceType::class, [
                'required' => true,
                'choices' => [
                    'キャンペーン内容' => 'キャンペーン内容',
                    '金額' => '金額',
                    '通信について' => '通信について',
                    '機能について' => '機能について',
                    '設置方法' => '設置方法',
                    '介護保険について' => '介護保険について',
                    '特になし' => '特になし',
                    'その他' => 'その他',
                ],
                'expanded' => true,
                'multiple' => true,
                'constraints' => [
                    new Assert\NotBlank(),
                ],
            ])
            ->add('query6_1_other', TextareaType::class, [
                'required' => false,
            ])
            ->add('query6_2', ChoiceType::class, [
                'required' => true,
                'choices' => [
                    'キャンペーン内容' => 'キャンペーン内容',
                    '金額' => '金額',
                    '通信について' => '通信について',
                    '機能について' => '機能について',
                    '設置方法' => '設置方法',
                    '介護保険について' => '介護保険について',
                    '特になし' => '特になし',
                    'その他' => 'その他',
                ],
                'expanded' => true,
                'multiple' => true,
                'constraints' => [
                    new Assert\NotBlank(),
                ],
            ])
            ->add('query6_2_other', TextareaType::class, [
                'required' => false,
            ])
            ->add('query7', ChoiceType::class, [
                'required' => true,
                'choices' => [
                    '設置場所にインターネットがいらないから' => '設置場所にインターネットがいらないから',
                    '簡単そうだったから' => '簡単そうだったから',
                    '特定の動きをお知らせできるから(お知らせ機能)' => '特定の動きをお知らせできるから(お知らせ機能)',
                    '呼びかけできるから' => '呼びかけできるから',
                    '現地の音が聞けるから' => '現地の音が聞けるから',
                    '録画機能' => '録画機能',
                    'お試し体験ができるから' => 'お試し体験ができるから',
                    'その他' => 'その他',
                ],
                'expanded' => true,
                'multiple' => true,
                'constraints' => [
                    new Assert\NotBlank(),
                ],
            ])
            ->add('query7_other', TextareaType::class, [
                'required' => false,
            ])
            ->add('query8', ChoiceType::class, [
                'required' => true,
                'choices' => [
                    'みまもり' => 'みまもり',
                    '通知機能' => '通知機能',
                    '録画確認' => '録画確認',
                    '呼びかけ' => '呼びかけ',
                    '集音' => '集音',
                    'その他' => 'その他',
                ],
                'expanded' => true,
                'multiple' => true,
                'constraints' => [
                    new Assert\NotBlank(),
                ],
            ])
            ->add('query8_other', TextareaType::class, [
                'required' => false,
            ])
            ->add('query9', ChoiceType::class, [
                'required' => true,
                'choices' => [
                    '利用している' => '利用している',
                    '利用していない' => '利用していない',
                ],
                'expanded' => true,
                'multiple' => false,
                'constraints' => [
                    new Assert\NotBlank(),
                ],
            ])
            ->add('query9_other', ChoiceType::class, [
                'required' => false,
                'choices' => [
                    'LINE' => 'LINE',
                    'Facebook' => 'Facebook',
                    'Instagram' => 'Instagram',
                    'Twitter' => 'Twitter',
                ],
                'expanded' => true,
                'multiple' => true,
            ])
            ->add('query10', ChoiceType::class, [
                'required' => true,
                'choices' => [
                    '北海道' => '北海道',
                    '青森県' => '青森県',
                    '岩手県' => '岩手県',
                    '宮城県' => '宮城県',
                    '秋田県' => '秋田県',
                    '山形県' => '山形県',
                    '福島県' => '福島県',
                    '茨城県' => '茨城県',
                    '栃木県' => '栃木県',
                    '群馬県' => '群馬県',
                    '埼玉県' => '埼玉県',
                    '千葉県' => '千葉県',
                    '東京都' => '東京都',
                    '神奈川県' => '神奈川県',
                    '新潟県' => '新潟県',
                    '富山県' => '富山県',
                    '石川県' => '石川県',
                    '福井県' => '福井県',
                    '山梨県' => '山梨県',
                    '長野県' => '長野県',
                    '岐阜県' => '岐阜県',
                    '静岡県' => '静岡県',
                    '愛知県' => '愛知県',
                    '三重県' => '三重県',
                    '滋賀県' => '滋賀県',
                    '京都府' => '京都府',
                    '大阪府' => '大阪府',
                    '兵庫県' => '兵庫県',
                    '奈良県' => '奈良県',
                    '和歌山県' => '和歌山県',
                    '鳥取県' => '鳥取県',
                    '島根県' => '島根県',
                    '岡山県' => '岡山県',
                    '広島県' => '広島県',
                    '山口県' => '山口県',
                    '徳島県' => '徳島県',
                    '香川県' => '香川県',
                    '愛媛県' => '愛媛県',
                    '高知県' => '高知県',
                    '福岡県' => '福岡県',
                    '佐賀県' => '佐賀県',
                    '長崎県' => '長崎県',
                    '熊本県' => '熊本県',
                    '大分県' => '大分県',
                    '宮崎県' => '宮崎県',
                    '鹿児島県' => '鹿児島県',
                    '沖縄県' => '沖縄県',
                ],
                'constraints' => [
                    new Assert\NotBlank(),
                ],
            ])
            ->add('query11', ChoiceType::class, [
                'required' => true,
                'choices' => [
                    '高くない' => '高くない',
                    '普通' => '普通',
                    '高い' => '高い',
                ],
                'expanded' => true,
                'multiple' => false,
                'constraints' => [
                    new Assert\NotBlank(),
                ],
            ])
            ->add('query12', ChoiceType::class, [
                'required' => false,
                'choices' => [
                    '電話でお問合せをした' => '電話でお問合せをした',
                    'メールでお問合せをした' => 'メールでお問合せをした',
                    'ご本人の方以外がお問合せをした' => 'ご本人の方以外がお問合せをした',
                    'お問合せしていない' => 'お問合せしていない',
                    'その他' => 'その他',
                ],
                'expanded' => true,
                'multiple' => true,
            ])
            ->add('query12_other', TextareaType::class, [
                'required' => false,
            ])
            ->add('query13', TextareaType::class, [
                'required' => false,
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'enquete';
    }
}
