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

use Plugin\ProductOption\Entity\Option;
use Plugin\ProductOption\Entity\ProductOption;
use Plugin\ProductOption\Repository\OptionRepository;
use Plugin\ProductOption\Repository\ProductOptionRepository;
use Plugin\ProductOption\Form\Type\Admin\ProductOptionType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ProductOptionController extends \Eccube\Controller\AbstractController
{

    /**
     * @var ProductRepository
     */
    private $productRepository;

    /**
     * @var OptionRepository
     */
    private $optionRepository;

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
    public function __construct(
            \Eccube\Repository\ProductRepository $productRepository,
            OptionRepository $optionRepository,
            ProductOptionRepository $productOptionRepository
            )
    {
        $this->productRepository = $productRepository;
        $this->optionRepository = $optionRepository;
        $this->productOptionRepository = $productOptionRepository;
    }

    /**
     * @Route("/%eccube_admin_route%/product/product/option/{id}", requirements={"id" = "\d+"} , name="admin_product_product_option")
     * @Template("@ProductOption/admin/Product/product_option.twig")
     */
    public function index(Request $request, $id)
    {

        $arrType[Option::SELECT_TYPE] = trans("productoption.option.type.select");
        $arrType[Option::RADIO_TYPE] = trans("productoption.option.type.radio");
        $arrType[Option::CHECKBOX_TYPE] = trans("productoption.option.type.checkbox");
        $arrType[Option::TEXT_TYPE] = trans("productoption.option.type.text");
        $arrType[Option::TEXTAREA_TYPE] = trans("productoption.option.type.textarea");
        $arrType[Option::DATE_TYPE] = trans("productoption.option.type.date");
        $arrType[Option::NUMBER_TYPE] = trans("productoption.option.type.number");

        $Product = $this->productRepository->find($id);

        if (!$Product) {
            throw new NotFoundHttpException();
        }

        $Options = $this->optionRepository->getList();

        foreach ($Options as $Option) {
            $ProductOption = new ProductOption();
            $ProductOption->setProduct($Product);
            $ProductOption->setOption($Option);
            if ($this->productOptionRepository->isExist($Product, $Option))
                $ProductOption->setChecked(true);
            $data[] = $ProductOption;
        }

        $builder = $this->formFactory->createBuilder();
        $arrLine = $builder->add('product_options', Type\CollectionType::class, [
                    'entry_type' => ProductOptionType::class,
                    'allow_add' => true,
                    'allow_delete' => true,
                    'error_bubbling' => false,
                    'data' => $data,
                ])
                ->getForm()
                ->createView();

        return [
                'optionForm' => $arrLine,
                'Product' => $Product,
                'Options' => $Options,
                'arrType' => $arrType,
        ];
    }

    /**
     * @Route("/%eccube_admin_route%/product/product/option/{id}/edit", requirements={"id" = "\d+"}, name="admin_product_product_option_edit")
     */
    public function edit(Request $request, $id)
    {
        $Product = $this->productRepository->find($id);

        if (!$Product) {
            throw new NotFoundHttpException();
        }

        $builder = $this->formFactory->createBuilder();
        $form = $builder->add('product_options', Type\CollectionType::class, [
                    'entry_type' => ProductOptionType::class,
                    'allow_add' => true,
                    'allow_delete' => true,
                    'error_bubbling' => false,
                ])
                ->getForm();

        if ($request->getMethod() === 'POST') {
            $form->handleRequest($request);
                if ($form->isSubmitted()) {

                // 一旦クリア
                $currentProductOptions = $Product->getProductOptions();
                foreach ($currentProductOptions as $currentProductOption) {
                    $this->entityManager->remove($currentProductOption);
                }
                $this->entityManager->flush();

                $ProductOptions = $form->get('product_options');
                foreach ($ProductOptions as $formData) {
                    if ($formData->get('checked')->getData()) {
                        if ($formData->isValid()) {
                            $ProductOption = new ProductOption();
                            $ProductOption->setProduct($Product);
                            $option_id = $formData->get('option_id')->getData();
                            $Option = $this->optionRepository
                                    ->find($option_id);
                            $ProductOption->setOption($Option);
                            $this->productOptionRepository->save($ProductOption);
                        }
                    }
                }

                $this->entityManager->flush();

                $this->addSuccess('admin.product.product_option.complete', 'admin');
            }
        }

        return $this->redirectToRoute('admin_product_product_option_rank', ['id' => $id]);
    }
}
