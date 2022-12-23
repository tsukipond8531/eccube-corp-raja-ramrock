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

namespace Plugin\ProductOption\Repository;

use Eccube\Repository\AbstractRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

class CartItemOptionRepository extends AbstractRepository
{
    public function __construct(RegistryInterface $registry, string $entityClass = \Plugin\ProductOption\Entity\CartItemOption::class)
    {
        parent::__construct($registry, $entityClass);
    }
}
