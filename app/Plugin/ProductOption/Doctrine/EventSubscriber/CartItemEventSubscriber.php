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

namespace Plugin\ProductOption\Doctrine\EventSubscriber;

use Doctrine\Common\EventSubscriber;
use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;
use Eccube\Entity\CartItem;
use Eccube\Service\TaxRuleService;
use Plugin\ProductOption\Entity\Option;
use Plugin\ProductOption\Entity\OptionCategory;
use Plugin\ProductOption\Entity\CartItemOption;
use Plugin\ProductOption\Entity\CartItemOptionCategory;

class CartItemEventSubscriber implements EventSubscriber
{

    public function __construct(
            TaxRuleService $taxRuleService
            )
    {
        $this->taxRuleService = $taxRuleService;
    }

    public function getSubscribedEvents()
    {
        return [Events::postLoad];
    }

    public function postLoad(LifecycleEventArgs $args)
    {
        $entity = $args->getObject();
        $entityManager = $args->getObjectManager();
        if ($entity instanceof CartItem) {
            $CartItem = $entity;
            $Cart = $CartItem->getCart();
            $ProductClass = $CartItem->getProductClass();
            $Options = $CartItem->getArrOption();
            if(!empty($Options)){
                foreach($Options as $optionId => $values){
                    if(!is_array($values))$values = [$values];
                    $Option = $entityManager->getRepository(Option::class)->find($optionId);
                    if(is_null($Option)){
                        $Cart->removeItem($CartItem);
                        $entityManager->remove($CartItem);
                        continue;
                    }
                    $CartItemOption = new CartItemOption();
                    $CartItemOption->setLabel($Option->getName())
                            ->setOption($Option);
                    foreach($values as $value){
                        if(
                                $Option->getType() == Option::SELECT_TYPE
                                || $Option->getType() == Option::RADIO_TYPE
                                || $Option->getType() == Option::CHECKBOX_TYPE
                                ){
                            $OptionCategory = $entityManager->getRepository(OptionCategory::class)->find($value);
                            if(is_null($OptionCategory))continue;
                            $value = $OptionCategory->getName();
                        }else{
                                $OptionCategory = null;
                                $OptionCategories = $Option->getOptionCategories();
                                if(count($OptionCategories) > 0)$OptionCategory = $OptionCategories[0];
                        }
                        $CartItemOptionCategory = new CartItemOptionCategory();
                        $CartItemOptionCategory
                                            ->setValue($value);
                        if(!is_null($OptionCategory)){
                            $option_price = $OptionCategory->getValue();
                            if($Option->getType() == Option::NUMBER_TYPE){
                                if($OptionCategory->getMultipleFlg())$option_price *= $value;
                            }
                            $CartItemOptionCategory->setDeliveryFreeFlg($OptionCategory->getDeliveryFreeFlg())
                                                ->setOptionCategory($OptionCategory)
                                                ->setPrice($option_price)
                                                ->setTax($this->taxRuleService->getTax($option_price,$ProductClass->getProduct(),$ProductClass));
                        }
                        $CartItemOption->addCartItemOptionCategory($CartItemOptionCategory);
                    }
                    if(count($CartItemOption->getCartItemOptionCategories()) == 0){
                        $Cart->removeItem($CartItem);
                        $entityManager->remove($CartItem);
                        continue;
                    }
                    $CartItem->addCartItemOption($CartItemOption);
                }
            }
        }
    }
}