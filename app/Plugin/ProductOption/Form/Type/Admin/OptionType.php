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

use Plugin\ProductOption\Entity\Option;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OptionType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $arrType[Option::SELECT_TYPE] = trans("productoption.option.type.select");
        $arrType[Option::RADIO_TYPE] = trans("productoption.option.type.radio");
        $arrType[Option::CHECKBOX_TYPE] = trans("productoption.option.type.checkbox");
        $arrType[Option::TEXT_TYPE] = trans("productoption.option.type.text");
        $arrType[Option::TEXTAREA_TYPE] = trans("productoption.option.type.textarea");
        $arrType[Option::DATE_TYPE] = trans("productoption.option.type.date");
        $arrType[Option::NUMBER_TYPE] = trans("productoption.option.type.number");

        $arrDisp[Option::DISP_OFF] = trans("productoption.option.disp.off");
        $arrDisp[Option::DISP_ON] = trans("productoption.option.disp.on");

        $builder
                // 基本情報
                ->add('name', Type\TextType::class, [
                    'label' => 'productoption.admin.product.option.name',
                    'constraints' => [
                        new Assert\NotBlank(),
                    ],
                ])
                ->add('backend_name', Type\TextType::class, [
                    'label' => 'productoption.admin.product.option.backend_name',
                    'constraints' => [
                        new Assert\NotBlank(),
                    ],
                ])
                ->add('description', Type\TextareaType::class, [
                    'label' => 'productoption.admin.product.option.description',
                    'required' => false,
                ])
                ->add('type', Type\ChoiceType::class, [
                    'label' => 'productoption.admin.product.option.type',
                    'choices' => array_flip($arrType),
                ])
                ->add('description_flg', Type\ChoiceType::class, [
                    'label' => 'productoption.admin.product.option.description_flg',
                    'choices' => array_flip($arrDisp),
                ])
                ->add('pricedisp_flg', Type\CheckboxType::class, [
                    'label' => 'productoption.admin.product.option.pricedisp_flg',
                    'required' => false,
                ])
                ->add('is_required', Type\CheckboxType::class, [
                    'label' => 'productoption.admin.product.option.is_required',
                    'required' => false,
                ])
                ->add('require_min', Type\IntegerType::class, [
                    'label' => 'productoption.admin.product.option.require_min',
                    'required' => false,
                ])
                ->add('require_max', Type\IntegerType::class, [
                    'label' => 'productoption.admin.product.option.require_max',
                    'required' => false,
                ])
        ;

        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            $form = $event->getForm();

            $min = $form['require_min']->getData();
            $max = $form['require_max']->getData();
            if(strlen($min) > 0 && strlen($max) > 0 && $min > $max){
                $form['require_min']->addError(new FormError('productoption.type.range.invalid'));
                $form['require_max']->addError(new FormError('productoption.type.range.invalid'));
            }

            if($form['type']->getData() == Option::CHECKBOX_TYPE){
                if(strlen($min) > 0 && $min < 0){
                    $form['require_min']->addError(new FormError('productoption.type.minus.invalid'));
                }
                if(strlen($max) > 0 && $max < 0){
                    $form['require_max']->addError(new FormError('productoption.type.minus.invalid'));
                }
            }
        });
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'Plugin\ProductOption\Entity\Option',
        ]);
    }
}
