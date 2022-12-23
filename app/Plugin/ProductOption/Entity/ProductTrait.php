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
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @EntityExtension("Eccube\Entity\Product")
 */
trait ProductTrait
{
    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="Plugin\ProductOption\Entity\ProductOption", mappedBy="Product", cascade={"remove"})
     * @ORM\OrderBy({
     *     "sort_no"="ASC"
     * })
     */
    private $ProductOptions;

    public function addProductOption(\Plugin\ProductOption\Entity\ProductOption $productOption)
    {
        $this->ProductOptions[] = $productOption;

        return $this;
    }

    public function removeProductOption(\Plugin\ProductOption\Entity\ProductOption $productOption)
    {
        $this->ProductOptions->removeElement($productOption);
    }

    public function getProductOptions()
    {
        return $this->ProductOptions;
    }
}
