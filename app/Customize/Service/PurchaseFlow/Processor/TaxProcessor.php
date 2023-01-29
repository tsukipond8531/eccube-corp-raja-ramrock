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

namespace Customize\Service\PurchaseFlow\Processor;

use Doctrine\ORM\EntityManagerInterface;
use Eccube\Entity\ItemHolderInterface;
use Eccube\Entity\Master\OrderItemType;
use Eccube\Entity\Master\TaxDisplayType;
use Eccube\Entity\Master\TaxType;
use Eccube\Entity\Order;
use Eccube\Repository\TaxRuleRepository;
use Eccube\Service\PurchaseFlow\ItemHolderPreprocessor;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Eccube\Service\TaxRuleService;

use Eccube\Service\PurchaseFlow\Processor\TaxProcessor as BaseService;
use Plugin\SeEnquete4\Repository\EnqueteUserRepository;
use Plugin\SeEnquete4\Repository\EnqueteRepository;

!defined('ENQUETE_ID') && define('ENQUETE_ID', 2);
!defined('ENQUETE_DISCOUNT_PRICE') && define('ENQUETE_DISCOUNT_PRICE', 2600);

class TaxProcessor extends BaseService
{
    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * @var TaxRuleRepository
     */
    protected $taxRuleRepository;

    /**
     * @var TaxRuleService
     */
    protected $taxRuleService;

    /**
     * @var EnqueteUserRepository
     */
    protected $enqueteUserRepository;

    /**
     * @var EnqueteRepository
     */
    protected $enqueteRepository;

    /**
     * TaxProcessor constructor.
     *
     * @param TaxRuleRepository $taxRuleRepository
     * @param TaxRuleService $taxRuleService
     * @param EnqueteUserRepository $enqueteUserRepository
     * @param EnqueteRepository $enqueteRepository
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        TaxRuleRepository $taxRuleRepository,
        EnqueteUserRepository $enqueteUserRepository,
        EnqueteRepository $enqueteRepository,
        TaxRuleService $taxRuleService
    ) {
        parent::__construct($entityManager, $taxRuleRepository, $taxRuleService);
        $this->enqueteUserRepository = $enqueteUserRepository;
        $this->enqueteRepository = $enqueteRepository;
    }

    /**
     * @param ItemHolderInterface $itemHolder
     * @param PurchaseContext $context
     *
     * @throws \Doctrine\ORM\NoResultException
     */
    public function process(ItemHolderInterface $itemHolder, PurchaseContext $context)
    {
        if (!$itemHolder instanceof Order) {
            return;
        }
        $Customer = $itemHolder->getCustomer();

        foreach ($itemHolder->getOrderItems() as $item) {
            // 明細種別に応じて税区分, 税表示区分を設定する,
            $OrderItemType = $item->getOrderItemType();

            if (!$item->getTaxType()) {
                $item->setTaxType($this->getTaxType($OrderItemType));
            }
            if (!$item->getTaxDisplayType()) {
                $item->setTaxDisplayType($this->getTaxDisplayType($OrderItemType));
            }

            // 税区分: 非課税, 不課税
            if ($item->getTaxType()->getId() != TaxType::TAXATION) {
                $item->setTax(0);
                $item->setTaxRate(0);
                $item->setRoundingType(null);

                continue;
            }

            // 注文フロー内で税率が変更された場合を考慮し反映する
            // 受注管理画面内では既に登録された税率は自動で変更しない
            if ($context->isShoppingFlow() || $item->getRoundingType() === null) {
                $TaxRule = $item->getOrderItemType()->isProduct()
                    ? $this->taxRuleRepository->getByRule($item->getProduct(), $item->getProductClass())
                    : $this->taxRuleRepository->getByRule();

                $item->setTaxRate($TaxRule->getTaxRate())
                    ->setTaxAdjust($TaxRule->getTaxAdjust())
                    ->setRoundingType($TaxRule->getRoundingType());

                $Enquete = $this->enqueteRepository->find(ENQUETE_ID);
                $EnqueteUser = $this->enqueteUserRepository->findBy(['Enquete' => $Enquete, 'customer_id' => $Customer->getId()]);
                if ($EnqueteUser && !$Customer->getCouponUsed() && $item->getOrderItemType()->isProduct()) {
                    $item->setPrice(ENQUETE_DISCOUNT_PRICE);
                }
            }

            // 税込表示の場合は, priceが税込金額のため割り戻す.
            if ($item->getTaxDisplayType()->getId() == TaxDisplayType::INCLUDED) {
                $tax = $this->taxRuleService->calcTaxIncluded(
                    $item->getPrice(), $item->getTaxRate(), $item->getRoundingType()->getId(),
                    $item->getTaxAdjust());
            } else {
                $tax = $this->taxRuleService->calcTax(
                    $item->getPrice(), $item->getTaxRate(), $item->getRoundingType()->getId(),
                    $item->getTaxAdjust());
            }

            $item->setTax($tax);
        }
    }
}
