<?php

/*
 * Copyright(c) 2020 Shadow Enterprise, Inc. All rights reserved.
 * http://www.shadow-ep.co.jp/
 */

namespace Plugin\SeEnquete4\Repository;

use Eccube\Repository\AbstractRepository;
use Eccube\Repository\CustomerRepository;
use Plugin\SeEnquete4\Entity\EnqueteUser;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * EnqueteUserRepository
 */
class EnqueteUserRepository extends AbstractRepository
{
    /**
     * @var CustomerRepository
     */
    protected $customerRepository;

    /**
     * EnqueteUserRepository constructor.
     *
     * @param RegistryInterface $registry
     * @param CustomerRepository $customerRepository
     */
    public function __construct(
        RegistryInterface $registry,
        CustomerRepository $customerRepository)
    {
        parent::__construct($registry, EnqueteUser::class);

        $this->customerRepository = $customerRepository;
    }

    /**
     * Eccube 会員情報を取得する
     *
     * @param string $customerId
     * @return object \Eccube\Entity\Customer 会員情報
     */
    public function getCustomer($customerId)
    {
        if ( empty($customerId) || !is_numeric($customerId) ) return null;

        $Customer =
            $this->customerRepository->find($customerId);
        if (is_null($Customer)) {
            return null;
        }

        return $Customer;
    }


}
