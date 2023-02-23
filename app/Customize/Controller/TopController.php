<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) EC-CUBE CO.,LTD. All Rights Reserved.
 *
 * http://www.ec-cube.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Customize\Controller;

use Eccube\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Routing\Annotation\Route;

class TopController extends AbstractController
{
    /**
     * @Route("/", name="homepage", methods={"GET"})
     * @Template("index.twig")
     */
    public function index()
    {
        require_once($_SERVER['DOCUMENT_ROOT'].'/page/wp-load.php');
        $args = array(
            'post_type' => 'post',
            'posts_per_page' => 5,  // 最新から5件取得
        );

        $wp_information = get_posts($args);
        foreach ($wp_information as $val) {
            $information[$i]['title'] = $val->post_title; // タイトル
            $information[$i]['link'] = get_permalink($val->ID); // リンク先
            $information[$i]['date'] = $val->post_date; // 投稿日
            $cat = get_the_category($val->ID); 
            $cat = $cat[0];
            $information[$i]['cate'] = get_cat_name($cat->term_id);;
            $i++;
        }


        return [
            'InformationList' => $information
        ];
    }
}
