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
use Eccube\Entity\Product;
use Eccube\Entity\ProductClass;
use Eccube\Entity\Master\ProductStatus;
use Eccube\Event\EccubeEvents;
use Eccube\Event\EventArgs;
use Eccube\Form\Type\AddCartType;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Eccube\Service\PurchaseFlow\PurchaseFlow;
use Plugin\ProductOption\Entity\Option;
use Plugin\ProductOption\Service\ProductOptionCartService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class AddcartController extends AbstractController
{
    protected $purchaseFlow;
    protected $cartService;

    public function __construct(
        PurchaseFlow $cartPurchaseFlow,
        ProductOptionCartService $cartService
    ) {
        $this->purchaseFlow = $cartPurchaseFlow;
        $this->cartService = $cartService;
    }

    /**
     * カートに追加.
     *
     * @Route("/products/add_cart/{id}", name="product_add_cart", methods={"POST"}, requirements={"id" = "\d+"})
     */
    public function addCart(Request $request, Product $Product)
    {
        // エラーメッセージの配列
        $errorMessages = [];
        if (!$this->checkVisibility($Product)) {
            throw new NotFoundHttpException();
        }

        $builder = $this->formFactory->createNamedBuilder(
            '',
            AddCartType::class,
            null,
            [
                'product' => $Product,
                'id_add_product_id' => false,
            ]
        );

        $event = new EventArgs(
            [
                'builder' => $builder,
                'Product' => $Product,
            ],
            $request
        );
        $this->eventDispatcher->dispatch(EccubeEvents::FRONT_PRODUCT_CART_ADD_INITIALIZE, $event);

        /* @var $form \Symfony\Component\Form\FormInterface */
        $form = $builder->getForm();
        $form->handleRequest($request);

        if (!$form->isValid()) {
            foreach($form->all() as $child){
                $config = $child->getConfig();
                foreach($child->getErrors() as $error){
                    $errorMessages[] = $config->getOption('label') .':'. $error->getMessage();
                }
            }
        }

        $addCartData = $form->getData();
        $ProductClass = $this->entityManager->getRepository(ProductClass::class)->find($addCartData['product_class_id']);
        $limit = $ProductClass->getSaleLimit();
        if(!$ProductClass->isStockUnlimited()){
            $stock = $ProductClass->getStock();
        }
        if (!is_null($limit) || isset($stock)) {
            $Carts = $this->cartService->getCarts();
            $quantity = $addCartData['quantity'];
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
            if (!is_null($limit) && $limit < $quantity ) {
                $errorMessages[] = trans('front.shopping.over_sale_limit', ['%product%' => $productName]);
            }
            if (isset($stock) && $stock < $quantity ) {
                $errorMessages[] = trans('front.shopping.out_of_stock', ['%product%' => $productName]);
            }
        }

        if(count($errorMessages) == 0){
            log_info(
                'カート追加処理開始',
                [
                    'product_id' => $Product->getId(),
                    'product_class_id' => $addCartData['product_class_id'],
                    'quantity' => $addCartData['quantity'],
                ]
            );

            // カートへ追加
            $ProductOptions = $Product->getProductOptions();

            $Options = [];
            foreach($ProductOptions as $ProductOption){
                $Option = $ProductOption->getOption();
                $option_key = 'productoption'. $Option->getId();
                $value = $form->get($option_key)->getData();
                if($Option){
                    $add = true;
                    if($Option->getType() == Option::SELECT_TYPE || $Option->getType() == Option::RADIO_TYPE ){
                        if($Option->getDisableCategory()){
                            if($Option->getDisableCategory() == $value){
                                $add = false;
                            }
                        }
                        $value = $value->getId();
                        if(strlen($value) == 0)$add = false;
                    }elseif($Option->getType() == Option::TEXT_TYPE || $Option->getType() == Option::TEXTAREA_TYPE || $Option->getType() == Option::NUMBER_TYPE){
                        if(strlen($value) == 0)$add = false;
                    }elseif($Option->getType() == Option::CHECKBOX_TYPE){
                        if(count($value) == 0){
                            $add = false;
                        }else{
                            $buff = $value;
                            $value = [];
                            foreach($buff as $categoryoption){
                                $value[] = $categoryoption->getId();
                            }
                        }
                    }elseif($Option->getType() == Option::DATE_TYPE){
                        if(is_null($value))$add = false;
                    }
                    if($add){
                        if(is_array($value)){
                            $Options[$Option->getId()] = $value;
                        }elseif(is_object($value)){
                            $Options[$Option->getId()] = $value->format('Y-m-d');
                        }else{
                            $Options[$Option->getId()] = (string)$value;
                        }
                    }
                }
            }
            $campagin = true;

            if ($this->isGranted('ROLE_USER')) {
                $Customer = $this->getUser();

                if (count($Customer->getOrders())) $campagin = false;
            }

            $this->cartService->addProductOption($addCartData['product_class_id'], $Options, $addCartData['quantity'], $campagin);

            // 明細の正規化
            $Carts = $this->cartService->getCarts();
            foreach ($Carts as $Cart) {
                $result = $this->purchaseFlow->validate($Cart, new PurchaseContext($Cart, $this->getUser()));
                // 復旧不可のエラーが発生した場合は追加した明細を削除.
                if ($result->hasError()) {
                    $this->cartService->removeProduct($addCartData['product_class_id']);
                    foreach ($result->getErrors() as $error) {
                        $errorMessages[] = $error->getMessage();
                    }
                }
                foreach ($result->getWarning() as $warning) {
                    $errorMessages[] = $warning->getMessage();
                }
            }
            $this->cartService->save();

            log_info(
                'カート追加処理完了',
                [
                    'product_id' => $Product->getId(),
                    'product_class_id' => $addCartData['product_class_id'],
                    'quantity' => $addCartData['quantity'],
                ]
            );

            $event = new EventArgs(
                [
                    'form' => $form,
                    'Product' => $Product,
                ],
                $request
            );
            $this->eventDispatcher->dispatch(EccubeEvents::FRONT_PRODUCT_CART_ADD_COMPLETE, $event);
        }

        if ($event->getResponse() !== null) {
            return $event->getResponse();
        }

        if ($request->isXmlHttpRequest()) {
            // ajaxでのリクエストの場合は結果をjson形式で返す。

            // 初期化
            $done = null;
            $messages = [];

            if (empty($errorMessages)) {
                // エラーが発生していない場合
                $done = true;
                array_push($messages, trans('front.product.add_cart_complete'));
            } else {
                // エラーが発生している場合
                $done = false;
                $messages = $errorMessages;
            }

            return $this->json(['done' => $done, 'messages' => $messages]);
        } else {
            // ajax以外でのリクエストの場合はカート画面へリダイレクト
            if (empty($errorMessages)) {
                return $this->redirectToRoute('cart');
            }else{
                foreach ($errorMessages as $errorMessage) {
                    $this->addRequestError($errorMessage);
                }
                return $this->redirect($request->headers->get('referer'));
            }
        }
    }

    private function checkVisibility(Product $Product)
    {
        $is_admin = $this->session->has('_security_admin');

        // 管理ユーザの場合はステータスやオプションにかかわらず閲覧可能.
        if (!$is_admin) {
            // 在庫なし商品の非表示オプションが有効な場合.
            // if ($this->BaseInfo->isOptionNostockHidden()) {
            //     if (!$Product->getStockFind()) {
            //         return false;
            //     }
            // }
            // 公開ステータスでない商品は表示しない.
            if ($Product->getStatus()->getId() !== ProductStatus::DISPLAY_SHOW) {
                return false;
            }
        }

        return true;
    }
}
