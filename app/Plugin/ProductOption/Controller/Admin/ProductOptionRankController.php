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

namespace Plugin\ProductOption\Controller\Admin;

use Plugin\ProductOption\Repository\ProductOptionRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class ProductOptionRankController extends \Eccube\Controller\AbstractController
{
    /**
     * @var ProductRepository
     */
    private $productRepository;

    /**
     * @var ProductOptionRepository
     */
    private $productOptionRepository;

    /**
     * OptionController constructor.
     * @param ProductRepository $productRepository
     * @param OptionRepository $optionRepository
     * @param ProductOptionRepository $productOptionRepository
     */
    public function __construct(\Eccube\Repository\ProductRepository $productRepository, ProductOptionRepository $productOptionRepository)
    {
        $this->productRepository = $productRepository;
        $this->productOptionRepository = $productOptionRepository;
    }

    /**
     * @Route("/%eccube_admin_route%/product/product/option/rank/{id}", requirements={"id" = "\d+"} , name="admin_product_product_option_rank")
     * @Template("@ProductOption/admin/Product/product_option_rank.twig")
     */
    public function index(Request $request, $id)
    {

        $Product = $this->productRepository->find($id);

        if (!$Product) {
            throw new NotFoundHttpException();
        }

        $ProductOptions = $Product->getProductOptions();

        return [
                    'Product' => $Product,
                    'ProductOptions' => $ProductOptions,
        ];
    }

    /**
     * @Route("/%eccube_admin_route%/product/product_option/sort_no/move" , name="admin_product_product_option_sort_no_move",methods={"POST"})
     */
    public function moveSortNo(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            $sortNos = $request->request->all();
            foreach ($sortNos as $productOptionId => $sortNo) {
                $ProductOption = $this->productOptionRepository
                        ->find($productOptionId);
                $ProductOption->setSortNo($sortNo);
                $this->entityManager->persist($ProductOption);
            }
            $this->entityManager->flush();
        }
        return new Response();
    }

}
