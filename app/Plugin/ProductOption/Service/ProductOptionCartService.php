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

use Doctrine\ORM\EntityManagerInterface;
use Eccube\Entity\CartItem;
use Eccube\Repository\CartRepository;
use Eccube\Repository\OrderRepository;
use Eccube\Repository\ProductClassRepository;
use Eccube\Service\Cart\CartItemAllocator;
use Eccube\Service\Cart\CartItemComparator;
use Eccube\Service\CartService;
use Eccube\Service\TaxRuleService;
use Plugin\ProductOption\Entity\Option;
use Plugin\ProductOption\Entity\OptionCategory;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class ProductOptionCartService extends CartService
{
    protected $taxRuleService;

    public function __construct(
        SessionInterface $session,
        EntityManagerInterface $entityManager,
        ProductClassRepository $productClassRepository,
        CartRepository $cartRepository,
        CartItemComparator $cartItemComparator,
        CartItemAllocator $cartItemAllocator,
        OrderRepository $orderRepository,
        TokenStorageInterface $tokenStorage,
        AuthorizationCheckerInterface $authorizationChecker,
        TaxRuleService $taxRuleService
    ) {
        $this->session = $session;
        $this->entityManager = $entityManager;
        $this->productClassRepository = $productClassRepository;
        $this->cartRepository = $cartRepository;
        $this->cartItemComparator = $cartItemComparator;
        $this->cartItemAllocator = $cartItemAllocator;
        $this->orderRepository = $orderRepository;
        $this->tokenStorage = $tokenStorage;
        $this->authorizationChecker = $authorizationChecker;
        $this->taxRuleService = $taxRuleService;
    }

    public function addProductOption($productClassId, $Options, $quantity = 1)
    {
        $ProductClass = $this->productClassRepository->find($productClassId);
        if(!$ProductClass)return false;
        log_info('規格ID',[$productClassId]);
        log_info('オプション',[$Options]);

        $exist_flg = false;
        $newItem = new CartItem();
        $newItem->setQuantity($quantity);
        $newItem->setPrice($ProductClass->getPrice02IncTax());
        $newItem->setProductClass($ProductClass);
        $newItem->setOptionSerial(serialize($Options));

        $allCartItems = [];
        foreach($this->getCarts() as $Cart){
            $CartItems = $Cart->getCartItems();
            foreach($CartItems as $CartItem){
                if($CartItem->getOptionSerial() == null)$CartItems->removeElement($CartItem);
            }
            $allCartItems = array_merge($allCartItems, $CartItems->toArray());
            foreach($CartItems as $cartItem){
                if($this->cartItemComparator->compare($newItem, $cartItem)){
                    $cartItem->setQuantity($cartItem->getQuantity() + $quantity);
                    $exist_flg = true;
                    break;
                }
            }
        }
        if($exist_flg){
            $this->restoreCarts($allCartItems);
            return;
        }

        $allCartItems[] = $newItem;

        $optionRepository = $this->entityManager->getRepository(Option::class);
        $optionCategoryRepository = $this->entityManager->getRepository(OptionCategory::class);

        $total_option_price = 0;
        foreach($Options as $optionId => $value){
            $Option = $optionRepository->find($optionId);
            if(!is_array($value))$value = [$value];
            foreach($value as $val){
                if(
                    $Option->getType() == Option::SELECT_TYPE
                    || $Option->getType() == Option::RADIO_TYPE
                    || $Option->getType() == Option::CHECKBOX_TYPE
                  ){
                    $OptionCategory = $optionCategoryRepository->find($val);
                }else{
                    $OptionCategory = null;
                    $OptionCategories = $Option->getOptionCategories();
                    if(count($OptionCategories) > 0)$OptionCategory = $OptionCategories[0];
                }
                if(!is_null($OptionCategory)){
                    $option_price = $OptionCategory->getValue();

                    // 設置代行オプションの台数が2台の場合、価格変更
                    if ( $OptionCategory->getOption()->getName() == '設置代行オプション' && $ProductClass->getMaintenancePack() == 2 ) {
                        $option_price = 28000;
                    }

                    if($Option->getType() == Option::NUMBER_TYPE){
                        if($OptionCategory->getMultipleFlg())$option_price *= $val;
                    }
                    $total_option_price += $option_price;
                }
            }
        }
        $price = $ProductClass->getPrice02() + $total_option_price;
        $newItem->setPrice($price + $this->taxRuleService->getTax($price,$ProductClass->getProduct(),$ProductClass));
        $this->restoreCarts($allCartItems);

        return;
    }

    public function removeCartItem($CartItem)
    {
        $allCartItems = $this->mergeAllCartItems();
        $foundIndex = -1;
        foreach ($allCartItems as $index => $itemInCart) {
            if ($this->cartItemComparator->compare($itemInCart, $CartItem)) {
                $foundIndex = $index;
                break;
            }
        }

        array_splice($allCartItems, $foundIndex, 1);
        $this->restoreCarts($allCartItems);
    }
}
