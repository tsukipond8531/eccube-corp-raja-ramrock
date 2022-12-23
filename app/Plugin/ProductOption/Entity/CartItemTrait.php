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

use Eccube\Annotation\EntityExtension;
use Plugin\ProductOption\Entity\OptionCategory;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @EntityExtension("Eccube\Entity\CartItem")
 */
trait CartItemTrait
{
    private $_calc_productoption = false;
    private $option_price = 0;
    private $option_tax = 0;
    private $delivery_free_flg = false;

    public function _calc_productoption()
    {
        if (!$this->_calc_productoption) {
            if(!is_null($this->CartItemOptions)){
                foreach ($this->CartItemOptions as $CartItemOption) {
                    $price = $CartItemOption->getOptionPrice();
                    $tax = $CartItemOption->getOptionTax();
                    if(is_numeric($price)){
                        $this->option_price += $price;
                        $this->option_tax += $tax;
                    }
                    if(!$this->delivery_free_flg){
                        $flg = $CartItemOption->getDeliveryFreeFlg();
                        if($flg == OptionCategory::ON){
                            $this->delivery_free_flg = true;
                        }
                    }
                }
            }
            $this->_calc_productoption = true;
        }
    }

    public function getOptionPrice()
    {
        $this->_calc_productoption();

        return $this->option_price;
    }

    public function getOptionTax()
    {
        $this->_calc_productoption();

        return $this->option_tax;
    }

    public function getDeliveryFreeFlg()
    {
        $this->_calc_productoption();

        return $this->delivery_free_flg;
    }

    public function getArrOption()
    {
        return unserialize($this->option_serial);
    }

    /**
     * @var string|null
     *
     * @ORM\Column(name="option_serial", type="string", length=10000, nullable=true)
     */
    private $option_serial;

    private $CartItemOptions;

    public function __construct()
    {
        $this->CartItemOptions = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function setOptionSerial($serial)
    {
        $this->option_serial = $serial;

        return $this;
    }

    public function getOptionSerial()
    {
        return $this->option_serial;
    }

    public function addCartItemOption(\Plugin\ProductOption\Entity\CartItemOption $cartItemOption)
    {
        $this->CartItemOptions[] = $cartItemOption;

        return $this;
    }

    public function removeCartItemOption(\Plugin\ProductOption\Entity\CartItemOption $cartItemOption)
    {
        $this->CartItemOptions->removeElement($cartItemOption);
    }

    public function getCartItemOptions()
    {
        return $this->CartItemOptions;
    }
}
