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

namespace Plugin\ProductOption\Util;

class CommonUtil
{

    static function compareArray($array1, $array2)
    {
        if(!is_array($array1))$array1 = [];
        if(!is_array($array2))$array2 = [];
        if (empty($array1) && empty($array2)) {
            return true;
        } elseif(
            (empty($array1) && !empty($array2))
            || (!empty($array1) && empty($array2))
        ) {
            return false;
        } elseif(
            (is_array($array1) && !is_array($array2))
            || (!is_array($array1) && is_array($array2))
        ) {
            return false;
        }

        if(count($array1) !== count($array2))return false;

        foreach($array1 as $key => $value){
            if(!array_key_exists($key, $array2))return false;
        }
        foreach($array2 as $key => $value){
            if(!array_key_exists($key, $array1))return false;
        }

        foreach($array1 as $key => $value) {
            if (is_array($value)) {
                if (!isset($array2[$key])) {
                    return false;
                } elseif(!is_array($array2[$key])) {
                    return false;
                } elseif (!self::compareArray($value, $array2[$key])) {
                    return false;
                }
            } elseif($array2[$key] !== $value) {
                return false;
            }
        }

        return true;
    }
}
