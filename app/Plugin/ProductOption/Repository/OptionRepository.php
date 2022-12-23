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
use Plugin\ProductOption\Entity\Option;
use Symfony\Bridge\Doctrine\RegistryInterface;

class OptionRepository extends AbstractRepository
{

    public function __construct(RegistryInterface $registry, string $entityClass = Option::class)
    {
        parent::__construct($registry, $entityClass);
    }

    public function getList()
    {
        $qb = $this->createQueryBuilder('o')
                ->orderBy('o.sort_no', 'DESC');
        $Options = $qb->getQuery()
                ->getResult();

        return $Options;
    }

    public function getIds()
    {
        $qb = $this->createQueryBuilder('o')
                ->select('o.id')
                ->orderBy('o.sort_no', 'DESC');
        $results = $qb->getQuery()
                ->getResult();

        $Ids = [];
        foreach($results as $result){
            $Ids[] = $result['id'];
        }
        return $Ids;
    }

    public function save($Option)
    {
        $em = $this->getEntityManager();
        try {
            if (!$Option->getId()) {
                $sort_no = $this->createQueryBuilder('o')
                        ->select('MAX(o.sort_no)')
                        ->getQuery()
                        ->getSingleScalarResult();
                if (!$sort_no) {
                    $sort_no = 0;
                }
                $Option->setSortNo($sort_no + 1);
            }

            if($Option->getType() == Option::CHECKBOX_TYPE){
                $Option->setIsRequired(false);
            }elseif($Option->getType() == Option::NUMBER_TYPE){
            }else{
                $Option->setRequireMin(null);
                $Option->setRequireMax(null);
            }

            $em->persist($Option);
            $em->flush($Option);

        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    public function delete($Option)
    {
        $em = $this->getEntityManager();
        try {
            $ProductOptions = $em->createQueryBuilder()
                                 ->from('Plugin\ProductOption\Entity\ProductOption','po')
                                 ->select('po')
                                 ->where('po.Option = :Option')
                                 ->setParameter('Option',$Option)
                                 ->getQuery()
                                 ->getResult();

            if (count($ProductOptions) > 0) {
                foreach($ProductOptions as $ProductOption){
                    $em->getRepository('Plugin\ProductOption\Entity\ProductOption')
                                ->delete($ProductOption);
                }
            }

            $sort_no = $Option->getSortNo();
            $em->createQueryBuilder()
                    ->update('Plugin\ProductOption\Entity\Option', 'o')
                    ->set('o.sort_no', 'o.sort_no - 1')
                    ->where('o.sort_no > :sort_no')->setParameter('sort_no', $sort_no)
                    ->getQuery()
                    ->execute();

            $em->remove($Option);
            $em->flush();
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

}
