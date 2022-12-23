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

namespace Plugin\ProductOption\Service;

use Eccube\Common\EccubeConfig;
use Eccube\Entity\Shipping;
use Eccube\Repository\BaseInfoRepository;
use Eccube\Repository\OrderRepository;
use Eccube\Repository\ShippingRepository;
use Eccube\Service\TaxRuleService;
use Eccube\Twig\Extension\EccubeExtension;
use Eccube\Twig\Extension\TaxExtension;
use Eccube\Service\OrderPdfService;

class ProductOptionOrderPdfService extends OrderPdfService
{

    protected $eccubeConfig;
    protected $eccubeExtension;
    protected $taxExtension;

    public function __construct(EccubeConfig $eccubeConfig, OrderRepository $orderRepository, ShippingRepository $shippingRepository, TaxRuleService $taxRuleService, BaseInfoRepository $baseInfoRepository, EccubeExtension $eccubeExtension, TaxExtension $taxExtension = null)
    {
        $this->eccubeConfig = $eccubeConfig;
        $this->baseInfoRepository = $baseInfoRepository->get();
        $this->orderRepository = $orderRepository;
        $this->shippingRepository = $shippingRepository;
        $this->taxRuleService = $taxRuleService;
        $this->eccubeExtension = $eccubeExtension;
        $this->taxExtension = $taxExtension;
        parent::__construct($eccubeConfig,$orderRepository,$shippingRepository,$taxRuleService,$baseInfoRepository,$eccubeExtension, $taxExtension);
    }

    /**
     * 購入商品詳細情報を設定する.
     *
     * @param Order $Order
     */
    protected function renderOrderDetailData(Shipping $Shipping)
    {
        $labelCell[] = '商品名 / 商品コード / [ 規格 ] / [ オプション ]';
        $labelCell[] = '数量';
        $labelCell[] = '単価';
        $labelCell[] = '金額(税込)';
        $widthCell = [110.3, 12, 21.7, 24.5];

        $arrOrder = [];
        // テーブルの微調整を行うための購入商品詳細情報をarrayに変換する

        // =========================================
        // 受注詳細情報
        // =========================================
        $i = 0;
        $isShowReducedTaxMess = false;
        /* @var OrderDetail $OrderDetail */
        foreach ($Shipping->getOrderItems() as $OrderItem) {
            // class categoryの生成
            $classCategory = '';
            if ($OrderItem->getClassCategoryName1()) {
                $classCategory .= ' [ '.$OrderItem->getClassCategoryName1();
                if ($OrderItem->getClassCategoryName2() == '') {
                    $classCategory .= ' ]';
                } else {
                    $classCategory .= ' * '.$OrderItem->getClassCategoryName2().' ]';
                }
            }

            // product
            $productName = $OrderItem->getProductName();
            if (null !== $OrderItem->getProductCode()) {
                $productName .= ' / '.$OrderItem->getProductCode();
            }
            if ($classCategory) {
                $productName .= ' / '.$classCategory;
            }
            $option = '';
            if($OrderItem->isProduct()){
                foreach($OrderItem->getOrderItemOptions() as $OrderItemOption){
                    $option .= $OrderItemOption->getLabel() . ':';
                    foreach($OrderItemOption->getOrderItemOptionCategories() as $OrderItemOptionCategory){
                        $option .= $OrderItemOptionCategory->getValue() . ',';
                    }
                }
                $option =rtrim($option, ',');
                if(strlen($option) > 0){
                    $delimiter = ' / ';
                    if(strlen($classCategory) == 0)$delimiter .= ' / ';
                    $productName .= $delimiter . '[' . $option . ']';
                }
            }
            if (!is_null($this->taxExtension) && $this->taxExtension->isReducedTaxRate($OrderItem)) {
                $productName .= ' ※';
                $isShowReducedTaxMess = true;
            }
            $arrOrder[$i][0] = $productName;

            // 購入数量
            $arrOrder[$i][1] = number_format($OrderItem->getQuantity());
            // 税込金額（単価）
            $arrOrder[$i][2] = $this->eccubeExtension->getPriceFilter($OrderItem->getPriceIncTax());
            // 小計（商品毎）
            $arrOrder[$i][3] = $this->eccubeExtension->getPriceFilter($OrderItem->getTotalPrice());

            ++$i;
        }

        $Order = $Shipping->getOrder();

        if (!$Order->isMultiple()) {
            // =========================================
            // 小計
            // =========================================
            $arrOrder[$i][0] = '';
            $arrOrder[$i][1] = '';
            $arrOrder[$i][2] = '';
            $arrOrder[$i][3] = '';

            ++$i;
            $arrOrder[$i][0] = '';
            $arrOrder[$i][1] = '';
            $arrOrder[$i][2] = '商品合計';
            $arrOrder[$i][3] = $this->eccubeExtension->getPriceFilter($Order->getSubtotal());

            ++$i;
            $arrOrder[$i][0] = '';
            $arrOrder[$i][1] = '';
            $arrOrder[$i][2] = '送料';
            $arrOrder[$i][3] = $this->eccubeExtension->getPriceFilter($Order->getDeliveryFeeTotal());

            ++$i;
            $arrOrder[$i][0] = '';
            $arrOrder[$i][1] = '';
            $arrOrder[$i][2] = '手数料';
            $arrOrder[$i][3] = $this->eccubeExtension->getPriceFilter($Order->getCharge());

            ++$i;
            $arrOrder[$i][0] = '';
            $arrOrder[$i][1] = '';
            $arrOrder[$i][2] = '値引き';
            $arrOrder[$i][3] = '- '.$this->eccubeExtension->getPriceFilter($Order->getDiscount());

            if(method_exists($Order, 'getTaxableTotalByTaxRate')){
                ++$i;
                $arrOrder[$i][0] = '';
                $arrOrder[$i][1] = '';
                $arrOrder[$i][2] = '';
                $arrOrder[$i][3] = '';

                ++$i;
                $arrOrder[$i][0] = '';
                $arrOrder[$i][1] = '';
                $arrOrder[$i][2] = '合計';
                $arrOrder[$i][3] = $this->eccubeExtension->getPriceFilter($Order->getTaxableTotal());

                foreach ($Order->getTaxableTotalByTaxRate() as $rate => $total) {
                    ++$i;
                    $arrOrder[$i][0] = '';
                    $arrOrder[$i][1] = '';
                    $arrOrder[$i][2] = '('.$rate.'%対象)';
                    $arrOrder[$i][3] = $this->eccubeExtension->getPriceFilter($total);
                }

                ++$i;
                $arrOrder[$i][0] = '';
                $arrOrder[$i][1] = '';
                $arrOrder[$i][2] = '';
                $arrOrder[$i][3] = '';

                foreach($Order->getTaxFreeDiscountItems() as $Item) {
                    ++$i;
                    $arrOrder[$i][0] = '';
                    $arrOrder[$i][1] = '';
                    $arrOrder[$i][2] = $Item->getProductName();
                    $arrOrder[$i][3] = $this->eccubeExtension->getPriceFilter($Item->getTotalPrice());
                }
            }

            ++$i;
            $arrOrder[$i][0] = '';
            $arrOrder[$i][1] = '';
            $arrOrder[$i][2] = '請求金額';
            $arrOrder[$i][3] = $this->eccubeExtension->getPriceFilter($Order->getPaymentTotal());

            if ($isShowReducedTaxMess) {
                ++$i;
                $arrOrder[$i][0] = '※は軽減税率対象商品です。';
                $arrOrder[$i][1] = '';
                $arrOrder[$i][2] = '';
                $arrOrder[$i][3] = '';
            }
        }

        // PDFに設定する
        $this->setFancyTable($labelCell, $arrOrder, $widthCell);
    }

}
