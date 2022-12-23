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

class OptionCategoryRepository extends AbstractRepository
{

    public function __construct(RegistryInterface $registry, string $entityClass = \Plugin\ProductOption\Entity\OptionCategory::class)
    {
        parent::__construct($registry, $entityClass);
    }

    public function getList($Option = null)
    {
        $qb = $this->createQueryBuilder('oc')
                ->orderBy('oc.sort_no', 'DESC');
        if ($Option) {
            $qb->where('oc.Option = :Option')->setParameter('Option', $Option);
        }
        $OptionCategories = $qb->getQuery()
                ->getResult();

        return $OptionCategories;
    }

    public function save($OptionCategory)
    {
        $em = $this->getEntityManager();
        $Option = $OptionCategory->getOption();
        try {
            if (!$OptionCategory->getId()) {
                $sort_no = $this->createQueryBuilder('oc')
                        ->select('MAX(oc.sort_no)')
                        ->where('oc.Option = :Option')->setParameter('Option', $Option)
                        ->getQuery()
                        ->getSingleScalarResult();
                if (!$sort_no) {
                    $sort_no = 0;
                }
                $OptionCategory->setSortNo($sort_no + 1);
            }

            if($Option->getType() != Option::CHECKBOX_TYPE){
                if($OptionCategory->getInitFlg()){
                    $qb = $em->createQueryBuilder()
                            ->update('Plugin\ProductOption\Entity\OptionCategory', 'oc')
                            ->set('oc.init_flg', 'false')
                            ->where('oc.Option = :Option')
                            ->setParameter('Option', $Option);
                    if ($OptionCategory->getId()) {
                        $qb->andWhere('oc.id <> :id')
                           ->setParameter('id', $OptionCategory->getId());
                    }
                    $qb->getQuery()
                       ->execute();
                }
            }

            $em->persist($OptionCategory);
            $em->flush($OptionCategory);

        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    public function delete($OptionCategory)
    {
        $em = $this->getEntityManager();
        try {
            $sort_no = $OptionCategory->getSortNo();
            $Option = $OptionCategory->getOption();

            $em->createQueryBuilder()
                    ->update('Plugin\ProductOption\Entity\OptionCategory', 'oc')
                    ->set('oc.sort_no', 'oc.sort_no - 1')
                    ->where('oc.sort_no > :sort_no AND oc.Option = :Option')
                    ->setParameter('sort_no', $sort_no)
                    ->setParameter('Option', $Option)
                    ->getQuery()
                    ->execute();

            $em->remove($OptionCategory);
            $em->flush($OptionCategory);
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }
}
