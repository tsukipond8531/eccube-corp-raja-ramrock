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

namespace Plugin\ProductOption\Entity;

use Plugin\ProductOption\Entity\OptionCategory;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * CartItemOption
 *
 */
class CartItemOption extends \Eccube\Entity\AbstractEntity
{
    private $_calc = false;
    private $option_price = 0;
    private $option_tax = 0;
    private $delivery_free_flg = false;

    public function _calc()
    {
        if (!$this->_calc) {
            foreach ($this->getCartItemOptionCategories() as $CartItemOptionCategory) {
                $price = $CartItemOptionCategory->getPrice();
                $tax = $CartItemOptionCategory->getTax();
                if(is_numeric($price)){
                    $this->option_price += $price;
                    $this->option_tax += $tax;
                }
                if(!$this->delivery_free_flg){
                    $flg = $CartItemOptionCategory->getDeliveryFreeFlg();
                    if($flg == OptionCategory::ON){
                        $this->delivery_free_flg = true;
                    }
                }
            }
            $this->_calc = true;
        }
    }

    public function getOptionPrice()
    {
        $this->_calc();

        return $this->option_price;
    }

    public function getOptionTax()
    {
        $this->_calc();

        return $this->option_tax;
    }

    public function getOptionTotalPrice()
    {
        $this->_calc();

        return $this->option_price + $this->option_tax;
    }

    public function getDeliveryFreeFlg()
    {
        $this->_calc();

        return $this->delivery_free_flg;
    }

    private $label;

    private $Option;

    private $CartItemOptionCategories;

    public function __construct()
    {
        $this->CartItemOptionCategories = new ArrayCollection();
    }

    public function setLabel($label)
    {
        $this->label = $label;

        return $this;
    }

    public function getLabel()
    {
        return $this->label;
    }

    public function setOption($option)
    {
        $this->Option = $option;

        return $this;
    }

    public function getOption()
    {
        return $this->Option;
    }

    public function addCartItemOptionCategory(\Plugin\ProductOption\Entity\CartItemOptionCategory $cartItemOptionCategory)
    {
        $this->CartItemOptionCategories[] = $cartItemOptionCategory;

        return $this;
    }

    public function removeCartItemOptionCategory(\Plugin\ProductOption\Entity\CartItemOptionCategory $cartItemOptionCategory)
    {
        $this->CartItemOptionCategories->removeElement($cartItemOptionCategory);
    }

    public function getCartItemOptionCategories()
    {
        return $this->CartItemOptionCategories;
    }
}
