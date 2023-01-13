<?php

/*
 * Copyright(c) 2020 Shadow Enterprise, Inc. All rights reserved.
 * http://www.shadow-ep.co.jp/
 */

namespace Plugin\SeEnquete4\Repository;

use Plugin\SeEnquete4\Util\CommonUtil;

trait GetFindCollectionTrait
{

    /*
     * 検索対象のレコードを取得
     */
    public function getFindCollection( $where_list=[], $order=[], $limit=0, $offset=0, $to_camel_case=true, $ucfirst=true )
    {

        if ( empty($where_list) ) return false;

        $replace_operator = [ 'eq' => '=', 'ne' => '! =', 'gt' => '>', 'lt' => '<', 'ge' => '>=', 'le' => '<=' ];

        $qb = $this->createQueryBuilder('c');

        $isWhere = 'where';    // change addWhere if used
        foreach( $where_list as $key => $value ) {
            if ( $key == 'or' ) {
                $addExpr = $addParams = [];
                $orx = $qb->expr()->orX();
                foreach ( $value as $v2key => $v2value ) {
                    $camelKey = ( $to_camel_case ) ? CommonUtil::changeStrToCamelCase($v2key, $ucfirst) : $v2key ;
                    if ( is_array($v2value) ) {
                        $connector = ( isset($replace_operator[$v2value[0]]) ) ? $v2value[0] : '' ;
                        if ( $v2value[0] == 'like' ) {
                            $connector = $v2value[0] ;
                            //$addExpr[] = $qb->expr()->{$connector}('c.'.$camelKey, ':' .$v2key );
                            $orx->add( $qb->expr()->{$connector}('c.'.$camelKey, ':' .$v2key ) );
                            $addParams[ $v2key ] = '%' .$v2value[1] .'%';
                        } else 
                        if ( !empty($connector) ) {
                            //$addExpr[] = $qb->expr()->{$connector}('c.'.$camelKey, ':' .$v2key );
                            $orx->add( $qb->expr()->{$connector}('c.'.$camelKey, ':' .$v2key ) );
                            $addParams[ $v2key ] = $v2value[1];
                        }
                    } else {
                        $connector = ( isset($replace_operator[$v2key]) ) ? $replace_operator[$v2value[0]] : $v2value[0] ;
                        //$addExpr[] = $qb->expr()->eq('c.'.$camelKey, ':' .$v2key );
                        $orx->add( $qb->expr()->eq('c.'.$camelKey, ':' .$v2key ) );
                        $addParams[ $v2key ] = $v2value;
                    }
                }

                if ( $addParams ) {
                    $qb->{$isWhere}($orx);
                    foreach ( $addParams as $pkey => $pvalue) {
                        $qb->setParameter( $pkey, $pvalue );
                    }
                }
            } else {
                $camelKey = ( $to_camel_case ) ? CommonUtil::changeStrToCamelCase($key, $ucfirst) : $key ;
                if ( is_array($value) ) {
                    $connector = ( isset($replace_operator[$value[0]]) ) ? $replace_operator[$value[0]] : $value[0] ;
                    if ( $connector == 'like' ) {
                        $qb->{$isWhere}( $qb->expr()->like('c.'.$camelKey, ':' .$key ) )
                            ->setParameter( $key, '%' .$value[1] .'%' );
                            //->setParameter( $key, $qb->expr()->literal('%' .$value[1] .'%') );    // literalをかますと%が文字列としてエスケープされる？

                        // こっちでも動くが、上を推奨ぽい
                        //$qb->{$isWhere}( 'c.'.$camelKey .' ' .$connector .' :' .$key )
                        //    ->setParameter( $key, '%' .$value[1] .'%' );
                    } else {
                        $qb->{$isWhere}( 'c.'.$camelKey .' ' .$connector .' :' .$key )
                            ->setParameter( $key, $value[1] );
                    }
                } else {
                    $qb->{$isWhere}( 'c.'.$camelKey .' = :' .$key )
                        ->setParameter( $key, $value );
                }
            }
            $isWhere = 'andWhere';
        }

        if ( $order ) {
            if ( is_array($order) ) {
                $isOrder = 'orderBy';   // change addOrderBy if used
                foreach ( $order as $key => $value ) {
                    $camelKey = ( $to_camel_case ) ? CommonUtil::changeStrToCamelCase($key, $ucfirst) : $key ;
                    $qb->{$isOrder}('c.'.$camelKey, strtoupper($value));
                    $isOrder = 'addOrderBy';
                }
            } else {
                $camelKey = ( $to_camel_case ) ? CommonUtil::changeStrToCamelCase($order, $ucfirst) : $key ;
                $qb->orderBy('c.'.$camelKey, strtoupper('ASC'));
            }
        }
        if ( $limit ) {
            $qb->setMaxResults($limit);
        }
        if ( $offset ) {
            $qb->setFirstResult($offset);
        }

        //var_dump( $qb->getQuery()->getSQL() );
        //var_dump( $qb->getQuery()->getParameters() );
        //exit();

        //return ( $limit == 1 ) ? $qb->getQuery()->getSingleResult() : $qb->getQuery()->getResult() ;
        return $qb->getQuery()->getResult();

    }


}
