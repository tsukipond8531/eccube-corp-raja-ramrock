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

namespace Plugin\ProductOption;

use Eccube\Common\EccubeNav;

class ProductOptionNav implements EccubeNav
{
    /**
     * @return array
     */
    public static function getNav()
    {
        return [
            'product' => [
                'children' => [
                    'option' => [
                        'id' => 'admin_product_option',
                        'name' => 'productoption.admin.nav.product.option',
                        'url' => 'admin_product_option',
                    ],
                ],
            ],
            'content' => [
                'children' => [
                    'productoption' => [
                        'id' => 'admin_content_productoption',
                        'name' => 'productoption.admin.nav.content.productoption',
                        'children' => [
                            'option' => [
                                'id' => 'admin_content_productoption_option',
                                'name' => 'productoption.admin.nav.content.productoption.option',
                                'url' => 'admin_content_productoption_option',
                            ],
                            'description' => [
                                'id' => 'admin_content_productoption_description',
                                'name' => 'productoption.admin.nav.content.productoption.description',
                                'url' => 'admin_content_productoption_description',
                            ],
                            'css' => [
                                'id' => 'admin_content_productoption_css',
                                'name' => 'productoption.admin.nav.content.productoption.css',
                                'url' => 'admin_content_productoption_css',
                            ]
                        ]
                    ],
                ],
            ],
        ];
    }
}