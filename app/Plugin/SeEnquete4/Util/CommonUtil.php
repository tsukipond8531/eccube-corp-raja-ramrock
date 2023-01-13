<?php

/*
 * Copyright(c) 2020 Shadow Enterprise, Inc. All rights reserved.
 * http://www.shadow-ep.co.jp/
 */

namespace Plugin\SeEnquete4\Util;

/**
 * 汎用関数クラス
 */
class CommonUtil
{
    /**
     * プラグインコード定数
     */
    const PLUGIN_CODE = "SeEnquete4";

    public static function &getInstance()
    {
        static $CommonUtil;

        if (empty($CommonUtil)) {
            $CommonUtil = new CommonUtil();
        }

        return $CommonUtil;
    }

    /**
     * ログ出力（エラー）
     *
     * @param mixed $msg
     * @param array $masks 配列キーを指定してマスク
     */
    public static function logError($msg, $masks = ["Pass", "Token"])
    {
        $text = $msg;
        if (is_array($msg)) {
            $text = print_r(CommonUtil::arrayMaskValue($msg, $masks), true);
        } elseif (is_object($msg)) {
            $text = get_class($msg);
        }
        logs(CommonUtil::PLUGIN_CODE)->error($text);
    }

    /**
     * ログ出力（情報）
     *
     * @param mixed $msg
     * @param array $masks 配列キーを指定してマスク
     */
    public static function logInfo($msg, $masks = ["Pass", "Token"])
    {
        $text = $msg;
        if (is_array($msg)) {
            $text = print_r(CommonUtil::arrayMaskValue($msg, $masks), true);
        } elseif (is_object($msg)) {
            $text = get_class($msg);
        }
        logs(CommonUtil::PLUGIN_CODE)->info($text);
    }

    /**
     * ログ出力（デバッグ）
     *
     * @param mixed $msg
     * @param array $masks 配列キーを指定してマスク
     */
    public static function logDebug($msg, $masks = ["Pass", "Token"])
    {
        $text = $msg;
        if (is_array($msg)) {
            $text = print_r(CommonUtil::arrayMaskValue($msg, $masks), true);
        } elseif (is_object($msg)) {
            $text = get_class($msg);
        }
        logs(CommonUtil::PLUGIN_CODE)->debug($text);
    }

    /**
     * 配列にある値を変換する
     **/
    public static function replaceTrans($array=[])
    {

        if ( empty($array) || !is_array($array) ) return [] ;

        foreach ( $array as $key => $value ) {
            $array[$key] = trans($value);
        }

        return $array;

    }

    /*
     * スネークケースへ変更
     *   ex) FooBar -> foo_bar
     */
    public static function changeStrToSnakeCase( $str ) // {{{
    {
    
        $before = $str;
        $after  = strtolower( preg_replace("/([A-Z])/u", "_$0", $before) );
        $after  = substr( $after, 1 );
        
        return $after;
    
    } // }}}
    
    /*
     * キャメルケースへ変更
     *   ex) foo_bar -> FooBar
     * 先頭は小文字にする場合は第２パラメータを false へ変更する
     */
    public static function changeStrToCamelCase( $str, $ucf=true ) // {{{
    {
    
        $before = $str;
        $parts  = explode("_", $before );       //アンダーバーで配列に分割
        
        foreach( $parts as $part ){
            if( !isset( $after ) ){
                $after = ( $ucf ) ? ucfirst($part) : $part ;
            }else{
                $after .= ucfirst( $part );
            }
        }
        
        return $after;
    
    } // }}}

}
