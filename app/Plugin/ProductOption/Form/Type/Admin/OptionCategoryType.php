<?php
/*
 * Plugin Name : ProductOption
 *
 * Copyright (C) BraTech Co., Ltd. All Rights Reserved.
 * http://www.bratech.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\ProductOption\Form\Type\Admin;

use Eccube\Common\EccubeConfig;
use Eccube\Form\Type\PriceType;
use Plugin\ProductOption\Entity\OptionCategory;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class OptionCategoryType extends AbstractType
{
    protected $eccubeConfig;

    public function __construct(
        EccubeConfig $eccubeConfig
    ) {
        $this->eccubeConfig = $eccubeConfig;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $arrSelect[OptionCategory::OFF] = trans("productoption.option.category.delivery_free.off");
        $arrSelect[OptionCategory::ON] = trans("productoption.option.category.delivery_free.on");

        $builder
                // 基本情報
                ->add('name', Type\TextType::class, [
                    'label' => trans("productoption.admin.product.option.category.name"),
                    'constraints' => [
                        new Assert\NotBlank(),
                    ],
                ])
                ->add('description', Type\TextareaType::class, [
                    'label' => trans("productoption.admin.product.option.category.description"),
                    'required' => false,
                ])
                ->add('option_image', Type\FileType::class, [
                    'label' => trans("productoption.admin.product.option.category.image"),
                    'multiple' => true,
                    'required' => false,
                    'mapped' => false,
                ])
                ->add('disable_flg', Type\CheckboxType::class, [
                    'label' => trans("productoption.admin.product.option.category.disable_flg"),
                    'required' => false,
                ])
                ->add('init_flg', Type\CheckboxType::class, [
                    'label' => trans("productoption.admin.product.option.category.init_flg"),
                    'required' => false,
                ])
                // 画像
                ->add('images', Type\CollectionType::class, [
                    'entry_type' => Type\HiddenType::class,
                    'prototype' => true,
                    'mapped' => false,
                    'allow_add' => true,
                    'allow_delete' => true,
                ])
                ->add('add_images', Type\CollectionType::class, [
                    'entry_type' => Type\HiddenType::class,
                    'prototype' => true,
                    'mapped' => false,
                    'allow_add' => true,
                    'allow_delete' => true,
                ])
                ->add('delete_images', Type\CollectionType::class, [
                    'entry_type' => Type\HiddenType::class,
                    'prototype' => true,
                    'mapped' => false,
                    'allow_add' => true,
                    'allow_delete' => true,
                ])
                ->add('value', PriceType::class, [
                    'label' => trans("productoption.admin.product.option.category.price"),
                    'required' => false,
                    'accept_minus' => true,
                ])
                ->add('delivery_free_flg', Type\ChoiceType::class, [
                    'label' => trans("productoption.admin.product.option.category.delivery_free_flg"),
                    'choices' => array_flip($arrSelect),
                ])
                ->add('return_link', Type\HiddenType::class, [
                    'mapped' => false,
                ])
        ;

        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            /** @var FormInterface $form */
            $form = $event->getForm();
            $saveImgDir = $this->eccubeConfig['eccube_save_image_dir'];
            $tempImgDir = $this->eccubeConfig['eccube_temp_image_dir'];
            $this->validateFilePath($form->get('delete_images'), [$saveImgDir, $tempImgDir]);
            $this->validateFilePath($form->get('add_images'), [$tempImgDir]);
        });
    }

    /**
    * 指定された複数ディレクトリのうち、いずれかのディレクトリ以下にファイルが存在するかを確認。
    *
    * @param $form FormInterface
    * @param $dirs array
    */
    private function validateFilePath($form, $dirs)
    {
        foreach ($form->getData() as $fileName) {
            $fileInDir = array_filter($dirs, function ($dir) use ($fileName) {
                $filePath = realpath($dir.'/'.$fileName);
                $topDirPath = realpath($dir);
                return strpos($filePath, $topDirPath) === 0 && $filePath !== $topDirPath;
            });
            if (!$fileInDir) {
                $form->getRoot()['option_image']->addError(new FormError(trans('productoption.type.image.path.error')));
            }
        }
    }
}
