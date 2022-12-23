<?php

namespace Customize\Doctrine\EventSubscriber;

use Doctrine\Common\EventSubscriber;
use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;
use Eccube\Entity\ProductClass;
use Eccube\Service\TaxRuleService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Eccube\Doctrine\EventSubscriber\TaxRuleEventSubscriber as BaseTaxRuleEventSubscriber;

class TaxRuleEventSubscriber extends BaseTaxRuleEventSubscriber
{
    /**
     * TaxRuleEventSubscriber constructor.
     */
    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
    }

    public function prePersist(LifecycleEventArgs $args)
    {
        $entity = $args->getObject();

        if ($entity instanceof ProductClass) {
            $entity->setPrice01IncTax($this->getTaxRuleService()->getPriceIncTax($entity->getPrice01(),
                $entity->getProduct(), $entity));
            $entity->setPrice02IncTax($this->getTaxRuleService()->getPriceIncTax($entity->getPrice02(),
                $entity->getProduct(), $entity));
            $entity->setDefaultPriceIncTax($this->getTaxRuleService()->getPriceIncTax($entity->getDefaultPrice(),
                $entity->getProduct(), $entity));
        }
    }

    public function postLoad(LifecycleEventArgs $args)
    {
        $entity = $args->getObject();

        if ($entity instanceof ProductClass) {
            $entity->setPrice01IncTax($this->getTaxRuleService()->getPriceIncTax($entity->getPrice01(),
                $entity->getProduct(), $entity));
            $entity->setPrice02IncTax($this->getTaxRuleService()->getPriceIncTax($entity->getPrice02(),
                $entity->getProduct(), $entity));
            $entity->setDefaultPriceIncTax($this->getTaxRuleService()->getPriceIncTax($entity->getDefaultPrice(),
                $entity->getProduct(), $entity));
        }
    }

    public function postPersist(LifecycleEventArgs $args)
    {
        $entity = $args->getObject();

        if ($entity instanceof ProductClass) {
            $entity->setPrice01IncTax($this->getTaxRuleService()->getPriceIncTax($entity->getPrice01(),
                $entity->getProduct(), $entity));
            $entity->setPrice02IncTax($this->getTaxRuleService()->getPriceIncTax($entity->getPrice02(),
                $entity->getProduct(), $entity));
            $entity->setDefaultPriceIncTax($this->getTaxRuleService()->getPriceIncTax($entity->getDefaultPrice(),
                $entity->getProduct(), $entity));
        }
    }

    public function postUpdate(LifecycleEventArgs $args)
    {
        $entity = $args->getObject();

        if ($entity instanceof ProductClass) {
            $entity->setPrice01IncTax($this->getTaxRuleService()->getPriceIncTax($entity->getPrice01(),
                $entity->getProduct(), $entity));
            $entity->setPrice02IncTax($this->getTaxRuleService()->getPriceIncTax($entity->getPrice02(),
                $entity->getProduct(), $entity));
            $entity->setDefaultPriceIncTax($this->getTaxRuleService()->getPriceIncTax($entity->getDefaultPrice(),
                $entity->getProduct(), $entity));
        }
    }
}