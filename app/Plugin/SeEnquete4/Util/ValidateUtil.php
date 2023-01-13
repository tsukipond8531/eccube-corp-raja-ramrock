<?php

/*
 * Copyright(c) 2020 Shadow Enterprise, Inc. All rights reserved.
 * http://www.shadow-ep.co.jp/
 */

namespace Plugin\SeEnquete4\Util;

/**
 * Validate関数クラス
 */
class ValidateUtil
{

    /**
     * return boolean
     *  空文字チェック
     **/
    public static function isNonNull( $string="" ) {

        return ( !empty(trim($string)) ) ? true : false ;

    }

    /*
     * return boolean
     *  メールアドレス
     */
    public static function isValidEmail( $string ) {

        //return ( preg_match( '/^[a-zA-Z0-9]{1}.+@[a-zA-Z0-9]{1}.*\..+$/', $string ) ) ? true : false ;
        return ( preg_match( '/^[^@]+@[a-zA-Z0-9]{1}[^@]*\.[^@]+$/', $string ) ) ? true : false ;

    }

    /*
     * return boolean
     *  カナ
     */
    public static function isValidKana( $string ) {

        return ( mb_ereg( "^[ア-ン゛゜ァ-ォャ-ョー「」、]+$", $string ) ) ? true : false ;

    }

    /*
     * return boolean
     *  電話番号
     */
    public static function isValidTel( $string ) {

        return ( preg_match('/^[0-9]{10,11}$/', $string) ) ? true : false ;

    }

    /*
     * return boolean
     *  郵便番号
     */
    public static function isValidPostalCode( $string ) {

        return ( preg_match('/^[0-9]{7}$/', $string) ) ? true : false ;

    }

    /*
     * return boolean
     *  英数字
     */
    public static function isValidAlfaNumeric( $string, $lower=false ) {

        if ( $lower ) {
            return ( preg_match('/^[0-9a-z]+$/', $string) ) ? true : false ;
        } else {
            return ( preg_match('/^[0-9a-zA-Z]+$/', $string) ) ? true : false ;
        }

    }

    /*
     * return boolean
     *  数値(float) 
     */
    public static function isValidNumeric( $string ) {

        return ( is_numeric($string) ) ? true : false ;

    }

    /*
     * return boolean
     *  数値(float) ※指定数値のみ
     */
    public static function isValidIntSpecific( int $string, $nums=[0,1] ) {

        return ( in_array($string, $nums, true) ) ? true : false ;

    }

    /*
     * return boolean
     *  最小文字数
     */
    public static function isValidMinLength( $string, $length=1 ) {

        return ( mb_strlen($string) >= $length ) ? true : false ;

    }

    /*
     * return boolean
     *  最大文字数
     */
    public static function isValidMaxLength( $string, $length=1 ) {

        return ( mb_strlen($string) <= $length ) ? true : false ;

    }

    /*
     * return boolean
     *  日付
     */
    public static function isValidDateTime( $string, $format='Y-m-d' ) {

        date_default_timezone_set('Asia/Tokyo');

        $date_obj = \DateTime::createFromFormat($format, $string);

        $date_obj->setTimeZone(new \DateTimeZone('Asia/Tokyo'));

        return ( $string == $date_obj->format($format) ) ? true : false ;

    }

}
