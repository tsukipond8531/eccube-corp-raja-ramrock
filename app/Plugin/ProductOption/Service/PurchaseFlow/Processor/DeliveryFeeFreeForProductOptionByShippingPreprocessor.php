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

namespace Plugin\ProductOption\Service\PurchaseFlow\Processor;

use Eccube\Annotation\ShoppingFlow;
use Eccube\Entity\ItemHolderInterface;
use Eccube\Entity\Order;
use Eccube\Service\PurchaseFlow\ItemHolderPreprocessor;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Plugin\ProductOption\Entity\ProductOptionConfig;
use Plugin\ProductOption\Repository\ConfigRepository;

/**
 * @ShoppingFlow
 */
class DeliveryFeeFreeForProductOptionByShippingPreprocessor implements ItemHolderPreprocessor
{
    private $configRepository;

    public function __construct(
            ConfigRepository $configRepository
            )
    {
        $this->configRepository = $configRepository;
    }

    public function process(ItemHolderInterface $itemHolder, PurchaseContext $context)
    {
        if(!$itemHolder instanceof Order){
            return;
        }

        $Config = $this->configRepository->findOneBy(['name' => ProductOptionConfig::RANGE_NAME]);
        $flg = false;
        if(!is_null($Config)){
            if($Config->getValue() == ProductOptionConfig::BY_ALL)$flg = true;
        }

        $order_flg = false;
        foreach ($itemHolder->getShippings() as $Shipping) {
            $shipping_flg = false;
            foreach ($Shipping->getOrderItems() as $Item) {
                $OrderItemOptions = $Item->getOrderItemOptions();
                foreach($OrderItemOptions as $OrderItemOption){
                    $shipping_flg = $OrderItemOption->getDeliveryFreeFlg();
                    if($shipping_flg){
                        if($flg)$order_flg = true;
                        break 2;
                    }
                }
            }
            if($shipping_flg){
                foreach ($Shipping->getOrderItems() as $Item) {
                    if ($Item->isDeliveryFee()) {
                        $Item->setQuantity(0);
                    }
                }
            }
        }

        if($order_flg){
            foreach ($itemHolder->getShippings() as $Shipping) {
                foreach ($Shipping->getOrderItems() as $Item) {
                    if ($Item->isDeliveryFee()) {
                        $Item->setQuantity(0);
                    }
                }
            }
        }
    }
}
