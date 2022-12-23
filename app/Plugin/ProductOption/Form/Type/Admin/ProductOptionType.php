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

use Plugin\ProductOption\Entity\ProductOption;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductOptionType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
                ->add('checked', Type\CheckboxType::class, [
                    'label' => false,
                    'required' => false,
                    'value' => 1,
                    'mapped' => false,
                ])
                ->add('option_id', Type\HiddenType::class , [
                    'label' => false,
                    'required' => true,
                    'mapped' => false,
                ])
        ;
    }
}
