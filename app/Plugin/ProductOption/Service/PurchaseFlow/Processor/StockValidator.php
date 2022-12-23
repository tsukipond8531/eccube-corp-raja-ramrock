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
use Eccube\Annotation\ShoppingFlow;
use Eccube\Entity\ItemInterface;
use Eccube\Entity\CartItem;
use Eccube\Entity\OrderItem;
use Eccube\Service\PurchaseFlow\ItemValidator;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @CartFlow
 * @ShoppingFlow
 */
class StockValidator extends ItemValidator
{
    private $requestStack;

    public function __construct(
            RequestStack $requestStack
            )
    {
        $this->requestStack = $requestStack;
    }

    protected function validate(ItemInterface $item, PurchaseContext $context)
    {
        $request = $this->requestStack->getMasterRequest();
        if($item instanceof CartItem && $request->get('_route') == 'shopping_shipping_multiple')return;
        if (!$item->isProduct()) {
            return;
        }

        if ($item->getProductClass()->isStockUnlimited()) {
            return;
        }
        $stock = $item->getProductClass()->getStock();
        $quantity = 0;
        if($item instanceof CartItem){
            foreach($item->getCart()->getCartItems() as $CartItem){
                if($item->getProductClass()->getId() == $CartItem->getProductClass()->getId())$quantity += $CartItem->getQuantity();
            }
        }
        if($item instanceof OrderItem){
            foreach($item->getOrder()->getProductOrderItems() as $OrderItem){
                if($item->getProductClass()->getId() == $OrderItem->getProductClass()->getId())$quantity += $OrderItem->getQuantity();
            }
        }
        if ($stock < $quantity) {
            $this->throwInvalidItemException('front.shopping.out_of_stock', $item->getProductClass());
        }
    }

    protected function handle(ItemInterface $item, PurchaseContext $context)
    {
        $stock = $item->getProductClass()->getStock();
        $quantity = 0;
        if($item instanceof CartItem){
            foreach($item->getCart()->getCartItems() as $CartItem){
                if($CartItem->getId() === $item->getId())continue;
                if($item->getProductClass()->getId() == $CartItem->getProductClass()->getId())$quantity += $CartItem->getQuantity();
            }
        }
        if($item instanceof OrderItem){
            foreach($item->getOrder()->getProductOrderItems() as $OrderItem){
                if($OrderItem->getId() === $item->getId())continue;
                if($item->getProductClass()->getId() == $OrderItem->getProductClass()->getId())$quantity += $OrderItem->getQuantity();
            }
        }
        $item->setQuantity($stock - $quantity);
    }
}
