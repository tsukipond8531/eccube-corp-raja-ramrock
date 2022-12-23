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

use Doctrine\ORM\Mapping as ORM;

/**
 * OrderOptionItem
 *
 * @ORM\Table(name="plg_productoption_dtb_order_item_option_category")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="discriminator_type", type="string", length=255)
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Entity(repositoryClass="Plugin\ProductOption\Repository\OrderItemOptionCategoryRepository")
 */
class OrderItemOptionCategory extends \Eccube\Entity\AbstractEntity
{

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", options={"unsigned":true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string|null
     *
     * @ORM\Column(name="price", type="decimal", precision=12, scale=2, nullable=true)
     */
    private $price;

    /**
     * @var string|null
     *
     * @ORM\Column(name="tax", type="decimal", precision=12, scale=2, nullable=true)
     */
    private $tax;

    /**
     * @var string|null
     *
     * @ORM\Column(name="value", type="string", length=4000, nullable=true)
     */
    private $value;

    /**
     * @var int|null
     *
     * @ORM\Column(name="delivery_free_flg", type="smallint", nullable=true)
     */
    private $delivery_free_flg;

    /**
     * @var int
     *
     * @ORM\Column(name="sort_no", type="integer")
     */
    private $sort_no;

    /**
     * @var int|null
     *
     * @ORM\Column(name="option_category_id", type="integer", nullable=true)
     */
    private $option_category_id;

    /**
     * @var \Plugin\ProductOption\Entity\OrderItemOption
     *
     * @ORM\ManyToOne(targetEntity="Plugin\ProductOption\Entity\OrderItemOption", inversedBy="OrderItemOptionCategories")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="order_item_option_id", referencedColumnName="id")
     * })
     */
    private $OrderItemOption;

    public function getId()
    {
        return $this->id;
    }

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

    public function setSortNo($sortNo)
    {
        $this->sort_no = $sortNo;

        return $this;
    }

    public function getSortNo()
    {
        return $this->sort_no;
    }

    public function setOptionCategoryId($optionCategoryId)
    {
        $this->option_category_id = $optionCategoryId;

        return $this;
    }

    public function getOptionCategoryId()
    {
        return $this->option_category_id;
    }

    public function setOrderItemOption($orderItemOption)
    {
        $this->OrderItemOption = $orderItemOption;

        return $this;
    }

    public function getOrderItemOption()
    {
        return $this->OrderItemOption;
    }
}
