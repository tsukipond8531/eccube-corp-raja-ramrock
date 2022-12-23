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

namespace Plugin\ProductOption\Twig\Extension;

use Eccube\Entity\Product;
use Eccube\Entity\ProductClass;
use Eccube\Service\TaxRuleService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class PriceIncTaxExtension extends AbstractExtension
{
    private $taxRuleService;

    public function __construct(TaxRuleService $taxRuleService)
    {
        $this->taxRuleService = $taxRuleService;
    }

    /**
     * Returns a list of functions to add to the existing list.
     *
     * @return TwigFunction[] An array of functions
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('priceIncTax', [$this, 'priceIncTax']),
        ];
    }

    public function priceIncTax($price, Product $Product = null, ProductClass $ProductClass = null)
    {
        return $this->taxRuleService->getPriceIncTax($price,$Product, $ProductClass);
    }
}
