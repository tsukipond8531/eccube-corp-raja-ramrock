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

use Eccube\Annotation\CartFlow;
use Eccube\Entity\ItemInterface;
use Eccube\Service\PurchaseFlow\ItemValidator;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Eccube\Service\OrderHelper;
use Plugin\ProductOption\Util\CommonUtil;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @CartFlow
 */
class QuantityAdjustValidator extends ItemValidator
{
    private $orderHelper;
    private $requestStack;

    public function __construct(
            OrderHelper $orderHelper,
            RequestStack $requestStack
            )
    {
        $this->orderHelper = $orderHelper;
        $this->requestStack = $requestStack;
    }

    protected function validate(ItemInterface $item, PurchaseContext $context)
    {
        if (!$item->isProduct()) {
            return;
        }
        $request = $this->requestStack->getMasterRequest();
        if($request->get('_route') !== 'shopping_shipping_multiple')return;

        $Cart = $item->getCart();
        $Order = $this->orderHelper->getPurchaseProcessingOrder($Cart->getPreOrderId());

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

        foreach($arrOrderItems as $orderItem){
            $ProductClass1 = $item->getProductClass();
            $product_class_id1 = $ProductClass1 ? (string) $ProductClass1->getId() : null;
            $product_class_id2 = $orderItem['product_class_id'];
            if ($product_class_id1 === $product_class_id2 && CommonUtil::compareArray(unserialize($item->getOptionSerial()),unserialize($orderItem['option'])) && $item->getQuantity() != $orderItem['quantity']) {
                $item->setQuantity($orderItem['quantity']);
            }
        }
    }

    protected function handle(ItemInterface $item, PurchaseContext $context)
    {

    }
}
