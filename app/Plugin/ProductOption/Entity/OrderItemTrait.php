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
 * @EntityExtension("Eccube\Entity\OrderItem")
 */
trait OrderItemTrait
{

    private $_calc_productoption = false;
    private $option_price = 0;
    private $option_tax = 0;
    private $delivery_free_flg = false;
    private $current_price = 0;
    private $current_tax = 0;

    public function _calc_productoption()
    {
        if (!$this->_calc_productoption) {
            foreach ($this->getOrderItemOptions() as $OrderItemOption) {
                $price = $OrderItemOption->getOptionPrice();
                $tax = $OrderItemOption->getOptionTax();
                if(is_numeric($price)){
                    $this->option_price += $price;
                    $this->option_tax += $tax;
                }
                if(!$this->delivery_free_flg){
                    $flg = $OrderItemOption->getDeliveryFreeFlg();
                    if($flg == OptionCategory::ON){
                        $this->delivery_free_flg = true;
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

    public function setCurrentPrice($tax)
    {
        $this->current_price = $tax;

        return $this;
    }

    public function getCurrentPrice()
    {
        return $this->current_price;
    }

    public function setCurrentTax($tax)
    {
        $this->current_tax = $tax;

        return $this;
    }

    public function getCurrentTax()
    {
        return $this->current_tax;
    }

    public function getCurrentPriceIncTax()
    {
        return $this->current_price + $this->current_tax;
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
     * @var boolean|null
     *
     * @ORM\Column(name="option_set_flg", type="boolean", nullable=true)
     */
    private $option_set_flg;

    /**
     * @var string|null
     *
     * @ORM\Column(name="option_serial", type="string", length=10000, nullable=true)
     */
    private $option_serial;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="Plugin\ProductOption\Entity\OrderItemOption", mappedBy="OrderItem", cascade={"persist","remove"})
     * @ORM\OrderBy({
     *     "sort_no"="ASC"
     * })
     */
    private $OrderItemOptions;

    public function __construct()
    {
        $this->OrderItemOptions = new ArrayCollection();
    }

    public function setOptionSetFlg($flg)
    {
        $this->option_set_flg = $flg;

        return $this;
    }

    public function getOptionSetFlg()
    {
        return $this->option_set_flg;
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

    public function addOrderItemOption(\Plugin\ProductOption\Entity\OrderItemOption $orderItemOption)
    {
        $this->OrderItemOptions[] = $orderItemOption;

        return $this;
    }

    public function removeOrderItemOption(\Plugin\ProductOption\Entity\OrderItemOption $orderItemOption)
    {
        $this->OrderItemOptions->removeElement($orderItemOption);
    }

    public function getOrderItemOptions()
    {
        return $this->OrderItemOptions;
    }



}
