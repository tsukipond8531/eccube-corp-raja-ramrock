<?php

/*
 * Copyright(c) 2020 Shadow Enterprise, Inc. All rights reserved.
 * http://www.shadow-ep.co.jp/
 */

namespace Plugin\SeEnquete4\Repository;

use Eccube\Repository\AbstractRepository;
use Plugin\SeEnquete4\Entity\EnqueteItem;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * EnqueteItemRepository
 */
class EnqueteItemRepository extends AbstractRepository
{
    /**
     * EnqueteItemRepository constructor.
     *
     * @param RegistryInterface $registry
     */
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, EnqueteItem::class);
    }
}
