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
 * ProductOption
 *
 * @ORM\Table(name="plg_productoption_dtb_product_option")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="discriminator_type", type="string", length=255)
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Entity(repositoryClass="Plugin\ProductOption\Repository\ProductOptionRepository")
 */
class ProductOption extends \Eccube\Entity\AbstractEntity
{

    private $checked = false;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", options={"unsigned":true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var int
     *
     * @ORM\Column(name="sort_no", type="integer")
     */
    private $sort_no;

    /**
     * @var \Eccube\Entity\Product
     *
     * @ORM\ManyToOne(targetEntity="Eccube\Entity\Product", inversedBy="ProductOptions")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="product_id", referencedColumnName="id")
     * })
     */
    private $Product;

    /**
     * @var \Plugin\ProductOption\Entity\Option
     *
     * @ORM\ManyToOne(targetEntity="Plugin\ProductOption\Entity\Option")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="option_id", referencedColumnName="id")
     * })
     */
    private $Option;

    public function getId()
    {
        return $this->id;
    }

    public function setChecked($checked)
    {
        $this->checked = $checked;

        return $this;
    }

    public function getChecked()
    {
        return $this->checked;
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

    public function setProduct(\Eccube\Entity\Product $product = null)
    {
        $this->Product = $product;

        return $this;
    }

    public function getProduct()
    {
        return $this->Product;
    }

    public function setOption(\Plugin\ProductOption\Entity\Option $Option = null)
    {
        $this->Option = $Option;

        return $this;
    }

    public function getOption()
    {
        return $this->Option;
    }

}
