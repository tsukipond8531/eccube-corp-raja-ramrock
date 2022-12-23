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

use Eccube\Controller\AbstractController;
use Eccube\Event\EccubeEvents;
use Eccube\Event\EventArgs;
use Eccube\Exception\CartException;
use Eccube\Repository\OrderRepository;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Eccube\Service\PurchaseFlow\PurchaseFlow;
use Plugin\ProductOption\Entity\Option;
use Plugin\ProductOption\Entity\OptionCategory;
use Plugin\ProductOption\Service\ProductOptionCartService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class ReorderController extends AbstractController
{
    protected $orderRepository;
    protected $purchaseFlow;
    protected $cartService;

    public function __construct(
        OrderRepository $orderRepository,
        PurchaseFlow $purchaseFlow,
        ProductOptionCartService $productOptionCartService
    ) {
        $this->orderRepository = $orderRepository;
        $this->purchaseFlow = $purchaseFlow;
        $this->cartService = $productOptionCartService;
    }

    /**
     * 再購入を行う.
     *
     * @Route("/mypage/order/{order_no}", name="mypage_order", methods={"PUT"})
     */
    public function order(Request $request, $order_no)
    {
        $this->isTokenValid();

        log_info('再注文開始', [$order_no]);

        $Customer = $this->getUser();

        /* @var $Order \Eccube\Entity\Order */
        $Order = $this->orderRepository->findOneBy(
            [
                'order_no' => $order_no,
                'Customer' => $Customer,
            ]
        );

        $event = new EventArgs(
            [
                'Order' => $Order,
                'Customer' => $Customer,
            ],
            $request
        );
        $this->eventDispatcher->dispatch(EccubeEvents::FRONT_MYPAGE_MYPAGE_ORDER_INITIALIZE, $event);

        if (!$Order) {
            log_info('対象の注文が見つかりません', [$order_no]);
            throw new NotFoundHttpException();
        }

        // エラーメッセージの配列
        $errorMessages = [];

        $optionRepository = $this->entityManager->getRepository(Option::class);
        $optionCategoryRepository = $this->entityManager->getRepository(OptionCategory::class);
        foreach ($Order->getOrderItems() as $OrderItem) {
            try {
                if ($OrderItem->getProduct() && $OrderItem->getProductClass()) {
                    $addCart = true;
                    $ProductClass = $OrderItem->getProductClass();
                    $limit = $ProductClass->getSaleLimit();
                    if(!$ProductClass->isStockUnlimited()){
                        $stock = $ProductClass->getStock();
                    }
                    if (!is_null($limit) || isset($stock)) {
                        $Carts = $this->cartService->getCarts();
                        $quantity = $OrderItem->getQuantity();
                        foreach($Carts as $Cart){
                            foreach($Cart->getCartItems() as $item){
                                if($item->getProductClass()->getId() == $ProductClass->getId())$quantity += $item->getQuantity();
                            }
                        }
                        $productName = $ProductClass->getProduct()->getName();
                        if ($ProductClass->hasClassCategory1()) {
                            $productName .= ' - '.$ProductClass->getClassCategory1()->getName();
                        }
                        if ($ProductClass->hasClassCategory2()) {
                            $productName .= ' - '.$ProductClass->getClassCategory2()->getName();
                        }
                        if (!is_null($limit) && $limit < $quantity) {
                            $addCart = false;
                            $errorMessages[] = trans('front.shopping.over_sale_limit', ['%product%' => $productName]);
                        }
                        if (isset($stock) && $stock < $quantity ) {
                            $addCart = false;
                            $errorMessages[] = trans('front.shopping.out_of_stock', ['%product%' => $productName]);
                        }
                    }
                    if($addCart){
                        $Options = [];
                        foreach($OrderItem->getOrderItemOptions() as $OrderItemOption){
                            $optionId = $OrderItemOption->getOptionId();
                            $Option = $optionRepository->find($optionId);
                            if(is_null($Option))continue;
                            foreach($OrderItemOption->getOrderItemOptionCategories() as $OrderItemOptionCategory){
                                if(
                                    $Option->getType() == Option::SELECT_TYPE
                                    || $Option->getType() == Option::RADIO_TYPE
                                    || $Option->getType() == Option::CHECKBOX_TYPE
                                  ){
                                    $optionCategoryId = $OrderItemOptionCategory->getOptionCategoryId();
                                    $OptionCategory = $optionCategoryRepository->find($optionCategoryId);
                                    if(is_null($OptionCategory))continue;
                                    $Options[$Option->getId()][] = $OptionCategory->getId();
                                }else{
                                    $Options[$Option->getId()][] = $OrderItemOptionCategory->getValue();
                                }
                            }
                        }


                        $this->cartService->addProductOption($OrderItem->getProductClass()->getId(), $Options , $OrderItem->getQuantity());

                        // 明細の正規化
                        $Carts = $this->cartService->getCarts();
                        foreach ($Carts as $Cart) {
                            $result = $this->purchaseFlow->validate($Cart, new PurchaseContext($Cart, $this->getUser()));
                            // 復旧不可のエラーが発生した場合は追加した明細を削除.
                            if ($result->hasError()) {
                                $this->cartService->removeProduct($OrderItem->getProductClass());
                                foreach ($result->getErrors() as $error) {
                                    $errorMessages[] = $error->getMessage();
                                }
                            }
                            foreach ($result->getWarning() as $warning) {
                                $errorMessages[] = $warning->getMessage();
                            }
                        }
                        $this->cartService->save();
                    }
                    unset($stock);
                }
            } catch (CartException $e) {
                log_info($e->getMessage(), [$order_no]);
                $this->addRequestError($e->getMessage());
            }
        }

        foreach ($errorMessages as $errorMessage) {
            $this->addRequestError($errorMessage);
        }

        $event = new EventArgs(
            [
                'Order' => $Order,
                'Customer' => $Customer,
            ],
            $request
        );
        $this->eventDispatcher->dispatch(EccubeEvents::FRONT_MYPAGE_MYPAGE_ORDER_COMPLETE, $event);

        if ($event->getResponse() !== null) {
            return $event->getResponse();
        }

        log_info('再注文完了', [$order_no]);

        return $this->redirect($this->generateUrl('cart'));
    }

}
