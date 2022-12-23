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
use Doctrine\ORM\Mapping as ORM;

/**
 * @EntityExtension("Eccube\Entity\Shipping")
 */
trait ShippingTrait
{
    private $OptionOfTemp;

    public function setOptionOfTemp($Option)
    {
        $this->OptionOfTemp = $Option;

        return $this;
    }

    public function getOptionOfTemp()
    {
        return $this->OptionOfTemp;
    }
}
