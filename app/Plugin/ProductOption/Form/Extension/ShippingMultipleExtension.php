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

namespace Plugin\ProductOption\Form\Extension;

use Eccube\Repository\ShippingRepository;
use Eccube\Form\Type\ShippingMultipleType;
use Eccube\Form\Type\ShippingMultipleItemType;
use Plugin\ProductOption\Util\CommonUtil;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;

class ShippingMultipleExtension extends AbstractTypeExtension
{

    /**
     * @var ShippingRepository
     */
    protected $shippingRepository;

    /**
     * ShippingMultipleType constructor.
     *
     * @param ShippingRepository $shippingRepository
     */
    public function __construct(ShippingRepository $shippingRepository)
    {
        $this->shippingRepository = $shippingRepository;
    }

    public function buildForm(FormBuilderInterface $builder, array $build_options)
    {
        $builder
            ->addEventListener(FormEvents::POST_SET_DATA, function ($event) {
                /** @var \Eccube\Entity\OrderItem $data */
                $data = $event->getData();
                /** @var \Symfony\Component\Form\Form $form */
                $form = $event->getForm();

                if (is_null($data)) {
                    return;
                }

                $shippings = $this->shippingRepository->findShippingsProduct($data->getOrder(), $data->getProductClass());

                $setShippings = [];
                // Add product class for each shipping on view
                foreach ($shippings as $key => $shipping) {
                    $flg = true;
                    foreach($shipping->getProductOrderItems() as $orderItem){
                        if(CommonUtil::compareArray(unserialize($data->getOptionSerial()),unserialize($orderItem->getOptionSerial()))){
                            $flg = false;
                            break;
                        }
                    }
                    if($flg)continue;
                    $shipping->setProductClassOfTemp($data->getProductClass());
                    $shipping->setOptionOfTemp($data->getOptionSerial());
                    $shippingTmp = clone $shipping;
                    $setShippings[] = $shippingTmp;
                }
                $form
                    ->add('shipping', CollectionType::class, [
                        'entry_type' => ShippingMultipleItemType::class,
                        'data' => $setShippings,
                        'allow_add' => true,
                        'allow_delete' => true,
                    ]);
            });
    }


    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return ShippingMultipleType::class;
    }

    public static function getExtendedTypes(): iterable
    {
        return [ShippingMultipleType::class];
    }

}
