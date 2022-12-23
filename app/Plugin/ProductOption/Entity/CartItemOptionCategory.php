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

/**
 * CartItemOptionValue
 *
 */
class CartItemOptionCategory extends \Eccube\Entity\AbstractEntity
{
    private $price;

    private $tax;

    private $value;

    private $delivery_free_flg;

    private $OptionCategory;

    public function setPrice($price)
    {
        $this->price = $price;

        return $this;
    }

    public function getPrice()
    {
        return $this->price;
    }

    public function setTax($tax)
    {
        $this->tax = $tax;

        return $this;
    }

    public function getTax()
    {
        return $this->tax;
    }

    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function setDeliveryFreeFlg($flg)
    {
        $this->delivery_free_flg = $flg;

        return $this;
    }

    public function getDeliveryFreeFlg()
    {
        return $this->delivery_free_flg;
    }

    public function setOptionCategory($optionCategory)
    {
        $this->OptionCategory = $optionCategory;

        return $this;
    }

    public function getOptionCategory()
    {
        return $this->OptionCategory;
    }
}
