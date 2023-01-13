<?php

/*
 * Copyright(c) 2020 Shadow Enterprise, Inc. All rights reserved.
 * http://www.shadow-ep.co.jp/
 */

namespace Plugin\SeEnquete4;

use Eccube\Common\EccubeNav;

class SeEnquete4Nav implements EccubeNav
{
    /**
     * @return array
     */
    public static function getNav()
    {
        return [
            'se_enquete' => [
                'name' => 'se_enquete.admin.nav.enquete_management',
                'icon' => 'fa-list',
                'children' => [
                    'se_enquete_admin_manage_index' => [
                        'name' => 'se_enquete.admin.nav.manage_list',
                        'url' => 'se_enquete_admin_manage_index',
                    ],
                    'se_enquete_admin_manage_new' => [
                        'name' => 'se_enquete.admin.nav.manage_new',
                        'url' => 'se_enquete_admin_manage_new',
                    ],
                ],
            ],
        ];
    }
}
