<?php

/*
 * Copyright(c) 2020 Shadow Enterprise, Inc. All rights reserved.
 * http://www.shadow-ep.co.jp/
 */

namespace Plugin\SeEnquete4\Repository;

use Eccube\Repository\AbstractRepository;
use Plugin\SeEnquete4\Entity\EnqueteMeta;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * EnqueteMetaRepository
 */
class EnqueteMetaRepository extends AbstractRepository
{
    /**
     * EnqueteMetaRepository constructor.
     *
     * @param RegistryInterface $registry
     */
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, EnqueteMeta::class);
    }
}
