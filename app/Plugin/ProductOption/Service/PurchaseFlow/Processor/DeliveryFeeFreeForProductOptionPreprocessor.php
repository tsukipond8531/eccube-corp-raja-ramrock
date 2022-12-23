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
use Eccube\Entity\ItemHolderInterface;
use Eccube\Service\PurchaseFlow\ItemHolderPreprocessor;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Plugin\ProductOption\Entity\Option;
use Plugin\ProductOption\Entity\OptionCategory;
use Plugin\ProductOption\Repository\OptionRepository;
use Plugin\ProductOption\Repository\OptionCategoryRepository;

/**
 * @CartFlow
 */
class DeliveryFeeFreeForProductOptionPreprocessor implements ItemHolderPreprocessor
{
    private $optionRepository;
    private $optionCategoryRepository;

    public function __construct(
            OptionRepository $optionRepository,
            OptionCategoryRepository $optionCategoryRepository
            )
    {
        $this->optionRepository = $optionRepository;
        $this->optionCategoryRepository = $optionCategoryRepository;
    }

    public function process(ItemHolderInterface $itemHolder, PurchaseContext $context)
    {
        $delivery_free_flg = false;
        foreach($itemHolder->getItems() as $cartItem){
            $Options = $cartItem->getArrOption();
            if(!empty($Options)){
                foreach($Options as $optionId => $values){
                    if(!is_array($values))$values = [$values];
                    $Option = $this->optionRepository->find($optionId);
                    if(is_null($Option))continue;
                    foreach($values as $value){
                        if(
                                $Option->getType() == Option::SELECT_TYPE
                                || $Option->getType() == Option::RADIO_TYPE
                                || $Option->getType() == Option::CHECKBOX_TYPE
                                ){
                            $OptionCategory = $this->optionCategoryRepository->find($value);
                        }else{
                            $OptionCategory = null;
                            $OptionCategories = $Option->getOptionCategories();
                            if(count($OptionCategories) > 0)$OptionCategory = $OptionCategories[0];
                        }
                        if(!is_null($OptionCategory)){
                            $flg = $OptionCategory->getDeliveryFreeFlg();
                            if($flg == OptionCategory::ON){
                                $delivery_free_flg = true;
                                break 3;
                            }
                        }
                    }
                }
            }
        }

        if($delivery_free_flg){
            foreach ($itemHolder->getItems() as $Item) {
                if ($Item->isDeliveryFee()) {
                    $Item->setQuantity(0);
                }
            }
        }
    }
}
