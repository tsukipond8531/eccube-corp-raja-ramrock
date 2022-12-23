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

namespace Plugin\ProductOption;

use Doctrine\ORM\EntityManagerInterface;
use Eccube\Entity\Shipping;
use Eccube\Entity\OrderItem;
use Eccube\Entity\Master\OrderItemType;
use Eccube\Event\EccubeEvents;
use Eccube\Event\TemplateEvent;
use Eccube\Event\EventArgs;
use Eccube\Form\Type\ShippingMultipleType;
use Eccube\Repository\BaseInfoRepository;
use Eccube\Repository\TaxRuleRepository;
use Eccube\Repository\Master\OrderItemTypeRepository;
use Eccube\Service\TaxRuleService;
use Plugin\ProductOption\Entity\Option;
use Plugin\ProductOption\Entity\OptionCategory;
use Plugin\ProductOption\Entity\ProductOption;
use Plugin\ProductOption\Entity\OrderItemOption;
use Plugin\ProductOption\Entity\OrderItemOptionCategory;
use Plugin\ProductOption\Repository\OptionRepository;
use Plugin\ProductOption\Repository\OptionCategoryRepository;
use Plugin\ProductOption\Repository\ProductOptionRepository;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ProductOptionEvent implements EventSubscriberInterface
{
    private $container;

    private $entityManager;

    private $BaseInfo;

    private $orderItemTypeRepository;

    private $optionRepository;

    private $optionCategoryRepository;

    private $productOptionRepository;

    private $tokenStorage;

    private $taxRuleService;

    private $taxRuleRepository;

    public function __construct(
            ContainerInterface $container,
            EntityManagerInterface $entityManager,
            BaseInfoRepository $baseInfoRepository,
            OrderItemTypeRepository $orderItemTypeRepository,
            OptionRepository $optionRepository,
            OptionCategoryRepository $optionCategoryRepository,
            ProductOptionRepository $productOptionRepository,
            TokenStorageInterface $tokenStorage,
            TaxRuleService $taxRuleService,
            TaxRuleRepository $taxRuleRepository
            )
    {
        $this->container = $container;
        $this->entityManager = $entityManager;
        $this->BaseInfo = $baseInfoRepository->get();
        $this->orderItemTypeRepository = $orderItemTypeRepository;
        $this->optionRepository = $optionRepository;
        $this->optionCategoryRepository = $optionCategoryRepository;
        $this->productOptionRepository = $productOptionRepository;
        $this->tokenStorage = $tokenStorage;
        $this->taxRuleService = $taxRuleService;
        $this->taxRuleRepository = $taxRuleRepository;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            '@admin/Product/index.twig' => 'onTemplateAdminProduct',
            '@admin/Product/product.twig' => 'onTemplateAdminProductEdit',
            EccubeEvents::ADMIN_PRODUCT_COPY_COMPLETE => 'hookAdminProductCopyComplete',
            EccubeEvents::ADMIN_PRODUCT_CSV_EXPORT => 'hookAdminProductCsvExport',
            '@admin/Order/edit.twig' => 'onTemplateAdminOrderEdit',
            '@admin/Order/shipping.twig' => 'onTemplateAdminOrderEdit',
            '@admin/Order/search_product.twig' => 'onTemplateAdminOrderSearchProduct',
            EccubeEvents::ADMIN_ORDER_CSV_EXPORT_ORDER => 'hookAdminOrderCsvExport',
            'Product/list.twig' => 'onTemplateProductList',
            'Product/detail.twig' => 'onTemplateProductDetail',
            'Cart/index.twig' => 'onTemplateCart',
            'Shopping/index.twig' => 'onTempleteShoppingIndex',
            'Shopping/confirm.twig' => 'onTempleteShoppingIndex',
            'Shopping/shipping_multiple.twig' => 'onTempleteShoppingShippingMultiple',
            EccubeEvents::FRONT_SHOPPING_SHIPPING_MULTIPLE_INITIALIZE => 'onHookShoppingShippingMultipleInitialize',
            EccubeEvents::FRONT_SHOPPING_SHIPPING_MULTIPLE_COMPLETE => 'onHookShoppingShippingMultipleComplete',
            'Mypage/index.twig' => 'onTemplateMypageIndex',
            'Mypage/history.twig' => 'onTemplateMypageHistory',
            'csvimportproductext.admin.product.csv.import.product.descriptions' => 'hookAdminProductCsvImportProductDescriptions',
            'csvimportproductext.admin.product.csv.import.product.check'=> 'hookAdminProductCsvImportProductCheck',
            'csvimportproductext.admin.product.csv.import.product.process' => 'hookAdminProductCsvImportProductProcess',
        ];
    }

    public function onTemplateAdminProduct(TemplateEvent $event)
    {
        $twig = '@ProductOption/admin/Product/product_list.twig';
        $event->addSnippet($twig);
        $js = '@ProductOption/admin/Product/product_list.js';
        $event->addAsset($js);
    }

    public function onTemplateAdminProductEdit(TemplateEvent $event)
    {
        $source = $event->getSource();

        if(preg_match("/\<\/div\>[\n|\r\n|\r]\s*\{\%\sif\sid\sis\snot\snull\s\%\}[\n|\r\n|\r]/",$source, $result)){
            $search = $result[0];
            $replace_parts = trim($search, '</div>');
            $replace = "</div>{{ include('@ProductOption/admin/Product/product_edit.twig') }}" . $replace_parts;
            $source = str_replace($search, $replace, $source);
        }

        $event->setSource($source);

        $parameters = $event->getParameters();
        $arrType[Option::SELECT_TYPE] = trans("productoption.option.type.select");
        $arrType[Option::RADIO_TYPE] = trans("productoption.option.type.radio");
        $arrType[Option::CHECKBOX_TYPE] = trans("productoption.option.type.checkbox");
        $arrType[Option::TEXT_TYPE] = trans("productoption.option.type.text");
        $arrType[Option::TEXTAREA_TYPE] = trans("productoption.option.type.textarea");
        $arrType[Option::DATE_TYPE] = trans("productoption.option.type.date");
        $arrType[Option::NUMBER_TYPE] = trans("productoption.option.type.number");
        $parameters['arrType'] = $arrType;
        $event->setParameters($parameters);
    }

    public function hookAdminProductCopyComplete(EventArgs $event)
    {
        $Product = $event->getArgument('Product');
        $CopyProduct = $event->getArgument('CopyProduct');

        foreach ($Product->getProductOptions() as $oldProductOption) {
            $newProductOption = new ProductOption();
            $newProductOption->setProduct($CopyProduct);
            $newProductOption->setOption($oldProductOption->getOption());
            $newProductOption->setSortNo($oldProductOption->getSortNo());
            $this->productOptionRepository->save($newProductOption);
        }
    }

    public function hookAdminProductCsvExport(EventArgs $event)
    {
        $ExportCsvRow = $event->getArgument('ExportCsvRow');
        if ($ExportCsvRow->isDataNull()) {
            $csvService = $event->getArgument('csvService');
            $ProductClass = $event->getArgument('ProductClass');
            $Product = $ProductClass->getProduct();
            $Csv = $event->getArgument('Csv');

            $csvEntityName = str_replace('\\\\', '\\', $Csv->getEntityName());
            if($csvEntityName == 'Plugin\ProductOption\Entity\ProductOption'){
                $array = [];
                foreach($Product->getProductOptions() as $ProductOption){
                    if($Csv->getFieldName() == 'product_option_id'){
                        $array[] = $ProductOption->getOption()->getId();
                    }elseif($Csv->getFieldName() == 'product_option_name'){
                        $array[] = $ProductOption->getOption()->getBackendName();
                    }
                }
                $ExportCsvRow->setData(implode(',', $array));
            }
        }
    }

    public function onTemplateAdminOrderEdit(TemplateEvent $event)
    {
        $source = $event->getSource();
        $parameters = $event->getParameters();

        if(preg_match("/\@admin\/Order\/order\_item\_prototype\.twig/",$source, $result)){
            $search = $result[0];
            $replace = "@ProductOption/admin/Order/order_item_prototype.twig";
            $source = str_replace($search, $replace, $source);
        }

        if(preg_match("/\{\{\sOrderItem\.product\_name\s\}\}/",$source, $result)){
            $search = $result[0];
            $replace = $search . "{{ include('@ProductOption/admin/Order/orderitem_option.twig') }}";
            $source = str_replace($search, $replace, $source);
        }

        if(preg_match("/\{\{\s*form\_widget\(orderItemForm\.product\_name,\s*\{\s*'type'\:\s*'hidden'\s*\}\)\s*\}\}/",$source, $result)){
            $search = $result[0];
            $replace = $search . "{{ form_widget(orderItemForm.option_serial, { 'type': 'hidden' }) }}";
            $source = str_replace($search, $replace, $source);
        }

        $event->setSource($source);
    }

    public function onTemplateAdminOrderSearchProduct(TemplateEvent $event)
    {
        $source = $event->getSource();

        if(preg_match("/\<\/script\>/",$source, $result)){
            $search = $result[0];
            $replace = $search . "{{ include('@ProductOption/admin/Order/search_product_js.twig') }}";
            $source = str_replace($search, $replace, $source);
        }

        if(preg_match("/fnAddOrderItem/",$source, $result)){
            $search = $result[0];
            $replace = "fnAddOrderItemOption";
            $source = str_replace($search, $replace, $source);
        }

        if(preg_match("/\{\{\s\'admin\.product\.product\_class\_\_short\'\|trans\s\}\}/",$source, $result)){
            $search = $result[0];
            $replace = $search . "/{{ 'productoption.admin.common.option'|trans }}";
            $source = str_replace($search, $replace, $source);
        }

        if(preg_match("/\<\/td\>[\n|\r\n|\r]\s*\<td class=\"align\-middle pr\-3 text\-right\"\>/",$source, $result)){
            $search = $result[0];
            $snippet = file_get_contents($this->container->getParameter('plugin_realdir'). '/ProductOption/Resource/template/admin/Order/search_product_option.twig');
            $replace = $snippet . $search;
            $source = str_replace($search, $replace, $source);
        }elseif(preg_match("/\<\/td\>[\n|\r\n|\r]\s*\{\%\sif\sProduct\.stock_find\s\%\}/",$source, $result)){
            $search = $result[0];
            $snippet = file_get_contents($this->container->getParameter('plugin_realdir'). '/ProductOption/Resource/template/admin/Order/search_product_option.twig');
            $replace = $snippet . $search;
            $source = str_replace($search, $replace, $source);
        }

        $event->setSource($source);

        $parameters = $event->getParameters();
        $Products = $parameters['Products'];
        $optionParameters = $this->getOptionParameters($Products);
        $parameters = array_merge($parameters, $optionParameters);
        $event->setParameters($parameters);
    }

    public function hookAdminOrderCsvExport(EventArgs $event)
    {
        $ExportCsvRow = $event->getArgument('ExportCsvRow');
        if ($ExportCsvRow->isDataNull()) {
            $csvService = $event->getArgument('csvService');
            $OrderItem = $event->getArgument('OrderItem');
            $Csv = $event->getArgument('Csv');

            $csvEntityName = str_replace('\\\\', '\\', $Csv->getEntityName());
            if($csvEntityName == 'Plugin\ProductOption\Entity\OrderItemOption'){
                $data = null;
                $arrData = [];
                $OrderItemOptions = $OrderItem->getOrderItemOptions();
                if(!is_null($Csv->getReferenceFieldName())){
                    $optionId = $Csv->getReferenceFieldName();
                    foreach($OrderItemOptions as $OrderItemOption){
                        if($optionId == $OrderItemOption->getOptionId()){
                            foreach($OrderItemOption->getOrderItemOptionCategories() as $OrderItemOptionCategory){
                                $arrData[] = $OrderItemOptionCategory->getValue();
                            }
                        }
                    }
                    if(count($arrData) > 0)$data = implode(',',$arrData);
                }else{
                    foreach($OrderItemOptions as $OrderItemOption){
                        $text = $OrderItemOption->getLabel(). ':';
                        foreach($OrderItemOption->getOrderItemOptionCategories() as $OrderItemOptionCategory){
                            $text .= $OrderItemOptionCategory->getValue() .',';
                        }
                        $arrData[] = rtrim($text,',');
                    }
                    if(count($arrData) > 0)$data = implode(',',$arrData);
                }
                $ExportCsvRow->setData($data);
            }
        }
    }

    public function onTemplateProductList(TemplateEvent $event)
    {
        $parameters = $event->getParameters();
        $Products = $parameters['pagination'];
        $source = $event->getSource();

        // カート追加のフォームが存在しない場合は処理を行わない
        if(!preg_match('/url\(\'product_add_cart\'/',$source, $result)){
            return;
        }

        $optionParameters = $this->getOptionParameters($Products);

        $parameters = array_merge($parameters, $optionParameters);
        $parameters['ProductOptions'] = $this->productOptionRepository->findAll();

        $event->setParameters($parameters);

        $twig = 'Product/option_css.twig';
        $event->addAsset($twig);

        $twig = '@ProductOption/default/Product/option_js.twig';
        $event->addSnippet($twig);

        $twig = '@ProductOption/default/Product/list_js.twig';
        $event->addSnippet($twig);

        if(!preg_match('/include\(\s*\'Product\/option\.twig/',$source, $result)){
            if(preg_match("/\<div\sclass\=\"ec\-numberInput\"\>/",$source, $result)){
                $search = $result[0];
                $replace = "{{ include('Product/option.twig') }}" . $search;
                $source = str_replace($search, $replace, $source);
            }
        }

        if(!preg_match('/include\(\s*\'Product\/option\_description\.twig/',$source, $result)){
            if(preg_match("/\<div\sclass\=\"ec\-modal\"\>/",$source, $result)){
                $search = $result[0];
                $replace = "{{ include('Product/option_description.twig') }}" . $search;
                $source = str_replace($search, $replace, $source);
            }
        }

        $event->setSource($source);
    }

    public function onTemplateProductDetail(TemplateEvent $event)
    {
        $parameters = $event->getParameters();
        $Product = $parameters['Product'];
        $Products = [$Product];

        $optionParameters = $this->getOptionParameters($Products);
        $parameters = array_merge($parameters, $optionParameters);
        $parameters['ProductOptions'] = $Product->getProductOptions();

        $event->setParameters($parameters);

        $twig = 'Product/option_css.twig';
        $event->addAsset($twig);

        $twig = '@ProductOption/default/Product/option_js.twig';
        $event->addSnippet($twig);

        $twig = '@ProductOption/default/Product/detail_js.twig';
        $event->addSnippet($twig);

        $source = $event->getSource();

        if(!preg_match('/include\(\s*\'Product\/option\.twig/',$source, $result)){
            if(preg_match("/\<div\sclass\=\"ec\-numberInput\"\>/",$source, $result)){
                $search = $result[0];
                $replace = "{{ include('Product/option.twig') }}" . $search;
                $source = str_replace($search, $replace, $source);
            }
        }

        if(!preg_match('/include\(\s*\'Product\/option\_description\.twig/',$source, $result)){
            if(preg_match("/\<div\sclass\=\"ec\-modal\"\>/",$source, $result)){
                $search = $result[0];
                $replace = "{{ include('Product/option_description.twig') }}" . $search;
                $source = str_replace($search, $replace, $source);
            }
        }

        $event->setSource($source);
    }

    private function getOptionParameters($Products)
    {
        $basicPointRate = $this->BaseInfo->getBasicPointRate();
        $taxRules = [];
        $optionPrices = [];
        $optionPoints = [];
        $optionMultiples = [];
        $optionDefaults = [];
        $default_class_id = [];
        foreach($Products as $Product){
            foreach($Product->getProductClasses() as $ProductClass){
                if(!isset($default_class_id[$Product->getId()]))$default_class_id[$Product->getId()] = $ProductClass->getId();
                $pointRate = $ProductClass->getPointRate();
                $TaxRule = $this->taxRuleRepository->getByRule($Product, $ProductClass);
                $taxRules[$ProductClass->getId()]['tax_rate'] = $TaxRule->getTaxRate();
                $taxRules[$ProductClass->getId()]['tax_rule'] = $TaxRule->getRoundingType()->getId();
            }
            if(is_null($pointRate)){
                $pointRate = $basicPointRate;
            }
            foreach($Product->getProductOptions() as $ProductOption){
                $Option = $ProductOption->getOption();
                if($Option->getType() == Option::CHECKBOX_TYPE || $Option->getType() == Option::RADIO_TYPE || $Option->getType() == Option::SELECT_TYPE){
                    foreach($Option->getOptionCategories() as $OptionCategory){
                        $optionDefaults[$Product->getId()][$Option->getId()][$OptionCategory->getId()] = $OptionCategory->getDisableFlg();
                        $price = $OptionCategory->getValue();
                        if(is_null($price))$price = 0;
                        $optionPrices[$Product->getId()][$Option->getId()][$OptionCategory->getId()] = $price;
                        $optionPoints[$Product->getId()][$Option->getId()][$OptionCategory->getId()] = round($price * ($pointRate / 100));
                    }
                }else{
                    $OptionCategories = $Option->getOptionCategories();
                    $price = 0;
                    if(count($OptionCategories) > 0){
                        $OptionCategory = $OptionCategories[0];
                        $price = $OptionCategory->getValue();
                        if(is_null($price))$price = 0;
                    }
                    if($Option->getType() == Option::NUMBER_TYPE){
                        $flg = false;
                        if(isset($OptionCategory) && $OptionCategory->getMultipleFlg())$flg = true;
                        $optionMultiples[$Product->getId()][$Option->getId()] = $flg;
                    }
                    $optionPrices[$Product->getId()][$Option->getId()][0] = $price;
                    $optionPoints[$Product->getId()][$Option->getId()][0] = round($price * ($pointRate / 100));
                    if(isset($OptionCategory))unset($OptionCategory);
                }
            }
        }
        $parameters['optionPrices'] = $optionPrices;
        $parameters['optionPoints'] = $optionPoints;
        $parameters['optionMultiples'] = $optionMultiples;
        $parameters['optionDefaults'] = $optionDefaults;
        $parameters['taxRules'] = $taxRules;
        $parameters['default_class_id'] = $default_class_id;
        return $parameters;
    }

    public function onTemplateCart(TemplateEvent $event)
    {
        $source = $event->getSource();

        if(preg_match("/url\('cart\_handle\_item'\s*,\s*\{'operation'\s*:\s*'down'\s*,\s*'productClassId'\s*:\s*ProductClass\.id/",$source, $result)){
            $search = $result[0];
            $snipet = "url('productoption_cart_handle_item', {'operation': 'down', 'cartItemId': CartItem.id";
            $replace = $snipet;
            $source = str_replace($search, $replace, $source);
        }

        if(preg_match("/url\('cart\_handle\_item'\s*,\s*\{'operation'\s*:\s*'up'\s*,\s*'productClassId'\s*:\s*ProductClass\.id/",$source, $result)){
            $search = $result[0];
            $snipet = "url('productoption_cart_handle_item', {'operation': 'up', 'cartItemId': CartItem.id";
            $replace = $snipet;
            $source = str_replace($search, $replace, $source);
        }

        if(preg_match("/url\('cart\_handle\_item',\s\{'operation'\:\s'remove',\s'productClassId'\:\sProductClass\.id/",$source, $result)){
            $search = $result[0];
            $snipet = "url('productoption_cart_handle_item', {'operation': 'remove', 'cartItemId': CartItem.id";
            $replace = $snipet;
            $source = str_replace($search, $replace, $source);
        }

        if(preg_match("/\<\/div\>[\n|\r\n\\r]\s*<div\sclass\=\"ec\-cartRow\_\_unitPrice\"\>/",$source, $result)){
            $search = $result[0];
            $replace = "{{ include('@ProductOption/default/Cart/cart_option.twig') }}" . $search;
            $source = str_replace($search, $replace, $source);
        }

        $event->setSource($source);

        $parameters = $event->getParameters();
        $Carts = $parameters['Carts'];
        $isDeliveryFree = $parameters['is_delivery_free'];

        foreach($Carts as $Cart){
            foreach($Cart->getCartItems() as $CartItem){
                $flg = $CartItem->getDeliveryFreeFlg();
                if($flg == OptionCategory::ON){
                    if(!$isDeliveryFree[$Cart->getCartKey()]){
                        $isDeliveryFree[$Cart->getCartKey()] = true;
                    }
                }
            }
        }

        $parameters['is_delivery_free'] = $isDeliveryFree;
        $event->setParameters($parameters);
    }

    public function onTempleteShoppingIndex(TemplateEvent $event)
    {
        $source = $event->getSource();

        if(preg_match("/\<p\>\{\{\sorderItem\.priceIncTax\|price\s\}\}/",$source, $result)){
            $search = $result[0];
            $replace = "{{ include('@ProductOption/default/Shopping/orderitem_option.twig') }}" . $search;
            $source = str_replace($search, $replace, $source);
        }

        $event->setSource($source);
    }

    public function onTempleteShoppingShippingMultiple(TemplateEvent $event)
    {
        $source = $event->getSource();

        if(preg_match("/\{\%\sif\sorderItem\.productClass\.id\s\=\=\skey\s\%\}/",$source, $result)){
            $search = $result[0];
            $replace = "{% if orderItem.productClass.id ~ '_' ~ orderItem.option_serial == key %}";
            $source = str_replace($search, $replace, $source);
        }

        if(preg_match("/\<div\sclass\=\"ec\-AddAddress\_\_itemtPrice\"\>/",$source, $result)){
            $search = $result[0];
            $replace = $search . "{{ include('@ProductOption/default/Shopping/shipping_multiple_option.twig') }}";
            $source = str_replace($search, $replace, $source);
        }

        $event->setSource($source);

        $parameters = $event->getParameters();
        $OrderItems = $parameters['OrderItems'];

        $OrderItemsForFormBuilder = [];
        $ItemQuantitiesByOption = [];
        $tmpIds = [];
        $Order = $OrderItems[0]->getOrder();
        foreach($Order->getProductOrderItems() as $OrderItem){
            $quantity = $OrderItem->getQuantity();
            $itemId = $OrderItem->getProductClass()->getId();
            $serial = $OrderItem->getOptionSerial();
            $key = $itemId .'_'. $serial;
            if (!in_array($key, $tmpIds)) {
                $OrderItemsForFormBuilder[] = $OrderItem;
                $tmpIds[] = $key;
                $ItemQuantitiesByOption[$key] = $quantity;
            }else{
                $ItemQuantitiesByOption[$key] += $quantity;
            }
        }

        $parameters['OrderItems'] = $OrderItemsForFormBuilder;
        $parameters['compItemQuantities'] = $ItemQuantitiesByOption;
        $event->setParameters($parameters);
    }

    public function onHookShoppingShippingMultipleInitialize(EventArgs $event)
    {
        $builder = $event->getArgument('builder');
        $Order = $event->getArgument('Order');

        $OrderItemsForFormBuilder = [];
        $tmpIds = [];
        foreach($Order->getProductOrderItems() as $OrderItem){
            $itemId = $OrderItem->getProductClass()->getId();
            $serial = $OrderItem->getOptionSerial();
            $key = $itemId .'_'. $serial;
            if (!in_array($key, $tmpIds)) {
                $OrderItemsForFormBuilder[] = $OrderItem;
                $tmpIds[] = $key;
            }
        }

        $builder->remove('shipping_multiple');
        $builder
            ->add('shipping_multiple', CollectionType::class, [
                'entry_type' => ShippingMultipleType::class,
                'data' => $OrderItemsForFormBuilder,
                'allow_add' => true,
                'allow_delete' => true,
            ]);
    }

    public function onHookShoppingShippingMultipleComplete(EventArgs $event)
    {
        $form = $event->getArgument('form');
        $Order = $event->getArgument('Order');

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form['shipping_multiple'];

            // フォームの入力から、送り先ごとに商品の数量を集計する
            $arrOrderItemTemp = [];
            foreach ($data as $multiples) {
                $OrderItem = $multiples->getData();
                foreach ($multiples as $items) {
                    foreach ($items as $item) {
                        $CustomerAddress = $item['customer_address']->getData();
                        $customerAddressName = $CustomerAddress->getShippingMultipleDefaultName();

                        $itemId = $OrderItem->getProductClass()->getId() . '_' . $OrderItem->getOptionSerial();
                        $quantity = $item['quantity']->getData();

                        if (isset($arrOrderItemTemp[$customerAddressName]) && array_key_exists($itemId, $arrOrderItemTemp[$customerAddressName])) {
                            $arrOrderItemTemp[$customerAddressName][$itemId] = $arrOrderItemTemp[$customerAddressName][$itemId] + $quantity;
                        } else {
                            $arrOrderItemTemp[$customerAddressName][$itemId] = $quantity;
                        }
                    }
                }
            }

            // -- ここから先がお届け先を再生成する処理 --

            // お届け先情報をすべて削除
            /** @var Shipping $Shipping */
            foreach ($Order->getShippings() as $Shipping) {
                foreach ($Shipping->getOrderItems() as $OrderItem) {
                    $Shipping->removeOrderItem($OrderItem);
                    $Order->removeOrderItem($OrderItem);
                    $this->entityManager->remove($OrderItem);
                }
                $Order->removeShipping($Shipping);
                $this->entityManager->remove($Shipping);
            }

            // お届け先のリストを作成する
            $ShippingList = [];
            foreach ($data as $multiples) {
                $OrderItem = $multiples->getData();
                $ProductClass = $OrderItem->getProductClass();
                $Delivery = $OrderItem->getShipping()->getDelivery();
                $saleTypeId = $ProductClass->getSaleType()->getId();

                foreach ($multiples as $items) {
                    foreach ($items as $item) {
                        $CustomerAddress = $item['customer_address']->getData();
                        $customerAddressName = $CustomerAddress->getShippingMultipleDefaultName();

                        if (isset($ShippingList[$customerAddressName][$saleTypeId])) {
                            continue;
                        }
                        $Shipping = new Shipping();
                        $Shipping
                            ->setOrder($Order)
                            ->setFromCustomerAddress($CustomerAddress)
                            ->setDelivery($Delivery);
                        $Order->addShipping($Shipping);
                        $ShippingList[$customerAddressName][$saleTypeId] = $Shipping;
                    }
                }
            }
            // お届け先のリストを保存
            foreach ($ShippingList as $ShippingListByAddress) {
                foreach ($ShippingListByAddress as $Shipping) {
                    $this->entityManager->persist($Shipping);
                }
            }

            $ProductOrderType = $this->orderItemTypeRepository->find(OrderItemType::PRODUCT);

            // お届け先に、配送商品の情報(OrderItem)を関連付ける
            foreach ($data as $multiples) {
                /** @var OrderItem $OrderItem */
                $OrderItem = $multiples->getData();
                $ProductClass = $OrderItem->getProductClass();
                $Product = $OrderItem->getProduct();
                $saleTypeId = $ProductClass->getSaleType()->getId();
                $itemId = $OrderItem->getProductClass()->getId() . '_' . $OrderItem->getOptionSerial();
                $optionSerial = $OrderItem->getOptionSerial();
                $OrderItemOptions = $OrderItem->getOrderItemOptions();
                $optionPrice = $OrderItem->getOptionPrice();
                $optionTax = $OrderItem->getOptionTax();

                foreach ($multiples as $items) {
                    foreach ($items as $item) {
                        $CustomerAddress = $item['customer_address']->getData();
                        $customerAddressName = $CustomerAddress->getShippingMultipleDefaultName();

                        // お届け先から商品の数量を取得
                        $quantity = 0;
                        if (isset($arrOrderItemTemp[$customerAddressName]) && array_key_exists($itemId, $arrOrderItemTemp[$customerAddressName])) {
                            $quantity = $arrOrderItemTemp[$customerAddressName][$itemId];
                            unset($arrOrderItemTemp[$customerAddressName][$itemId]);
                        } else {
                            // この配送先には送る商品がないのでスキップ（通常ありえない）
                            continue;
                        }

                        // 関連付けるお届け先のインスタンスを取得
                        $Shipping = $ShippingList[$customerAddressName][$saleTypeId];

                        // インスタンスを生成して保存
                        $OrderItem = new OrderItem();
                        $OrderItem->setShipping($Shipping)
                            ->setOrder($Order)
                            ->setProductClass($ProductClass)
                            ->setProduct($Product)
                            ->setProductName($Product->getName())
                            ->setProductCode($ProductClass->getCode())
                            ->setPrice($ProductClass->getPrice02())
                            ->setQuantity($quantity)
                            ->setOrderItemType($ProductOrderType)
                            ->setOptionSerial($optionSerial);

                        $ClassCategory1 = $ProductClass->getClassCategory1();
                        if (!is_null($ClassCategory1)) {
                            $OrderItem->setClasscategoryName1($ClassCategory1->getName());
                            $OrderItem->setClassName1($ClassCategory1->getClassName()->getName());
                        }
                        $ClassCategory2 = $ProductClass->getClassCategory2();
                        if (!is_null($ClassCategory2)) {
                            $OrderItem->setClasscategoryName2($ClassCategory2->getName());
                            $OrderItem->setClassName2($ClassCategory2->getClassName()->getName());
                        }
                        foreach($OrderItemOptions as $OrderItemOption){
                            $newOrderItemOption = new OrderItemOption();
                            $newOrderItemOption->setOptionId($OrderItemOption->getOptionId())
                                               ->setOrderItem($OrderItem)
                                               ->setLabel($OrderItemOption->getLabel())
                                               ->setSortNo($OrderItemOption->getSortNo());
                            foreach($OrderItemOption->getOrderItemOptionCategories() as $OrderItemOptionCategory){
                                $newOrderItemOptionCategory = new OrderItemOptionCategory();
                                $newOrderItemOptionCategory->setOptionCategoryId($OrderItemOptionCategory->getOptionCategoryId())
                                                           ->setOrderItemOption($newOrderItemOption)
                                                           ->setValue($OrderItemOptionCategory->getValue())
                                                           ->setPrice($OrderItemOptionCategory->getPrice())
                                                           ->setTax($OrderItemOptionCategory->getTax())
                                                           ->setDeliveryFreeFlg($OrderItemOptionCategory->getDeliveryFreeFlg())
                                                           ->setSortNo($OrderItemOptionCategory->getSortNo());
                                $newOrderItemOption->addOrderItemOptionCategory($newOrderItemOptionCategory);
                            }
                            $OrderItem->addOrderItemOption($newOrderItemOption);
                        }
                        $OrderItem->setPrice($OrderItem->getPrice() + $optionPrice);
                        $OrderItem->setTax($OrderItem->getTax() + $optionTax);
                        $OrderItem->setOptionSetFlg(true);
                        $Shipping->addOrderItem($OrderItem);
                        $Order->addOrderItem($OrderItem);
                        $this->entityManager->persist($OrderItem);
                    }
                }
            }

            $this->entityManager->flush();
        }
    }

    public function onTemplateMypageIndex(TemplateEvent $event)
    {
        $source = $event->getSource();


        if(preg_match("/Order\.MergedProductOrderItems/",$source, $result)){
            $search = $result[0];
            $replace = "Order.MergedProductOptionOrderItems";
            $source = str_replace($search, $replace, $source);
        }

        if(preg_match("/\<p\sclass\=\"ec\-historyRole\_\_detailPrice\"\>/",$source, $result)){
            $search = $result[0];
            $replace = $search . "{{ include('@ProductOption/default/Mypage/orderitem_option.twig') }}";
            $source = str_replace($search, $replace, $source);
        }

        $event->setSource($source);
    }

    public function onTemplateMypageHistory(TemplateEvent $event)
    {
        $parameters = $event->getParameters();
        $Order = $parameters['Order'];

        foreach($Order->getProductOrderItems() as $OrderItem){
            $ProductClass = $OrderItem->getProductClass();
            $current_price = 0;
            foreach($OrderItem->getOrderItemOptions() as $OrderItemOption){
                foreach($OrderItemOption->getOrderItemOptionCategories() as $OrderItemOptionCategory){
                    $optionCategoryId = $OrderItemOptionCategory->getOptionCategoryId();
                    if($optionCategoryId > 0){
                        $OptionCategory = $this->optionCategoryRepository->find($optionCategoryId);
                        if(!is_null($OptionCategory)){
                            $option_price = $OptionCategory->getValue();
                            if($OptionCategory->getOption()->getType() == Option::NUMBER_TYPE && $OptionCategory->getMultipleFlg()){
                                $option_price *= $OrderItemOptionCategory->getValue();
                            }
                            $current_price += $option_price;
                        }
                    }
                }
            }
            $OrderItem->setCurrentPrice($current_price);
            $OrderItem->setCurrentTax($this->taxRuleService->getTax($OrderItem->getCurrentPrice(),$ProductClass->getProduct(),$ProductClass));
        }
        $event->setParameters($parameters);

        $source = $event->getSource();

        if(preg_match("/=\s*orderItem\.productClass\.price02IncTax|=\s*orderItem\.productClass\.customer\_rank\_priceIncTax/",$source, $result)){
            $search = $result[0];
            $replace = $search . "+ orderItem.CurrentPriceIncTax";
            $source = str_replace($search, $replace, $source);
        }

        if(preg_match("/\{\{\s*orderItem\.productClass\.price02IncTax\|price\s*\}\}/",$source, $result)){
            $search = $result[0];
            $replace = "{% set currentPriceIncTax = orderItem.productClass.price02IncTax+orderItem.CurrentPriceIncTax %}{{ currentPriceIncTax|price }}";
            $source = str_replace($search, $replace, $source);
        }

        if(preg_match("/\{\{\s*orderItem\.productClass\.customer_rank_priceIncTax\|price\s*\}\}/",$source, $result)){
            $search = $result[0];
            $replace = "{% set currentPriceIncTax = orderItem.productClass.customer_rank_priceIncTax+orderItem.CurrentPriceIncTax %}{{ currentPriceIncTax|price }}";
            $source = str_replace($search, $replace, $source);
        }

        if(preg_match("/\<p\>\{\{\sorderItem\.price\_inc\_tax\|price\s\}\}/",$source, $result)){
            $search = $result[0];
            $replace = "{{ include('@ProductOption/default/Shopping/orderitem_option.twig') }}" . $search;
            $source = str_replace($search, $replace, $source);
        }

        $event->setSource($source);
    }

    public function hookAdminProductCsvImportProductDescriptions(EventArgs $event)
    {
        $header = $event->getArgument('header');
        $key = $event->getArgument('key');
        if($key == trans('productoption.csv.product.label.id')){
            $header['description'] = trans('productoption.admin.product.product_csv.product_assign_description');
            $header['required'] = false;
        }

        $event->setArgument('header',$header);
    }

    public function hookAdminProductCsvImportProductCheck(EventArgs $event)
    {
        $row = $event->getArgument('row');
        $data = $event->getArgument('data');
        $errors = $event->getArgument('errors');

        if(isset($row[trans('productoption.csv.product.label.id')])){
            if($row[trans('productoption.csv.product.label.id')] !== '' && preg_match("/[^0-9,]/", $row[trans('productoption.csv.product.label.id')])){
                $message = trans('productoption.admin.product.product_csv.not_correct', [
                    '%line%' => $data->key() + 1,
                    '%name%' => trans('productoption.csv.product.label.id'),
                ]);
                $errors[] = $message;
            }
        }

        $event->setArgument('errors',$errors);
    }

    public function hookAdminProductCsvImportProductProcess(EventArgs $event)
    {
        $row = $event->getArgument('row');
        $data = $event->getArgument('data');
        $ProductClass = $event->getArgument('ProductClass');
        $Product = $ProductClass->getProduct();

        if(isset($row[trans('productoption.csv.product.label.id')])){
            // 一旦クリア
            $currentProductOptions = $Product->getProductOptions();
            foreach ($currentProductOptions as $currentProductOption) {
                $this->entityManager->remove($currentProductOption);
                $Product->removeProductOption($currentProductOption);
            }
            $this->entityManager->persist($Product);
            $this->entityManager->flush();
        }

        if(isset($row[trans('productoption.csv.product.label.id')]) && strlen($row[trans('productoption.csv.product.label.id')]) > 0){
            $productOptions = explode(',', $row[trans('productoption.csv.product.label.id')]);
            foreach($productOptions as $option_id){
                if(is_numeric($option_id)){
                    $Option = $this->optionRepository->find($option_id);
                    if($Option){
                        $ProductOption = new ProductOption();
                        $ProductOption->setProduct($Product);
                        $ProductOption->setOption($Option);
                        $this->productOptionRepository->save($ProductOption);
                        $Product->addProductOption($ProductOption);
                    }
                }
            }
        }
    }

    protected function getUser()
    {
        if (null === $token = $this->tokenStorage->getToken()) {
            return;
        }

        if (!is_object($user = $token->getUser())) {
            // e.g. anonymous authentication
            return;
        }

        return $user;
    }
}
