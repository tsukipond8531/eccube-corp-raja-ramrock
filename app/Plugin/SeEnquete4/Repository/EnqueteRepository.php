<?php

/*
 * Copyright(c) 2020 Shadow Enterprise, Inc. All rights reserved.
 * http://www.shadow-ep.co.jp/
 */

namespace Plugin\SeEnquete4\Repository;

use Eccube\Repository\AbstractRepository;
use Plugin\SeEnquete4\Entity\Enquete;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * EnqueteRepository
 */
class EnqueteRepository extends AbstractRepository
{

    use \Plugin\SeEnquete4\Repository\GetFindCollectionTrait;

    /**
     * EnqueteRepository constructor.
     *
     * @param RegistryInterface $registry
     */
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Enquete::class);
    }
}
