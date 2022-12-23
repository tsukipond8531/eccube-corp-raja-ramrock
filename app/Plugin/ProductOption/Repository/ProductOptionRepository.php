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

class ProductOptionRepository extends AbstractRepository
{
    public function __construct(RegistryInterface $registry, string $entityClass = \Plugin\ProductOption\Entity\ProductOption::class)
    {
        parent::__construct($registry, $entityClass);
    }

    public function getListByProduct($Product)
    {
        $qb = $this->createQueryBuilder('po')
                ->where('po.Product = :product')
                ->setParameter('product', $Product)
                ->orderBy('po.sort_no','ASC');

        return $qb->getQuery()
                        ->getResult();
    }

    public function save($ProductOption)
    {
        $em = $this->getEntityManager();
        try {
            if (!$ProductOption->getId()) {
                $sort_no = $this->createQueryBuilder('po')
                        ->select('MAX(po.sort_no)')
                        ->where('po.Product = :product')
                        ->setParameter('product', $ProductOption->getProduct())
                        ->getQuery()
                        ->getSingleScalarResult();
                if (!$sort_no) {
                    $sort_no = 0;
                }
                $ProductOption->setSortNo($sort_no + 1);
            }

            $em->persist($ProductOption);
            $em->flush($ProductOption);
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    public function delete($ProductOption)
    {
        $em = $this->getEntityManager();
        try {
            $sort_no = $ProductOption->getSortNo();
            $Product = $ProductOption->getProduct();

            $em->createQueryBuilder()
                    ->update('Plugin\ProductOption\Entity\ProductOption', 'po')
                    ->set('po.sort_no', 'po.sort_no - 1')
                    ->where('po.sort_no > :sort_no AND po.Product = :Product')
                    ->setParameter('sort_no', $sort_no)
                    ->setParameter('Product', $Product)
                    ->getQuery()
                    ->execute();
            $em->remove($ProductOption);
            $em->flush();
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    public function isExist($Product, $Option)
    {
        $em = $this->getEntityManager();
        try {
            //
            $ProductOption = $this->findOneBy(['Product' => $Product, 'Option' => $Option]);
            if (!$ProductOption) {
                throw new \Exception();
            }
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

}
