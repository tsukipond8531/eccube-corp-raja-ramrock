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

namespace Plugin\ProductOption\Controller;

use Eccube\Controller\CartController;
use Eccube\Repository\CartItemRepository;
use Eccube\Repository\BaseInfoRepository;
use Eccube\Repository\ProductClassRepository;
use Eccube\Service\PurchaseFlow\PurchaseFlow;
use Plugin\ProductOption\Service\ProductOptionCartService;
use Symfony\Component\Routing\Annotation\Route;

class CartOperationController extends CartController
{
    protected $cartItemRepository;

    public function __construct(
        CartItemRepository $cartItemRepository,
        ProductClassRepository $productClassRepository,
        ProductOptionCartService $productOptionCartService,
        PurchaseFlow $cartPurchaseFlow,
        BaseInfoRepository $baseInfoRepository
    ) {
        $this->cartItemRepository = $cartItemRepository;
        $this->productClassRepository = $productClassRepository;
        $this->cartService = $productOptionCartService;
        $this->purchaseFlow = $cartPurchaseFlow;
        $this->baseInfo = $baseInfoRepository->get();
    }

    /**
     * カート明細の加算/減算/削除を行う.
     *
     * - 加算
     *      - 明細の個数を1増やす
     * - 減算
     *      - 明細の個数を1減らす
     *      - 個数が0になる場合は、明細を削除する
     * - 削除
     *      - 明細を削除する
     *
     * @Route(
     *     path="/productoption_cart/{operation}/{cartItemId}",
     *     name="productoption_cart_handle_item",
     *     methods={"PUT"},
     *     requirements={
     *          "operation": "up|down|remove",
     *          "cartItemId": "\d+"
     *     }
     * )
     */
    public function handleCartItem($operation, $cartItemId)
    {
        $this->isTokenValid();

        $CartItem = $this->cartItemRepository->find($cartItemId);

        if (is_null($CartItem)) {
            return $this->redirectToRoute('cart');
        }

        // 明細の増減・削除
        switch ($operation) {
            case 'up':
                $newQuantity = $CartItem->getQuantity() + 1;
                $limit = $CartItem->getProductClass()->getSaleLimit();
                if(!$CartItem->getProductClass()->isStockUnlimited()){
                    $stock = $CartItem->getProductClass()->getStock();
                }
                if (!is_null($limit) || isset($stock)) {
                    $Cart = $CartItem->getCart();
                    $quantity = 0;
                    foreach($Cart->getCartItems() as $item){
                        if($item->getProductClass()->getId() == $CartItem->getProductClass()->getId())$quantity += $CartItem->getQuantity();
                    }
                    $ProductClass = $CartItem->getProductClass();
                    $productName = $ProductClass->getProduct()->getName();
                    if ($ProductClass->hasClassCategory1()) {
                        $productName .= ' - '.$ProductClass->getClassCategory1()->getName();
                    }
                    if ($ProductClass->hasClassCategory2()) {
                        $productName .= ' - '.$ProductClass->getClassCategory2()->getName();
                    }
                    if (!is_null($limit) && $limit < $quantity) {
                        $newQuantity = $CartItem->getQuantity();
                        $this->addRequestError(trans('front.shopping.over_sale_limit', ['%product%' => $productName]));
                    }
                    if (isset($stock) && $stock < $quantity ) {
                        $newQuantity = $CartItem->getQuantity();
                        $errorMessages[] = trans('front.shopping.out_of_stock', ['%product%' => $productName]);
                    }
                }
                $CartItem->setQuantity($newQuantity);
                $this->entityManager->flush($CartItem);
                break;
            case 'down':
                $CartItem->setQuantity($CartItem->getQuantity() - 1);
                $this->entityManager->flush($CartItem);
                break;
            case 'remove':
                $this->cartService->removeCartItem($CartItem);
                break;
        }

        $Carts = $this->cartService->getCarts();
        $this->execPurchaseFlow($Carts);

        return $this->redirectToRoute('cart');
    }
}
