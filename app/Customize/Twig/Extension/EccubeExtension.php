<?php
namespace Customize\Twig\Extension;

use Eccube\Common\EccubeConfig;
use Eccube\Entity\Master\ProductStatus;
use Eccube\Entity\Product;
use Eccube\Entity\ProductClass;
use Eccube\Repository\ProductRepository;
use Eccube\Util\StringUtil;
use Symfony\Component\Form\FormView;
use Symfony\Component\Intl\Intl;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Eccube\Twig\Extension\EccubeExtension as BaseEccubeExtension;

class EccubeExtension extends BaseEccubeExtension
{
    /**
     * EccubeExtension constructor.
     *
     * @param EccubeConfig $eccubeConfig
     * @param ProductRepository $productRepository
     */
    public function __construct(EccubeConfig $eccubeConfig, ProductRepository $productRepository)
    {
        parent::__construct($eccubeConfig, $productRepository);
    }

    /**
     * Get the ClassCategories as JSON.
     *
     * @param Product $Product
     *
     * @return string
     */
    public function getClassCategoriesAsJson(Product $Product)
    {
        $Product->_calc();
        $class_categories = [
            '__unselected' => [
                '__unselected' => [
                    'name' => trans('common.select'),
                    'product_class_id' => '',
                ],
            ],
        ];
        foreach ($Product->getProductClasses() as $ProductClass) {
            /** @var ProductClass $ProductClass */
            if (!$ProductClass->isVisible()) {
                continue;
            }
            /* @var $ProductClass \Eccube\Entity\ProductClass */
            $ClassCategory1 = $ProductClass->getClassCategory1();
            $ClassCategory2 = $ProductClass->getClassCategory2();
            if ($ClassCategory2 && !$ClassCategory2->isVisible()) {
                continue;
            }
            $class_category_id1 = $ClassCategory1 ? (string) $ClassCategory1->getId() : '__unselected2';
            $class_category_id2 = $ClassCategory2 ? (string) $ClassCategory2->getId() : '';
            $class_category_name2 = $ClassCategory2 ? $ClassCategory2->getName().($ProductClass->getStockFind() ? '' : trans('front.product.out_of_stock_label')) : '';

            $class_categories[$class_category_id1]['#'] = [
                'classcategory_id2' => '',
                'name' => trans('common.select'),
                'product_class_id' => '',
            ];
            $class_categories[$class_category_id1]['#'.$class_category_id2] = [
                'classcategory_id2' => $class_category_id2,
                'name' => $class_category_name2,
                'stock_find' => $ProductClass->getStockFind(),
                'price01' => $ProductClass->getPrice01() === null ? '' : number_format($ProductClass->getPrice01()),
                'price02' => number_format($ProductClass->getPrice02()),
                'default_price' => number_format($ProductClass->getDefaultPrice()),
                'price01_inc_tax' => $ProductClass->getPrice01() === null ? '' : number_format($ProductClass->getPrice01IncTax()),
                'price02_inc_tax' => number_format($ProductClass->getPrice02IncTax()),
                'default_price_inc_tax' => number_format($ProductClass->getDefaultPriceIncTax()),
                'price01_with_currency' => $ProductClass->getPrice01() === null ? '' : $this->getPriceFilter($ProductClass->getPrice01()),
                'price02_with_currency' => $this->getPriceFilter($ProductClass->getPrice02()),
                'default_price_with_currency' => $this->getPriceFilter($ProductClass->getDefaultPrice()),
                'price01_inc_tax_with_currency' => $ProductClass->getPrice01() === null ? '' : $this->getPriceFilter($ProductClass->getPrice01IncTax()),
                'price02_inc_tax_with_currency' => $this->getPriceFilter($ProductClass->getPrice02IncTax()),
                'default_price_inc_tax_with_currency' => $this->getPriceFilter($ProductClass->getDefaultPriceIncTax()),
                'initial_breakdown' => $ProductClass->getInitialBreakdown(),
                'monthly_breakdown' => $ProductClass->getMonthlyBreakdown(),
                'product_class_id' => (string) $ProductClass->getId(),
                'product_code' => $ProductClass->getCode() === null ? '' : $ProductClass->getCode(),
                'sale_type' => (string) $ProductClass->getSaleType()->getId(),
            ];
        }

        return json_encode($class_categories);
    }
}