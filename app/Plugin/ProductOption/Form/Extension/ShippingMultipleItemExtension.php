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

namespace Plugin\ProductOption\Form\Extension;

use Eccube\Form\Type\ShippingMultipleItemType;
use Plugin\ProductOption\Util\CommonUtil;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class ShippingMultipleItemExtension extends AbstractTypeExtension
{

    public function buildForm(FormBuilderInterface $builder, array $build_options)
    {
        $builder
            ->addEventListener(FormEvents::POST_SET_DATA, function (FormEvent $event) {
                /** @var \Eccube\Entity\Shipping $data */
                $data = $event->getData();
                /** @var \Symfony\Component\Form\Form $form */
                $form = $event->getForm();

                if (is_null($data)) {
                    return;
                }

                $quantity = 0;
                // Check all shipment items
                foreach ($data->getProductOrderItems() as $OrderItem) {
                    // Check item distinct for each quantity
                    if ($data->getProductClassOfTemp()->getId() == $OrderItem->getProductClass()->getId() && CommonUtil::compareArray(unserialize($data->getOptionOfTemp()),unserialize($OrderItem->getOptionSerial()))) {
                        $quantity = $OrderItem->getQuantity();
                        break;
                    }
                }
                $form['quantity']->setData($quantity);
            });
    }


    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return ShippingMultipleItemType::class;
    }

    public static function getExtendedTypes(): iterable
    {
        return [ShippingMultipleItemType::class];
    }

}
