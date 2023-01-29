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
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Eccube\Service\OrderHelper;

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
     * カート画面.
     *
     * @Route("/cart", name="cart", methods={"GET"})
     * @Template("Cart/index.twig")
     */
    public function index(Request $request)
    {
        // カートを取得して明細の正規化を実行
        $Carts = $this->cartService->getCarts();
        $this->execPurchaseFlow($Carts);

        // TODO itemHolderから取得できるように
        $least = [];
        $quantity = [];
        $isDeliveryFree = [];
        $totalPrice = 0;
        $totalQuantity = 0;

        foreach ($Carts as $Cart) {
            $quantity[$Cart->getCartKey()] = 0;
            $isDeliveryFree[$Cart->getCartKey()] = false;

            if ($this->baseInfo->getDeliveryFreeQuantity()) {
                if ($this->baseInfo->getDeliveryFreeQuantity() > $Cart->getQuantity()) {
                    $quantity[$Cart->getCartKey()] = $this->baseInfo->getDeliveryFreeQuantity() - $Cart->getQuantity();
                } else {
                    $isDeliveryFree[$Cart->getCartKey()] = true;
                }
            }

            if ($this->baseInfo->getDeliveryFreeAmount()) {
                if (!$isDeliveryFree[$Cart->getCartKey()] && $this->baseInfo->getDeliveryFreeAmount() <= $Cart->getTotalPrice()) {
                    $isDeliveryFree[$Cart->getCartKey()] = true;
                } else {
                    $least[$Cart->getCartKey()] = $this->baseInfo->getDeliveryFreeAmount() - $Cart->getTotalPrice();
                }
            }

            $totalPrice += $Cart->getTotalPrice();
            $totalQuantity += $Cart->getQuantity();
        }

        // カートが分割された時のセッション情報を削除
        $request->getSession()->remove(OrderHelper::SESSION_CART_DIVIDE_FLAG);
        $this->session->remove('shopping_redirect_params');
        $this->session->remove('is_eaccount');

        return [
            'totalPrice' => $totalPrice,
            'totalQuantity' => $totalQuantity,
            // 空のカートを削除し取得し直す
            'Carts' => $this->cartService->getCarts(true),
            'least' => $least,
            'quantity' => $quantity,
            'is_delivery_free' => $isDeliveryFree,
        ];
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
