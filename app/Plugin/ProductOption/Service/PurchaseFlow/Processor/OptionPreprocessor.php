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
use Eccube\Annotation\ShoppingFlow;
use Eccube\Entity\ItemInterface;
use Eccube\Entity\OrderItem;
use Eccube\Entity\Plugin;
use Eccube\Service\PurchaseFlow\ItemPreprocessor;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Eccube\Service\CartService;
use Eccube\Service\TaxRuleService;
use Plugin\ProductOption\Entity\Option;
use Plugin\ProductOption\Entity\OptionCategory;
use Plugin\ProductOption\Entity\OrderItemOption;
use Plugin\ProductOption\Entity\OrderItemOptionCategory;
use Plugin\ProductOption\Util\CommonUtil;

/**
 * @CartFlow
 * @ShoppingFlow
 */
class OptionPreprocessor implements ItemPreprocessor
{
    private $entityManager;
    private $cartService;
    private $taxRuleService;

    public function __construct(
            EntityManagerInterface $entityManager,
            CartService $cartService,
            TaxRuleService $taxRuleService
            )
    {
        $this->entityManager = $entityManager;
        $this->cartService = $cartService;
        $this->taxRuleService = $taxRuleService;
    }

    /**
     * @param ItemInterface $item
     * @param PurchaseContext $context
     */
    public function process(ItemInterface $item, PurchaseContext $context)
    {
        if (!$item->isProduct()) {
            return;
        }

        $Plugin = $this->entityManager->getRepository(Plugin::class)->findOneBy(['code' => 'CustomerRank']);
        if ($item instanceof OrderItem) {
            if(strlen($item->getOptionSerial()) == 0){
                $optionRepository = $this->entityManager->getRepository(Option::class);
                $optionCategoryRepository = $this->entityManager->getRepository(OptionCategory::class);
                foreach($this->cartService->getCarts() as $Cart){
                    $CartItems = $Cart->getCartItems();
                    foreach($CartItems as $CartItem){
                        $ProductClass1 = $item->getProductClass();
                        $ProductClass2 = $CartItem->getProductClass();
                        $product_class_id1 = $ProductClass1 ? (string) $ProductClass1->getId() : null;
                        $product_class_id2 = $ProductClass2 ? (string) $ProductClass2->getId() : null;
                        if ($product_class_id1 === $product_class_id2 && $item->getQuantity() === $CartItem->getQuantity()) {
                            foreach($item->getOrder()->getProductOrderItems() as $orderItem){
                                if(strlen($orderItem->getOptionSerial()) == 0)continue;
                                $ProductClass = $orderItem->getProductClass();
                                $product_class_id = $ProductClass ? (string) $ProductClass->getId() : null;
                                if($product_class_id1 === $product_class_id && CommonUtil::compareArray(unserialize($orderItem->getOptionSerial()),unserialize($CartItem->getOptionSerial()))){
                                    $item->setOptionSerial($CartItem->getOptionSerial());
                                    continue 2;
                                }
                            }
                            $optionPrice = 0;
                            $optionTax = 0;
                            $sortNo = 0;
                            if(!is_array($CartItem->getArrOption()))continue;
                            foreach($CartItem->getArrOption() as $optionId => $values){
                                if(!is_array($values))$values = [$values];
                                $Option = $optionRepository->find($optionId);
                                if(is_null($Option))continue;
                                $OrderItemOption = new OrderItemOption();
                                $OrderItemOption->setLabel($Option->getName())
                                                ->setOptionId($Option->getId())
                                                ->setOrderItem($item)
                                                ->setSortNo($sortNo++);
                                $categorySortNo = 0;
                                foreach($values as $value){
                                    if(
                                        $Option->getType() == Option::SELECT_TYPE
                                        || $Option->getType() == Option::RADIO_TYPE
                                        || $Option->getType() == Option::CHECKBOX_TYPE
                                      ){
                                        $OptionCategory = $optionCategoryRepository->find($value);
                                        if(is_null($OptionCategory))continue;
                                        $value = $OptionCategory->getName();
                                    }else{
                                        $OptionCategory = null;
                                        $OptionCategories = $Option->getOptionCategories();
                                        if(count($OptionCategories) > 0)$OptionCategory = $OptionCategories[0];
                                    }
                                    $OrderItemOptionCategory = new OrderItemOptionCategory();
                                    $OrderItemOptionCategory
                                                            ->setOrderItemOption($OrderItemOption)
                                                            ->setSortNo($categorySortNo++)
                                                            ->setValue($value);
                                    if(!is_null($OptionCategory)){
                                        $option_price = $OptionCategory->getValue();
                                        if($Option->getType() == Option::NUMBER_TYPE){
                                            if($OptionCategory->getMultipleFlg())$option_price *= $value;
                                        }
                                        $OrderItemOptionCategory->setDeliveryFreeFlg($OptionCategory->getDeliveryFreeFlg())
                                                                ->setOptionCategoryId($OptionCategory->getId())
                                                                ->setPrice($option_price)
                                                                ->setTax($this->taxRuleService->getTax($option_price,$ProductClass1->getProduct(),$ProductClass1));
                                        $optionPrice += $OrderItemOptionCategory->getPrice();
                                        $optionTax += $OrderItemOptionCategory->getTax();
                                    }
                                    $OrderItemOption->addOrderItemOptionCategory($OrderItemOptionCategory);
                                    $this->entityManager->persist($OrderItemOptionCategory);
                                }
                                if(count($OrderItemOption->getOrderItemOptionCategories()) == 0)continue;
                                $item->addOrderItemOption($OrderItemOption);
                                $this->entityManager->persist($OrderItemOption);
                            }
                            $item->setPrice($item->getPrice() + $optionPrice);
                            $item->setTax($item->getTax() + $optionTax);
                            $item->setOptionSerial($CartItem->getOptionSerial());
                            $item->setOptionSetFlg(true);
                            $this->entityManager->persist($item);
                            $this->entityManager->flush($item);
                            break 2;
                        }
                    }
                }
            }else{
                if(!is_null($Plugin) && $Plugin->isEnabled() && $item->getOptionSetFlg() !== true){
                    $item->setPrice($item->getPrice() + $item->getOptionPrice());
                    $item->setTax($item->getTax() + $item->getOptionTax());
                    $item->setOptionSetFlg(true);
                }
            }
        }
    }
}
