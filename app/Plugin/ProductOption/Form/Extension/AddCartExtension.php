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

use Eccube\Form\Type\AddCartType;
use Eccube\Common\EccubeConfig;
use Eccube\Service\TaxRuleService;
use Plugin\ProductOption\Entity\Option;
use Plugin\ProductOption\Entity\OptionCategory;
use Plugin\ProductOption\Repository\OptionCategoryRepository;
use Plugin\ProductOption\Repository\ProductOptionRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;

class AddCartExtension extends AbstractTypeExtension
{

    /**
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    protected $entityManager;

    /**
     * @var array
     */
    protected $eccubeConfig;

    /**
     * @var ProductOptionRepository
     */
    protected $productOptionRepository;

    /**
     * @var OptionCategoryRepository
     */
    protected $OptionCategoryRepository;

    /**
     * @var TaxRuleService
     */
    protected $taxRuleService;
    private $requestStack;


    public function __construct(
        EntityManagerInterface $entityManager,
        EccubeConfig $eccubeConfig,
        OptionCategoryRepository $optionCategoryRepository,
        ProductOptionRepository $productOptionRepository,
        TaxRuleService $taxRuleService,
        RequestStack $requestStack
    ) {
        $this->eccubeConfig = $eccubeConfig;
        $this->entityManager = $entityManager;
        $this->optionCategoryRepository = $optionCategoryRepository;
        $this->productOptionRepository = $productOptionRepository;
        $this->taxRuleService = $taxRuleService;
        $this->requestStack = $requestStack;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $build_options)
    {
        $Product = $build_options['product'];
        $request = $this->requestStack->getMasterRequest();
        $route = $request->get('_route');

        $ProductOptions = $this->productOptionRepository->getListByProduct($Product);

        if (is_array($ProductOptions)) {
            foreach ($ProductOptions as $ProductOption) {
                $Product = $ProductOption->getProduct();
                if($Product->getStockFind() || $route === 'admin_order_search_product'){
                    $Option = $ProductOption->getOption();
                    $ProductClasses = $Product->getProductClasses();
                    $ProductClass = $ProductClasses[0];
                    $optionCategories = [];
                    foreach ($Option->getOptionCategories() as $OptionCategory) {
                        $select = $OptionCategory->getName();
                        if($Option->getPricedispFlg()){
                            $description = "";
                            $OptionCategoryVal = $OptionCategory->getValue();
                            if(strlen($OptionCategory->getValue()) > 0 && !empty($OptionCategoryVal)){
                                $description .= '（¥ ' . number_format($this->taxRuleService->getPriceIncTax($OptionCategoryVal,$Product, $ProductClass));
                            }
                            if($OptionCategory->getDeliveryFreeFlg() == OptionCategory::ON){
                                if(strlen($description) == 0){
                                    $description .= '（';
                                }else{
                                    $description .= ' , ';
                                }
                                $description .= trans('productoption.common.delivery_free');
                            }
                            if(strlen($description) > 0){
                                $description .= '）';
                                $select .= $description;
                            }
                        }
                        $OptionCategory->setLabel($select);
                        $optionCategories[$OptionCategory->getId()] = $OptionCategory;
                    }
                    $type = $Option->getType();
                    $options = ['mapped' => false];
                    $options['label'] = $Option->getName();
                    if ($Option->getIsRequired()) {
                        $options['required'] = true;
                        $options['constraints'] = [
                            new Assert\NotBlank(),
                        ];
                    } else {
                        $options['required'] = false;
                    }
                    if ($type == Option::SELECT_TYPE) {
                        $options['class'] = 'Plugin\ProductOption\Entity\OptionCategory';
                        $options['choice_label'] = 'label';
                        $options['expanded'] = false;
                        $options['multiple'] = false;
                        $options['placeholder'] = null;
                        $options['choices'] = $optionCategories;
                        if ($options['required'] === true) {
                            if($Option->getDisableCategory()){
                                $options['constraints'][] = new Assert\NotEqualTo([
                                    'value' => $Option->getDisableCategory(),
                                    'message' => 'This value should not be blank.',
                                ]);
                            }
                        }
                        if($Option->getDefaultCategory()){
                            $options['data'] = $Option->getDefaultCategory();
                        }
                        $form_type = EntityType::class;
                    } elseif ($type == Option::RADIO_TYPE) {
                        $options['class'] = 'Plugin\ProductOption\Entity\OptionCategory';
                        $options['choice_label'] = 'label';
                        $options['expanded'] = true;
                        $options['multiple'] = false;
                        $options['placeholder'] = null;
                        $options['choices'] = $optionCategories;
                        if ($options['required'] === true) {
                            if($Option->getDisableCategory()){
                                $options['constraints'][] = new Assert\NotEqualTo([
                                    'value' => $Option->getDisableCategory(),
                                    'message' => 'This value should not be blank.',
                                ]);
                            }
                        }
                        if($Option->getDefaultCategory()){
                            $options['data'] = $Option->getDefaultCategory();
                        }else{
                            $options['data'] = current($options['choices']);
                        }
                        $form_type = EntityType::class;
                    } elseif ($type == Option::CHECKBOX_TYPE) {
                        $options['class'] = 'Plugin\ProductOption\Entity\OptionCategory';
                        $options['choice_label'] = 'label';
                        $options['expanded'] = true;
                        $options['multiple'] = true;
                        $options['placeholder'] = null;
                        $options['choices'] = $optionCategories;
                        if($Option->getDefaultCategory()){
                            $data = [];
                            foreach($Option->getDefaultCategory() as $defaultCategory){
                                $data[] = $defaultCategory;
                            }
                            $options['data'] = $data;
                        }
                        $form_type = EntityType::class;
                    } elseif ($type == Option::TEXT_TYPE) {
                        $form_type = Type\TextType::class;
                        $OptionCategories = $Option->getOptionCategories();
                        if(count($OptionCategories) > 0){
                            $options['attr'] = ['placeholder' => $OptionCategories[0]->getName(), 'data' => $OptionCategories[0]->getId()];
                        }
                    } elseif ($type == Option::TEXTAREA_TYPE) {
                        $form_type = Type\TextareaType::class;
                        $OptionCategories = $Option->getOptionCategories();
                        if(count($OptionCategories) > 0){
                            $options['attr'] = ['placeholder' => $OptionCategories[0]->getName(), 'data' => $OptionCategories[0]->getId()];
                        }
                    } elseif ($type == Option::DATE_TYPE) {
                        $form_type = Type\DateType::class;
                        $options['input'] = 'datetime';
                        $options['widget'] = 'single_text';
                        $options['format'] = 'yyyy-MM-dd';
                        $options['placeholder'] = ['year' => '----', 'month' => '--', 'day' => '--'];
                        $options['attr'] = [
                            'class' => 'datetimepicker-input',
                            'data-toggle' => 'datetimepicker',
                        ];
                        $OptionCategories = $Option->getOptionCategories();
                    } elseif ($type == Option::NUMBER_TYPE) {
                        $form_type = Type\IntegerType::class;
                        $options['attr'] = ['class' => 'number','maxlength' => $this->eccubeConfig['eccube_int_len']];
                        $OptionCategories = $Option->getOptionCategories();
                        if(count($OptionCategories) > 0){
                            $options['attr']['placeholder'] = $OptionCategories[0]->getName();
                            $options['attr']['data'] = $OptionCategories[0]->getId();
                        }
                        $options['constraints'][] = new Assert\Regex(['pattern' => '/^-{0,1}\d+$/']);
                        $min = $Option->getRequireMin();
                        $max = $Option->getRequireMax();
                        if(strlen($min) > 0){
                            $options['attr']['min'] = $min;
                            $options['constraints'][] = new Assert\GreaterThanOrEqual(['value' => $min]);
                        }
                        if(strlen($max) > 0){
                            $options['attr']['max'] = $max;
                            $options['constraints'][] = new Assert\LessThanOrEqual(['value' => $max]);
                        }
                    }
                    $builder->add('productoption' . $Option->getId(), $form_type, $options);
                }
            }
        }

        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) use($build_options){
            $form = $event->getForm();

            $Product = $build_options['product'];

            $ProductOptions = $this->productOptionRepository->getListByProduct($Product);

            if (is_array($ProductOptions)) {
                foreach ($ProductOptions as $ProductOption) {
                    $Option = $ProductOption->getOption();
                    if($Option->getType() == Option::CHECKBOX_TYPE){
                        $min = $Option->getRequireMin();
                        $max = $Option->getRequireMax();
                        $Options = $form['productoption'.$Option->getId()]->getData();
                        if($min == 1 && $max == 1){
                            if(count($Options) != 1){
                                $form['productoption'.$Option->getId()]->addError(new FormError(trans('productoption.option.cart.error.check')));
                            }
                        }elseif($min == $max && strlen($min) != 0){
                            if(count($Options) != $min){
                                $form['productoption'.$Option->getId()]->addError(new FormError(trans('productoption.option.cart.error.min',['%num%'=> $min])));
                            }
                        }else{
                            if($min > 0){
                                if(count($Options) < $min){
                                    $form['productoption'.$Option->getId()]->addError(new FormError(trans('productoption.option.cart.error.max',['%num%' => $min])));
                                }
                            }
                            if($max > 0){
                                if(count($Options) > $max){
                                    $form['productoption'.$Option->getId()]->addError(new FormError(trans('productoption.option.cart.error.limit',['%num%' => $max])));
                                }
                            }
                        }
                    }
                }
            }
        });
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return AddCartType::class;
    }

    public static function getExtendedTypes(): iterable
    {
        return [AddCartType::class];
    }
}
