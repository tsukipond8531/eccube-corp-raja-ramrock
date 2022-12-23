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

use Plugin\ProductOption\Entity\ProductOptionConfig;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class ConfigType extends AbstractType
{

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $arrRange[ProductOptionConfig::BY_ALL] = trans("productoption.admin.config.label.range.all");
        $arrRange[ProductOptionConfig::BY_SHIPPING] = trans("productoption.admin.config.label.range.shipping");

        $builder
            ->add(ProductOptionConfig::RANGE_NAME, Type\ChoiceType::class, [
                'label' => 'productoption.admin.config.label.range',
                'required' => true,
                'expanded' => true,
                'multiple' => false,
                'choices'  => array_flip($arrRange),
            ])
        ;

    }
}
