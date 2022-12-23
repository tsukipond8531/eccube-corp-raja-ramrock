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

use Doctrine\ORM\EntityManagerInterface;
use Eccube\Annotation\CartFlow;
use Eccube\Entity\ItemHolderInterface;
use Eccube\Entity\Cart;
use Eccube\Service\PurchaseFlow\ItemHolderPreprocessor;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Eccube\Service\OrderHelper;
use Plugin\ProductOption\Util\CommonUtil;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @CartFlow
 */
class QuantityUpdatePreprocessor implements ItemHolderPreprocessor
{
    private $entityManager;
    private $orderHelper;
    private $requestStack;

    public function __construct(
            EntityManagerInterface $entityManager,
            OrderHelper $orderHelper,
            RequestStack $requestStack
            )
    {
        $this->entityManager = $entityManager;
        $this->orderHelper = $orderHelper;
        $this->requestStack = $requestStack;
    }

    public function process(ItemHolderInterface $itemHolder, PurchaseContext $context)
    {
        if (!$itemHolder instanceof Cart) {
            return;
        }
        $request = $this->requestStack->getMasterRequest();
        if(!strpos($request->get('_route'),'cart'))return;
        $Cart = $itemHolder;
        $Order = $this->orderHelper->getPurchaseProcessingOrder($Cart->getPreOrderId());

        if(is_null($Order)){
            return;
        }

        $arrOrderItems = [];
        foreach($Order->getProductOrderItems() as $OrderItem){
            $insert_flg = true;
            $ProductClass1 = $OrderItem->getProductClass();
            $product_class_id1 = $ProductClass1 ? (string) $ProductClass1->getId() : null;
            foreach($arrOrderItems as $key => $arrayItem){
                $product_class_id2 = $arrayItem['product_class_id'];
                if ($product_class_id1 === $product_class_id2 && CommonUtil::compareArray(unserialize($OrderItem->getOptionSerial()),unserialize($arrayItem['option']))) {
                    $arrOrderItems[$key]['quantity'] += $OrderItem->getQuantity();
                    $arrOrderItems[$key]['obj'][] = $OrderItem;
                    $insert_flg = false;
                    break;
                }
            }

            if($insert_flg){
                $insertItem = [];
                $insertItem['quantity'] = $OrderItem->getQuantity();
                $insertItem['option'] = $OrderItem->getOptionSerial();
                $insertItem['product_class_id'] = $product_class_id1;
                $insertItem['obj'][] = $OrderItem;
                $arrOrderItems[] = $insertItem;
            }
        }
        $CartItems = $Cart->getCartItems();
        foreach($CartItems as $CartItem){
            $ProductClass1 = $CartItem->getProductClass();
            $product_class_id1 = $ProductClass1 ? (string) $ProductClass1->getId() : null;
            foreach($arrOrderItems as $item){
                $product_class_id2 = $item['product_class_id'];
                if ($product_class_id1 === $product_class_id2 && CommonUtil::compareArray(unserialize($item['option']),unserialize($CartItem->getOptionSerial())) && $item['quantity'] != $CartItem->getQuantity()) {
                    $cart_quantity = $CartItem->getQuantity();
                    $diff_quantity = $cart_quantity - $item['quantity'];
                    foreach($item['obj'] as $orderItem){
                        if($diff_quantity == 0)break;
                        $org_quanitity = (int)$orderItem->getQuantity();
                        $orderItem->setQuantity($org_quanitity + $diff_quantity);
                        $diff_quantity = 0;
                        if($orderItem->getQuantity() <= 0){
                            $diff_quantity = (int)$orderItem->getQuantity() * -1;
                            $Shipping = $orderItem->getShipping();
                            $Shipping->removeOrderItem($orderItem);
                            $Order->removeOrderItem($orderItem);
                            $this->entityManager->remove($orderItem);
                            if(count($Shipping->getProductOrderItems()) == 0){
                                foreach($Shipping->getOrderItems() as $shippingItem){
                                    $Shipping->removeOrderItem($shippingItem);
                                    $Order->removeOrderItem($shippingItem);
                                    $this->entityManager->remove($shippingItem);
                                }
                                $Order->removeShipping($Shipping);
                                $this->entityManager->remove($Shipping);
                            }
                        }
                    }
                }
            }
        }
        $this->entityManager->flush();
    }
}
