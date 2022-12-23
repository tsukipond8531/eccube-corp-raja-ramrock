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

namespace Plugin\ProductOption\Form\Extension\Admin;

use Doctrine\ORM\EntityManagerInterface;
use Eccube\Entity\Master\OrderItemType as OrderItemTypeMaster;
use Eccube\Form\Type\Admin\OrderItemType;
use Eccube\Service\TaxRuleService;
use Plugin\ProductOption\Entity\Option;
use Plugin\ProductOption\Entity\OptionCategory;
use Plugin\ProductOption\Entity\OrderItemOption;
use Plugin\ProductOption\Entity\OrderItemOptionCategory;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\Extension\Core\Type;

class OrderItemExtension extends AbstractTypeExtension
{

    public function __construct(
        EntityManagerInterface $entityManager,
        TaxRuleService $taxRuleService

    ) {
        $this->entityManager = $entityManager;
        $this->taxRuleService = $taxRuleService;
    }

    public function buildForm(FormBuilderInterface $builder, array $build_options)
    {
        $builder->add('option_serial', Type\HiddenType::class,[]);

        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            /** @var OrderItem $OrderItem */
            $OrderItem = $event->getData();
            $OrderItemType = $OrderItem->getOrderItemType();
            switch ($OrderItemType->getId()) {
                case OrderItemTypeMaster::PRODUCT:
                    $serial = $OrderItem->getOptionSerial();
                    if($this->is_json($serial)){
                        $ProductClass = $OrderItem->getProductClass();
                        $optionRepository = $this->entityManager->getRepository(Option::class);
                        $optionCategoryRepository = $this->entityManager->getRepository(OptionCategory::class);
                        $optionPrice = 0;
                        $optionTax = 0;
                        $sortNo = 0;
                        foreach($OrderItem->getOrderItemOptions() as $OrderItemOption){
                            $OrderItem->removeOrderItemOption($OrderItemOption);
                        }
                        foreach(json_decode($serial) as $optionId => $values){
                            if(!is_array($values))$values = [$values];
                            $Option = $optionRepository->find($optionId);
                            $OrderItemOption = new OrderItemOption();
                            $OrderItemOption->setLabel($Option->getName())
                                            ->setOptionId($Option->getId())
                                            ->setOrderItem($OrderItem)
                                            ->setSortNo($sortNo++);
                            $categorySortNo = 0;
                            foreach($values as $value){
                                if(
                                    $Option->getType() == Option::SELECT_TYPE
                                    || $Option->getType() == Option::RADIO_TYPE
                                    || $Option->getType() == Option::CHECKBOX_TYPE
                                  ){
                                    $OptionCategory = $optionCategoryRepository->find($value);
                                    $value = $OptionCategory->getName();
                                }else{
                                    $OptionCategory = null;
                                    $OptionCategories = $Option->getOptionCategories();
                                    if(count($OptionCategories) > 0)$OptionCategory = $OptionCategories[0];
                                }
                                $OrderItemOptionCategory = new OrderItemOptionCategory();
                                $OrderItemOptionCategory
                                                        ->setOrderItemOption($OrderItemOption)
                                                        ->setSortNo($categorySortNo++)
                                                        ->setValue($value);
                                if(!is_null($OptionCategory)){
                                    $OrderItemOptionCategory->setDeliveryFreeFlg($OptionCategory->getDeliveryFreeFlg())
                                                            ->setOptionCategoryId($OptionCategory->getId())
                                                            ->setPrice($OptionCategory->getValue())
                                                            ->setTax($this->taxRuleService->getTax($OptionCategory->getValue(),$ProductClass->getProduct(),$ProductClass));
                                    $optionPrice += $OrderItemOptionCategory->getPrice();
                                    $optionTax += $OrderItemOptionCategory->getTax();
                                }
                                $OrderItemOption->addOrderItemOptionCategory($OrderItemOptionCategory);
                            }
                            $OrderItem->addOrderItemOption($OrderItemOption);
                            $OrderItem->setOptionSerial(serialize(json_decode($serial, true)));
                        }
                    }
                    break;

                default:
                    break;
            }
        });
    }

    function is_json($string){
       return is_string($string) && is_array(json_decode($string, true)) && (json_last_error() == JSON_ERROR_NONE) ? true : false;
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return OrderItemType::class;
    }

    public static function getExtendedTypes(): iterable
    {
        return [OrderItemType::class];
    }
}
