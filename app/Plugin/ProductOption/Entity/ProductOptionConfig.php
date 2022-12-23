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
 * ProductOptionConfig
 *
 * @ORM\Table(name="plg_productoption_dtb_config")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="discriminator_type", type="string", length=255)
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Entity(repositoryClass="Plugin\ProductOption\Repository\ConfigRepository")
 */
class ProductOptionConfig extends \Eccube\Entity\AbstractEntity
{
    const RANGE_NAME = 'range';

    const BY_ALL = 1;
    const BY_SHIPPING = 2;
    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", nullable=false, length=255)
     * @ORM\Id
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="value", type="string", nullable=true, length=255)
     */
    private $value;

    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    public function getName()
    {
        return $this->name;
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
}