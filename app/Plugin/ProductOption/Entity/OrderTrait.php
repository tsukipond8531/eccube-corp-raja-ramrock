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

namespace Plugin\ProductOption\Entity;

use Eccube\Annotation\EntityExtension;
use Eccube\Entity\OrderItem;
use Plugin\ProductOption\Entity\OptionCategory;
use Plugin\ProductOption\Entity\OrderItemOption;
use Plugin\ProductOption\Entity\OrderItemOptionCategory;
use Doctrine\ORM\Mapping as ORM;

/**
 * @EntityExtension("Eccube\Entity\Order")
 */
trait OrderTrait
{
    /**
     * 同じ規格の商品の個数をまとめた受注明細を取得
     *
     * @return OrderItem[]
     */
    public function getMergedProductOptionOrderItems()
    {

        $ProductOrderItems = $this->getProductOrderItems();
        $orderItemArray = [];
        /** @var OrderItem $ProductOrderItem */
        foreach ($ProductOrderItems as $ProductOrderItem) {
            $productClassId = $ProductOrderItem->getProductClass()->getId();
            $serial = $ProductOrderItem->getOptionSerial();
            $key = $productClassId .'_'. $serial;
            if (array_key_exists($key, $orderItemArray)) {
                /** @var ItemInterface $OrderItem */
                $OrderItem = $orderItemArray[$key];
                $quantity = $OrderItem->getQuantity() + $ProductOrderItem->getQuantity();
                $OrderItem->setQuantity($quantity);
            }else{
                // 新規規格の商品は新しく追加する
                $OrderItem = new OrderItem();
                $OrderItem->copyProperties($ProductOrderItem, ['id','OrderItemOptions']);
                foreach($ProductOrderItem->getOrderItemOptions() as $OrderItemOption){
                    $newOrderItemOption = new OrderItemOption();
                    $newOrderItemOption->setOptionId($OrderItemOption->getOptionId())
                                       ->setOrderItem($OrderItem)
                                       ->setLabel($OrderItemOption->getLabel())
                                       ->setSortNo($OrderItemOption->getSortNo());
                    foreach($OrderItemOption->getOrderItemOptionCategories() as $OrderItemOptionCategory){
                        $newOrderItemOptionCategory = new OrderItemOptionCategory();
                        $newOrderItemOptionCategory->setOptionCategoryId($OrderItemOptionCategory->getOptionCategoryId())
                                                   ->setOrderItemOption($newOrderItemOption)
                                                   ->setValue($OrderItemOptionCategory->getValue())
                                                   ->setPrice($OrderItemOptionCategory->getPrice())
                                                   ->setTax($OrderItemOptionCategory->getTax())
                                                   ->setDeliveryFreeFlg($OrderItemOptionCategory->getDeliveryFreeFlg())
                                                   ->setSortNo($OrderItemOptionCategory->getSortNo());
                        $newOrderItemOption->addOrderItemOptionCategory($newOrderItemOptionCategory);
                    }
                    $OrderItem->addOrderItemOption($newOrderItemOption);
                }
                $orderItemArray[$key] = $OrderItem;
            }
        }

        return array_values($orderItemArray);
    }
}
