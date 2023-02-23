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

namespace Customize\Service;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Eccube\Entity\Cart;
use Eccube\Entity\CartItem;
use Eccube\Entity\Customer;
use Eccube\Entity\Master\DeviceType;
use Eccube\Entity\Master\OrderItemType;
use Eccube\Entity\Master\OrderStatus;
use Eccube\Entity\Order;
use Eccube\Entity\OrderItem;
use Eccube\Entity\Shipping;
use Eccube\EventListener\SecurityListener;
use Eccube\Repository\DeliveryRepository;
use Eccube\Repository\Master\DeviceTypeRepository;
use Eccube\Repository\Master\OrderItemTypeRepository;
use Eccube\Repository\Master\OrderStatusRepository;
use Eccube\Repository\Master\PrefRepository;
use Eccube\Repository\OrderRepository;
use Eccube\Repository\PaymentRepository;
use Eccube\Util\StringUtil;
use SunCat\MobileDetectBundle\DeviceDetector\MobileDetector;
use Symfony\Bundle\FrameworkBundle\Controller\ControllerTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

use Eccube\Service\OrderHelper as BaseService;
use Symfony\Component\Security\Core\Security;

class OrderHelper extends BaseService
{
    private $security;
    public function __construct(
        ContainerInterface $container,
        EntityManagerInterface $entityManager,
        OrderRepository $orderRepository,
        OrderItemTypeRepository $orderItemTypeRepository,
        OrderStatusRepository $orderStatusRepository,
        DeliveryRepository $deliveryRepository,
        PaymentRepository $paymentRepository,
        DeviceTypeRepository $deviceTypeRepository,
        PrefRepository $prefRepository,
        MobileDetector $mobileDetector,
        SessionInterface $session,
        Security $security
    ) {
        parent::__construct($container, $entityManager, $orderRepository, $orderItemTypeRepository, $orderStatusRepository, $deliveryRepository,
            $paymentRepository, $deviceTypeRepository, $prefRepository, $mobileDetector, $session, $security);
        
        $this->security = $security;
    }

    /**
     * @param Shipping $Shipping
     */
    protected function setDefaultDelivery(Shipping $Shipping)
    {
        $Customer = $this->security->getUser();
        $isCampaign = true;
        $excludes = [OrderStatus::PENDING, OrderStatus::PROCESSING, OrderStatus::RETURNED];

        $Orders = $this->orderRepository
            ->createQueryBuilder('o')
            ->where('o.Customer = :Customer')
            ->andWhere('o.OrderStatus NOT IN (:excludes)')
            ->setParameter(':Customer', $Customer)
            ->setParameter(':excludes', $excludes)
            ->getQuery()
            ->getResult();

        if (count($Orders)) $isCampaign = false;

        // 配送商品に含まれる販売種別を抽出.
        $OrderItems = $Shipping->getOrderItems();
        $SaleTypes = [];
        /** @var OrderItem $OrderItem */
        foreach ($OrderItems as $OrderItem) {
            $ProductClass = $OrderItem->getProductClass();
            $SaleType = $ProductClass->getSaleType();
            $SaleTypes[$SaleType->getId()] = $SaleType;
        }

        // 販売種別に紐づく配送業者を取得.
        $allDeliveries = $this->deliveryRepository->getDeliveries($SaleTypes);

        // If Product is campaign, ShippingType whose delivery fee is 0 should be chosen.
        if (count($OrderItems) == 1 && $isCampaign) {
            $Delivery = $allDeliveries[0];
        } else {
            $Delivery = $allDeliveries[1];
        }

        // 販売種別に紐づく配送業者を取得.
        // $Deliveries = $this->deliveryRepository->getDeliveries($SaleTypes);

        // 初期の配送業者を設定
        // $Delivery = current($Deliveries);
        $Shipping->setDelivery($Delivery);
        $Shipping->setShippingDeliveryName($Delivery->getName());
    }
}
