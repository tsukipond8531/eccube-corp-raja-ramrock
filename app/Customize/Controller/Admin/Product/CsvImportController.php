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

namespace Customize\Controller\Admin\Product;

use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Eccube\Common\Constant;
use Eccube\Controller\Admin\AbstractCsvImportController;
use Eccube\Entity\BaseInfo;
use Eccube\Entity\Category;
use Eccube\Entity\Product;
use Eccube\Entity\ProductCategory;
use Eccube\Entity\ProductClass;
use Eccube\Entity\ProductImage;
use Eccube\Entity\ProductStock;
use Eccube\Entity\ProductTag;
use Eccube\Form\Type\Admin\CsvImportType;
use Eccube\Repository\BaseInfoRepository;
use Eccube\Repository\CategoryRepository;
use Eccube\Repository\ClassCategoryRepository;
use Eccube\Repository\DeliveryDurationRepository;
use Eccube\Repository\Master\ProductStatusRepository;
use Eccube\Repository\Master\SaleTypeRepository;
use Eccube\Repository\ProductImageRepository;
use Eccube\Repository\ProductRepository;
use Eccube\Repository\TagRepository;
use Eccube\Repository\TaxRuleRepository;
use Eccube\Service\CsvImportService;
use Eccube\Util\CacheUtil;
use Eccube\Util\StringUtil;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Validator\ValidatorInterface;

use Eccube\Entity\Customer;
use Eccube\Entity\Order;
use Eccube\Entity\Master\Pref;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Eccube\Entity\Master\CustomerStatus;
use Eccube\Entity\Shipping;
use Eccube\Entity\Payment;
use Eccube\Entity\OrderItem;
use Eccube\Entity\Master\OrderItemType;
use Eccube\Entity\Master\OrderStatus;
use Eccube\Entity\Master\DeviceType;
use Eccube\Entity\Master\RoundingType;
use Eccube\Entity\Master\TaxType;
use Eccube\Entity\Master\TaxDisplayType;
use Eccube\Repository\CustomerRepository;
use Eccube\Repository\Master\CustomerStatusRepository;
use Eccube\Repository\Master\PrefRepository;

use Eccube\Repository\OrderRepository;
use Eccube\Repository\PaymentRepository;
use Eccube\Repository\Master\OrderItemTypeRepository;
use Eccube\Repository\DeliveryRepository;
use Eccube\Repository\Master\DeviceTypeRepository;
use Eccube\Repository\Master\RoundingTypeRepository;
use Eccube\Repository\Master\TaxTypeRepository;
use Eccube\Repository\Master\TaxDisplayTypeRepository;
use Eccube\Repository\Master\OrderStatusRepository;

use Eccube\Entity\Delivery;
use Eccube\Entity\MailHistory;

class CsvImportController extends AbstractCsvImportController
{
    /**
     * @var DeliveryDurationRepository
     */
    protected $deliveryDurationRepository;

    /**
     * @var SaleTypeRepository
     */
    protected $saleTypeRepository;

    /**
     * @var TagRepository
     */
    protected $tagRepository;

    /**
     * @var CategoryRepository
     */
    protected $categoryRepository;

    /**
     * @var ClassCategoryRepository
     */
    protected $classCategoryRepository;

    /**
     * @var ProductImageRepository
     */
    protected $productImageRepository;

    /**
     * @var ProductStatusRepository
     */
    protected $productStatusRepository;

    /**
     * @var ProductRepository
     */
    protected $productRepository;

    /**
     * @var CustomerRepository
     */
    protected $customerRepository;

    /**
     * @var CustomerStatusRepository
     */
    protected $customerStatusRepository;

    /**
     * @var PrefRepository
     */
    protected $prefRepository;

    /**
     * @var OrderRepository
     */
    protected $orderRepository;

    /**
     * @var PaymentRepository
     */
    protected $paymentRepository;

    /**
     * @var OrderItemTypeRepository
     */
    protected $orderItemTypeRepository;

    /**
     * @var DeliveryRepository
     */
    protected $deliveryRepository;

    /**
     * @var DeviceTypeRepository
     */
    protected $deviceTypeRepository;

    /**
     * @var RoundingTypeRepository
     */
    protected $roundingTypeRepository;

    /**
     * @var TaxTypeRepository
     */
    protected $taxTypeRepository;

    /**
     * @var TaxDisplayTypeRepository
     */
    protected $taxDisplayTypeRepository;

    /**
     * @var OrderStatusRepository
     */
    protected $orderStatusRepository;

    /**
     * @var TaxRuleRepository
     */
    private $taxRuleRepository;

    /**
     * @var BaseInfo
     */
    protected $BaseInfo;

    /**
     * @var EncoderFactoryInterface
     */
    protected $encoderFactory;

    /**
     * @var ValidatorInterface
     */
    protected $validator;

    private $errors = [];

    protected $isSplitCsv = false;

    protected $csvFileNo = 1;

    protected $currentLineNo = 1;

    /**
     * CsvImportController constructor.
     *
     * @param DeliveryDurationRepository $deliveryDurationRepository
     * @param SaleTypeRepository $saleTypeRepository
     * @param TagRepository $tagRepository
     * @param CategoryRepository $categoryRepository
     * @param ClassCategoryRepository $classCategoryRepository
     * @param ProductImageRepository $productImageRepository
     * @param ProductStatusRepository $productStatusRepository
     * @param ProductRepository $productRepository
     * @param TaxRuleRepository $taxRuleRepository
     * @param BaseInfoRepository $baseInfoRepository
     * @param ValidatorInterface $validator
     * @param EncoderFactoryInterface $encoderFactory
     *
     * @throws \Exception
     */
    public function __construct(
        DeliveryDurationRepository $deliveryDurationRepository,
        SaleTypeRepository $saleTypeRepository,
        TagRepository $tagRepository,
        CategoryRepository $categoryRepository,
        ClassCategoryRepository $classCategoryRepository,
        ProductImageRepository $productImageRepository,
        ProductStatusRepository $productStatusRepository,
        ProductRepository $productRepository,
        TaxRuleRepository $taxRuleRepository,
        BaseInfoRepository $baseInfoRepository,
        CustomerRepository $customerRepository,
        CustomerStatusRepository $customerStatusRepository,
        PrefRepository $prefRepository,
        OrderRepository $orderRepository,
        PaymentRepository $paymentRepository,
        OrderItemTypeRepository $orderItemTypeRepository,
        DeliveryRepository $deliveryRepository,
        DeviceTypeRepository $deviceTypeRepository,
        RoundingTypeRepository $roundingTypeRepository,
        TaxTypeRepository $taxTypeRepository,
        TaxDisplayTypeRepository $taxDisplayTypeRepository,
        OrderStatusRepository $orderStatusRepository,
        EncoderFactoryInterface $encoderFactory,
        ValidatorInterface $validator
    ) {
        $this->deliveryDurationRepository = $deliveryDurationRepository;
        $this->saleTypeRepository = $saleTypeRepository;
        $this->tagRepository = $tagRepository;
        $this->categoryRepository = $categoryRepository;
        $this->classCategoryRepository = $classCategoryRepository;
        $this->productImageRepository = $productImageRepository;
        $this->productStatusRepository = $productStatusRepository;
        $this->productRepository = $productRepository;
        $this->taxRuleRepository = $taxRuleRepository;
        $this->customerRepository = $customerRepository;
        $this->customerStatusRepository = $customerStatusRepository;
        $this->prefRepository = $prefRepository;

        $this->orderRepository = $orderRepository;
        $this->paymentRepository = $paymentRepository;
        $this->orderItemTypeRepository = $orderItemTypeRepository;
        $this->deliveryRepository = $deliveryRepository;
        $this->deviceTypeRepository = $deviceTypeRepository;
        $this->roundingTypeRepository = $roundingTypeRepository;
        $this->taxTypeRepository = $taxTypeRepository;
        $this->taxDisplayTypeRepository = $taxDisplayTypeRepository;
        $this->orderStatusRepository = $orderStatusRepository;

        $this->BaseInfo = $baseInfoRepository->get();
        $this->validator = $validator;
        $this->encoderFactory = $encoderFactory;
    }

    /**
     * 商品登録CSVアップロード
     *
     * @Route("/%eccube_admin_route%/product/product_csv_upload", name="admin_product_csv_import", methods={"GET", "POST"})
     * @Template("@admin/Product/csv_product.twig")
     *
     * @return array
     *
     * @throws \Doctrine\DBAL\ConnectionException
     * @throws \Doctrine\ORM\NoResultException
     */
    public function csvProduct(Request $request, CacheUtil $cacheUtil)
    {
        $this->session->remove('SESSION_TEMP_CUSTOMER_LOOP_ID');
        $this->session->remove('SESSION_TEMP_ORDER_LOOP_ID');

        $form = $this->formFactory->createBuilder(CsvImportType::class)->getForm();
        $headers = $this->getProductCsvHeader();
        if ('POST' === $request->getMethod()) {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $this->isSplitCsv = $form['is_split_csv']->getData();
                $this->csvFileNo = $form['csv_file_no']->getData();

                $formFile = $form['import_file']->getData();
                if (!empty($formFile)) {
                    log_info('商品CSV登録開始');
                    $data = $this->getImportData($formFile);
                    if ($data === false) {
                        $this->addErrors(trans('admin.common.csv_invalid_format'));

                        return $this->renderWithError($form, $headers, false);
                    }
                    $getId = function ($item) {
                        return $item['id'];
                    };
                    $requireHeader = array_keys(array_map($getId, array_filter($headers, function ($value) {
                        return $value['required'];
                    })));

                    $columnHeaders = $data->getColumnHeaders();

                    // if (count(array_diff($requireHeader, $columnHeaders)) > 0) {
                    //     $this->addErrors(trans('admin.common.csv_invalid_format'));

                    //     return $this->renderWithError($form, $headers, false);
                    // }

                    $size = count($data);

                    if ($size < 1) {
                        $this->addErrors(trans('admin.common.csv_invalid_no_data'));

                        return $this->renderWithError($form, $headers, false);
                    }

                    $headerSize = count($columnHeaders);
                    $headerByKey = array_flip(array_map($getId, $headers));
                    $deleteImages = [];

                    $this->entityManager->getConfiguration()->setSQLLogger(null);
                    $this->entityManager->getConnection()->beginTransaction();
                    // CSVファイルの登録処理
                    foreach ($data as $row) {
                        $line = $this->convertLineNo($data->key() + 1);
                        $this->currentLineNo = $line;
                        if ($headerSize != count($row)) {
                            $message = trans('admin.common.csv_invalid_format_line', ['%line%' => $line]);
                            $this->addErrors($message);

                            return $this->renderWithError($form, $headers);
                        }

                        if (!isset($row["商品名"]) || StringUtil::isBlank($row["商品名"])) {
                            $Product = new Product();
                            $this->entityManager->persist($Product);
                        } else {
                            if ( !is_null($row["商品名"]) ) {
                                $Product = $this->productRepository->findOneBy(['name' => $row["商品名"]]);
                                if (!$Product) {
                                    $Product = new Product();
                                    $this->entityManager->persist($Product);
                                }
                            } else {
                                $message = trans('admin.common.csv_invalid_not_found', ['%line%' => $line, '%name%' => "商品名"]);
                                $this->addErrors($message);

                                return $this->renderWithError($form, $headers);
                            }

                            if (isset($row["削除フラグ"])) {
                                if (StringUtil::isNotBlank($row["削除フラグ"]) && $row["削除フラグ"] == (string) Constant::ENABLED) {
                                    // 商品を物理削除
                                    $deleteImages[] = $Product->getProductImage();

                                    try {
                                        $this->productRepository->delete($Product);
                                        $this->entityManager->flush();

                                        continue;
                                    } catch (ForeignKeyConstraintViolationException $e) {
                                        $message = trans('admin.common.csv_invalid_foreign_key', ['%line%' => $line, '%name%' => $Product->getName()]);
                                        $this->addErrors($message);

                                        return $this->renderWithError($form, $headers);
                                    }
                                }
                            }
                        }

                        if (StringUtil::isBlank($row["表示ステータス(公開・非公開)"])) {
                            $message = trans('admin.common.csv_invalid_required', ['%line%' => $line, '%name%' => "表示ステータス(公開・非公開)"]);
                            $this->addErrors($message);
                        } else {
                            if (preg_match('/^\d+$/', $row["表示ステータス(公開・非公開)"])) {
                                $ProductStatus = $this->productStatusRepository->find($row["表示ステータス(公開・非公開)"]);
                                if (!$ProductStatus) {
                                    $message = trans('admin.common.csv_invalid_not_found', ['%line%' => $line, '%name%' => "表示ステータス(公開・非公開)"]);
                                    $this->addErrors($message);
                                } else {
                                    $Product->setStatus($ProductStatus);
                                }
                            } else {
                                $message = trans('admin.common.csv_invalid_not_found', ['%line%' => $line, '%name%' => "表示ステータス(公開・非公開)"]);
                                $this->addErrors($message);
                            }
                        }

                        if (StringUtil::isBlank($row["商品名"])) {
                            $message = trans('admin.common.csv_invalid_not_found', ['%line%' => $line, '%name%' => "商品名"]);
                            $this->addErrors($message);

                            return $this->renderWithError($form, $headers);
                        } else {
                            $Product->setName(StringUtil::trimAll($row["商品名"]));
                        }

                        if (isset($row[$headerByKey['note']])) {
                            if (StringUtil::isNotBlank($row[$headerByKey['note']])) {
                                $Product->setNote(StringUtil::trimAll($row[$headerByKey['note']]));
                            } else {
                                $Product->setNote(null);
                            }
                        }

                        if (isset($row["一覧-メインコメント"])) {
                            if (StringUtil::isNotBlank($row["一覧-メインコメント"])) {
                                $Product->setDescriptionList(StringUtil::trimAll($row["一覧-メインコメント"]));
                            } else {
                                $Product->setDescriptionList(null);
                            }
                        }

                        if (isset($row["詳細-メインコメント"])) {
                            if (StringUtil::isNotBlank($row["詳細-メインコメント"])) {
                                if (mb_strlen($row["詳細-メインコメント"]) > $this->eccubeConfig['eccube_ltext_len']) {
                                    $message = trans('admin.common.csv_invalid_description_detail_upper_limit', [
                                        '%line%' => $line,
                                        '%name%' => "詳細-メインコメント",
                                        '%max%' => $this->eccubeConfig['eccube_ltext_len'],
                                    ]);
                                    $this->addErrors($message);

                                    return $this->renderWithError($form, $headers);
                                } else {
                                    $Product->setDescriptionDetail(StringUtil::trimAll($row["詳細-メインコメント"]));
                                }
                            } else {
                                $Product->setDescriptionDetail(null);
                            }
                        }

                        if (isset($row["検索ワード(コメント3)"])) {
                            if (StringUtil::isNotBlank($row["検索ワード(コメント3)"])) {
                                $Product->setSearchWord(StringUtil::trimAll($row["検索ワード(コメント3)"]));
                            } else {
                                $Product->setSearchWord(null);
                            }
                        }

                        if (isset($row["備考欄(SHOP専用)"])) {
                            if (StringUtil::isNotBlank($row["備考欄(SHOP専用)"])) {
                                $Product->setFreeArea(StringUtil::trimAll($row["備考欄(SHOP専用)"]));
                            } else {
                                $Product->setFreeArea(null);
                            }
                        }

                        // 商品画像登録
                        $this->createProductImage($row, $Product, $data, $headerByKey);

                        $this->entityManager->flush();

                        // 商品カテゴリ登録
                        $this->createProductCategory($row, $Product, $data, $headerByKey);

                        //タグ登録
                        $this->createProductTag($row, $Product, $data, $headerByKey);

                        // 商品規格が存在しなければ新規登録
                        /** @var ProductClass[] $ProductClasses */
                        $ProductClasses = $Product->getProductClasses();
                        if ($ProductClasses->count() < 1) {
                            // 規格分類1(ID)がセットされていると規格なし商品、規格あり商品を作成
                            $ProductClassOrg = $this->createProductClass($row, $Product, $data, $headerByKey);
                            if ($this->BaseInfo->isOptionProductDeliveryFee()) {
                                if (isset($row[$headerByKey['delivery_fee']]) && StringUtil::isNotBlank($row[$headerByKey['delivery_fee']])) {
                                    $deliveryFee = str_replace(',', '', $row[$headerByKey['delivery_fee']]);
                                    $errors = $this->validator->validate($deliveryFee, new GreaterThanOrEqual(['value' => 0]));
                                    if ($errors->count() === 0) {
                                        $ProductClassOrg->setDeliveryFee($deliveryFee);
                                    } else {
                                        $message = trans('admin.common.csv_invalid_greater_than_zero', ['%line%' => $line, '%name%' => $headerByKey['delivery_fee']]);
                                        $this->addErrors($message);
                                    }
                                }
                            }

                            // 商品別税率機能が有効の場合に税率を更新
                            if ($this->BaseInfo->isOptionProductTaxRule()) {
                                if (isset($row[$headerByKey['tax_rate']]) && StringUtil::isNotBlank($row[$headerByKey['tax_rate']])) {
                                    $taxRate = $row[$headerByKey['tax_rate']];
                                    $errors = $this->validator->validate($taxRate, new GreaterThanOrEqual(['value' => 0]));
                                    if ($errors->count() === 0) {
                                        if ($ProductClassOrg->getTaxRule()) {
                                            // 商品別税率の設定があれば税率を更新
                                            $ProductClassOrg->getTaxRule()->setTaxRate($taxRate);
                                        } else {
                                            // 商品別税率の設定がなければ新規作成
                                            $TaxRule = $this->taxRuleRepository->newTaxRule();
                                            $TaxRule->setTaxRate($taxRate);
                                            $TaxRule->setApplyDate(new \DateTime());
                                            $TaxRule->setProduct($Product);
                                            $TaxRule->setProductClass($ProductClassOrg);
                                            $ProductClassOrg->setTaxRule($TaxRule);
                                        }
                                    } else {
                                        $message = trans('admin.common.csv_invalid_greater_than_zero', ['%line%' => $line, '%name%' => $headerByKey['tax_rate']]);
                                        $this->addErrors($message);
                                    }
                                } else {
                                    // 税率の入力がなければ税率の設定を削除
                                    if ($ProductClassOrg->getTaxRule()) {
                                        $this->taxRuleRepository->delete($ProductClassOrg->getTaxRule());
                                        $ProductClassOrg->setTaxRule(null);
                                    }
                                }
                            }

                            if (isset($row["親規格分類名"]) && StringUtil::isNotBlank($row["親規格分類名"])) {
                                if (isset($row["規格分類名"]) && $row["親規格分類名"] == $row["規格分類名"]) {
                                    $message = trans('admin.common.csv_invalid_not_same', [
                                        '%line%' => $line,
                                        '%name1%' => "親規格分類名",
                                        '%name2%' => "規格分類名",
                                    ]);
                                    $this->addErrors($message);
                                } else {
                                    // 商品規格あり
                                    // 規格分類あり商品を作成
                                    $ProductClass = clone $ProductClassOrg;
                                    $ProductStock = clone $ProductClassOrg->getProductStock();

                                    // 規格分類1、規格分類2がnullであるデータを非表示
                                    $ProductClassOrg->setVisible(false);

                                    // 規格分類1、2をそれぞれセットし作成
                                    $ClassCategory1 = null;
                                    if ( !is_null($row["親規格分類名"])) {
                                        $ClassCategory1 = $this->classCategoryRepository->findOneBy(['name' => $row["親規格分類名"]]);
                                        if (!$ClassCategory1) {
                                            $message = trans('admin.common.csv_invalid_not_found', ['%line%' => $line, '%name%' => "親規格分類名"]);
                                            $this->addErrors($message);
                                        } else {
                                            $ProductClass->setClassCategory1($ClassCategory1);
                                        }
                                    } else {
                                    }

                                    if (isset($row["規格分類名"]) && StringUtil::isNotBlank($row["規格分類名"])) {
                                        if ( !is_null($row["規格分類名"]) ) {
                                            $ClassCategory2 = $this->classCategoryRepository->findOneBy(['name' => $row["規格分類名"]]);
                                            if (!$ClassCategory2) {
                                                $message = trans('admin.common.csv_invalid_not_found', ['%line%' => $line, '%name%' => "規格分類名"]);
                                                $this->addErrors($message);
                                            } else {
                                                if ($ClassCategory1 &&
                                                    ($ClassCategory1->getClassName()->getId() == $ClassCategory2->getClassName()->getId())
                                                ) {
                                                    $message = trans('admin.common.csv_invalid_not_same', ['%line%' => $line, '%name1%' => "親規格分類名", '%name2%' => "規格分類名"]);
                                                    $this->addErrors($message);
                                                } else {
                                                    $ProductClass->setClassCategory2($ClassCategory2);
                                                }
                                            }
                                        } else {
                                            $message = trans('admin.common.csv_invalid_not_found', ['%line%' => $line, '%name%' => "規格分類名"]);
                                            $this->addErrors($message);
                                        }
                                    }
                                    $ProductClass->setProductStock($ProductStock);
                                    $ProductStock->setProductClass($ProductClass);

                                    $this->entityManager->persist($ProductClass);
                                    $this->entityManager->persist($ProductStock);
                                }
                            } else {
                                // if (isset($row["規格分類名"]) && StringUtil::isNotBlank($row["規格分類名"])) {
                                //     $message = trans('admin.common.csv_invalid_not_found', ['%line%' => $line, '%name%' => "規格分類名"]);
                                //     $this->addErrors($message);
                                // }
                            }
                        } else {
                            // 商品規格の更新
                            $flag = false;
                            $classCategoryId1 = StringUtil::isBlank($row["親規格分類名"]) ? null : $row["親規格分類名"];
                            $classCategoryId2 = StringUtil::isBlank($row["規格分類名"]) ? null : $row["規格分類名"];

                            foreach ($ProductClasses as $pc) {
                                $classCategory1 = is_null($pc->getClassCategory1()) ? null : $pc->getClassCategory1()->getName();
                                $classCategory2 = is_null($pc->getClassCategory2()) ? null : $pc->getClassCategory2()->getName();

                                // 登録されている商品規格を更新
                                if ($classCategory1 == $classCategoryId1 &&
                                    $classCategory2 == $classCategoryId2
                                ) {
                                    $this->updateProductClass($row, $Product, $pc, $data, $headerByKey);

                                    if ($this->BaseInfo->isOptionProductDeliveryFee()) {
                                        if (isset($row[$headerByKey['delivery_fee']]) && StringUtil::isNotBlank($row[$headerByKey['delivery_fee']])) {
                                            $deliveryFee = str_replace(',', '', $row[$headerByKey['delivery_fee']]);
                                            $errors = $this->validator->validate($deliveryFee, new GreaterThanOrEqual(['value' => 0]));
                                            if ($errors->count() === 0) {
                                                $pc->setDeliveryFee($deliveryFee);
                                            } else {
                                                $message = trans('admin.common.csv_invalid_greater_than_zero', ['%line%' => $line, '%name%' => $headerByKey['delivery_fee']]);
                                                $this->addErrors($message);
                                            }
                                        }
                                    }

                                    // 商品別税率機能が有効の場合に税率を更新
                                    if ($this->BaseInfo->isOptionProductTaxRule()) {
                                        if (isset($row[$headerByKey['tax_rate']]) && StringUtil::isNotBlank($row[$headerByKey['tax_rate']])) {
                                            $taxRate = $row[$headerByKey['tax_rate']];
                                            $errors = $this->validator->validate($taxRate, new GreaterThanOrEqual(['value' => 0]));
                                            if ($errors->count() === 0) {
                                                if ($pc->getTaxRule()) {
                                                    // 商品別税率の設定があれば税率を更新
                                                    $pc->getTaxRule()->setTaxRate($taxRate);
                                                } else {
                                                    // 商品別税率の設定がなければ新規作成
                                                    $TaxRule = $this->taxRuleRepository->newTaxRule();
                                                    $TaxRule->setTaxRate($taxRate);
                                                    $TaxRule->setApplyDate(new \DateTime());
                                                    $TaxRule->setProduct($Product);
                                                    $TaxRule->setProductClass($pc);
                                                    $pc->setTaxRule($TaxRule);
                                                }
                                            } else {
                                                $message = trans('admin.common.csv_invalid_greater_than_zero', ['%line%' => $line, '%name%' => $headerByKey['tax_rate']]);
                                                $this->addErrors($message);
                                            }
                                        } else {
                                            // 税率の入力がなければ税率の設定を削除
                                            if ($pc->getTaxRule()) {
                                                $this->taxRuleRepository->delete($pc->getTaxRule());
                                                $pc->setTaxRule(null);
                                            }
                                        }
                                    }

                                    $flag = true;
                                    break;
                                }
                            }

                            // 商品規格を登録
                            if (!$flag) {
                                $pc = $ProductClasses[0];
                                if ($pc->getClassCategory1() == null &&
                                    $pc->getClassCategory2() == null
                                ) {
                                    // 規格分類1、規格分類2がnullであるデータを非表示
                                    $pc->setVisible(false);
                                }

                                if (isset($row["親規格分類名"]) && isset($row["規格分類名"])
                                    && $row["親規格分類名"] == $row["規格分類名"]) {
                                    $message = trans('admin.common.csv_invalid_not_same', [
                                        '%line%' => $line,
                                        '%name1%' => "親規格分類名",
                                        '%name2%' => "規格分類名",
                                    ]);
                                    $this->addErrors($message);
                                } else {
                                    // 必ず規格分類1がセットされている
                                    // 規格分類1、2をそれぞれセットし作成
                                    $ClassCategory1 = null;
                                    if ( !is_null($classCategoryId1) ) {
                                        $ClassCategory1 = $this->classCategoryRepository->findOneBy(['name' => $classCategoryId1]);
                                        if (!$ClassCategory1) {
                                            $message = trans('admin.common.csv_invalid_not_found', ['%line%' => $line, '%name%' => "親規格分類名"]);
                                            $this->addErrors($message);
                                        }
                                    } else {
                                    }

                                    $ClassCategory2 = null;
                                    if (isset($row["規格分類名"]) && StringUtil::isNotBlank($row["規格分類名"])) {
                                        if ($pc->getClassCategory1() != null && $pc->getClassCategory2() == null) {
                                            $message = trans('admin.common.csv_invalid_can_not', ['%line%' => $line, '%name%' => "規格分類名"]);
                                            $this->addErrors($message);
                                        } else {
                                            if ( !is_null($classCategoryId2) ) {
                                                $ClassCategory2 = $this->classCategoryRepository->findOneBy(['name' => $classCategoryId2]);
                                                if (!$ClassCategory2) {
                                                    $message = trans('admin.common.csv_invalid_not_found', ['%line%' => $line, '%name%' => "規格分類名"]);
                                                    $this->addErrors($message);
                                                } else {
                                                    if ($ClassCategory1 &&
                                                        ($ClassCategory1->getClassName()->getId() == $ClassCategory2->getClassName()->getId())
                                                    ) {
                                                        $message = trans('admin.common.csv_invalid_not_same', [
                                                            '%line%' => $line,
                                                            '%name1%' => "親規格分類名",
                                                            '%name2%' => "規格分類名",
                                                        ]);
                                                        $this->addErrors($message);
                                                    }
                                                }
                                            } else {
                                                $message = trans('admin.common.csv_invalid_not_found', ['%line%' => $line, '%name%' => "規格分類名"]);
                                                $this->addErrors($message);
                                            }
                                        }
                                    } else {
                                        if ($pc->getClassCategory1() != null && $pc->getClassCategory2() != null) {
                                            $message = trans('admin.common.csv_invalid_required', ['%line%' => $line, '%name%' => "規格分類名"]);
                                            $this->addErrors($message);
                                        }
                                    }
                                    $ProductClass = $this->createProductClass($row, $Product, $data, $headerByKey, $ClassCategory1, $ClassCategory2);

                                    if ($this->BaseInfo->isOptionProductDeliveryFee()) {
                                        if (isset($row[$headerByKey['delivery_fee']]) && StringUtil::isNotBlank($row[$headerByKey['delivery_fee']])) {
                                            $deliveryFee = str_replace(',', '', $row[$headerByKey['delivery_fee']]);
                                            $errors = $this->validator->validate($deliveryFee, new GreaterThanOrEqual(['value' => 0]));
                                            if ($errors->count() === 0) {
                                                $ProductClass->setDeliveryFee($deliveryFee);
                                            } else {
                                                $message = trans('admin.common.csv_invalid_greater_than_zero', ['%line%' => $line, '%name%' => $headerByKey['delivery_fee']]);
                                                $this->addErrors($message);
                                            }
                                        }
                                    }

                                    // 商品別税率機能が有効の場合に税率を更新
                                    if ($this->BaseInfo->isOptionProductTaxRule()) {
                                        if (isset($row[$headerByKey['tax_rate']]) && StringUtil::isNotBlank($row[$headerByKey['tax_rate']])) {
                                            $taxRate = $row[$headerByKey['tax_rate']];
                                            $errors = $this->validator->validate($taxRate, new GreaterThanOrEqual(['value' => 0]));
                                            if ($errors->count() === 0) {
                                                $TaxRule = $this->taxRuleRepository->newTaxRule();
                                                $TaxRule->setTaxRate($taxRate);
                                                $TaxRule->setApplyDate(new \DateTime());
                                                $TaxRule->setProduct($Product);
                                                $TaxRule->setProductClass($ProductClass);
                                                $ProductClass->setTaxRule($TaxRule);
                                            } else {
                                                $message = trans('admin.common.csv_invalid_greater_than_zero', ['%line%' => $line, '%name%' => $headerByKey['tax_rate']]);
                                                $this->addErrors($message);
                                            }
                                        }
                                    }

                                    $Product->addProductClass($ProductClass);
                                }
                            }
                        }
                        if ($this->hasErrors()) {
                            return $this->renderWithError($form, $headers);
                        }
                        $this->entityManager->persist($Product);
                    }
                    $this->entityManager->flush();
                    $this->entityManager->getConnection()->commit();

                    // 画像ファイルの削除(commit後に削除させる)
                    foreach ($deleteImages as $images) {
                        /** @var ProductImage $image */
                        foreach ($images as $image) {
                            if ($this->productImageRepository->findOneBy(['file_name' => $image->getFileName()])) {
                                continue;
                            }
                            try {
                                $fs = new Filesystem();
                                $fs->remove($this->eccubeConfig['eccube_save_image_dir'].'/'.$image);
                            } catch (\Exception $e) {
                                // エラーが発生しても無視する
                            }
                        }
                    }

                    log_info('商品CSV登録完了');
                    if (!$this->isSplitCsv) {
                        $message = 'admin.common.csv_upload_complete';
                        $this->session->getFlashBag()->add('eccube.admin.success', $message);
                    }

                    $cacheUtil->clearDoctrineCache();
                }
            }
        }

        return $this->renderWithError($form, $headers);
    }

    /**
     * カテゴリ登録CSVアップロード
     *
     * @Route("/%eccube_admin_route%/product/category_csv_upload", name="admin_product_category_csv_import", methods={"GET", "POST"})
     * @Template("@admin/Product/csv_category.twig")
     */
    public function csvCategory(Request $request, CacheUtil $cacheUtil)
    {
        $form = $this->formFactory->createBuilder(CsvImportType::class)->getForm();

        $headers = $this->getCategoryCsvHeader();
        if ('POST' === $request->getMethod()) {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $formFile = $form['import_file']->getData();
                if (!empty($formFile)) {
                    log_info('カテゴリCSV登録開始');
                    $data = $this->getImportData($formFile);
                    if ($data === false) {
                        $this->addErrors(trans('admin.common.csv_invalid_format'));

                        return $this->renderWithError($form, $headers, false);
                    }

                    $getId = function ($item) {
                        return $item['id'];
                    };
                    $requireHeader = array_keys(array_map($getId, array_filter($headers, function ($value) {
                        return $value['required'];
                    })));

                    $headerByKey = array_flip(array_map($getId, $headers));

                    $columnHeaders = $data->getColumnHeaders();
                    if (count(array_diff($requireHeader, $columnHeaders)) > 0) {
                        $this->addErrors(trans('admin.common.csv_invalid_format'));

                        return $this->renderWithError($form, $headers, false);
                    }

                    $size = count($data);
                    if ($size < 1) {
                        $this->addErrors(trans('admin.common.csv_invalid_no_data'));

                        return $this->renderWithError($form, $headers, false);
                    }
                    $this->entityManager->getConfiguration()->setSQLLogger(null);
                    $this->entityManager->getConnection()->beginTransaction();
                    // CSVファイルの登録処理
                    foreach ($data as $row) {
                        /** @var $Category Category */
                        $Category = new Category();
                        if (isset($row[$headerByKey['id']]) && strlen($row[$headerByKey['id']]) > 0) {
                            if (!preg_match('/^\d+$/', $row[$headerByKey['id']])) {
                                $this->addErrors(($data->key() + 1).'行目のカテゴリIDが存在しません。');

                                return $this->renderWithError($form, $headers);
                            }
                            $Category = $this->categoryRepository->find($row[$headerByKey['id']]);
                            if (!$Category) {
                                $this->addErrors(($data->key() + 1).'行目の更新対象のカテゴリIDが存在しません。新規登録の場合は、カテゴリIDの値を空で登録してください。');

                                return $this->renderWithError($form, $headers);
                            }
                            if ($row[$headerByKey['id']] == $row[$headerByKey['parent_category_id']]) {
                                $this->addErrors(($data->key() + 1).'行目のカテゴリIDと親カテゴリIDが同じです。');

                                return $this->renderWithError($form, $headers);
                            }
                        }

                        if (isset($row[$headerByKey['category_del_flg']]) && StringUtil::isNotBlank($row[$headerByKey['category_del_flg']])) {
                            if (StringUtil::trimAll($row[$headerByKey['category_del_flg']]) == 1) {
                                if ($Category->getId()) {
                                    log_info('カテゴリ削除開始', [$Category->getId()]);
                                    try {
                                        $this->categoryRepository->delete($Category);
                                        log_info('カテゴリ削除完了', [$Category->getId()]);
                                    } catch (ForeignKeyConstraintViolationException $e) {
                                        log_info('カテゴリ削除エラー', [$Category->getId(), $e]);
                                        $message = trans('admin.common.delete_error_foreign_key', ['%name%' => $Category->getName()]);
                                        $this->addError($message, 'admin');

                                        return $this->renderWithError($form, $headers);
                                    }
                                }

                                continue;
                            }
                        }

                        if (!isset($row[$headerByKey['category_name']]) || StringUtil::isBlank($row[$headerByKey['category_name']])) {
                            $this->addErrors(($data->key() + 1).'行目のカテゴリ名が設定されていません。');

                            return $this->renderWithError($form, $headers);
                        } else {
                            $Category->setName(StringUtil::trimAll($row[$headerByKey['category_name']]));
                        }

                        $ParentCategory = null;
                        if (isset($row[$headerByKey['parent_category_id']]) && StringUtil::isNotBlank($row[$headerByKey['parent_category_id']])) {
                            if (!preg_match('/^\d+$/', $row[$headerByKey['parent_category_id']])) {
                                $this->addErrors(($data->key() + 1).'行目の親カテゴリIDが存在しません。');

                                return $this->renderWithError($form, $headers);
                            }

                            /** @var $ParentCategory Category */
                            $ParentCategory = $this->categoryRepository->find($row[$headerByKey['parent_category_id']]);
                            if (!$ParentCategory) {
                                $this->addErrors(($data->key() + 1).'行目の親カテゴリIDが存在しません。');

                                return $this->renderWithError($form, $headers);
                            }
                        }
                        $Category->setParent($ParentCategory);

                        // Level
                        if (isset($row['階層']) && StringUtil::isNotBlank($row['階層'])) {
                            if ($ParentCategory == null && $row['階層'] != 1) {
                                $this->addErrors(($data->key() + 1).'行目の親カテゴリIDが存在しません。');

                                return $this->renderWithError($form, $headers);
                            }
                            $level = StringUtil::trimAll($row['階層']);
                        } else {
                            $level = 1;
                            if ($ParentCategory) {
                                $level = $ParentCategory->getHierarchy() + 1;
                            }
                        }

                        $Category->setHierarchy($level);

                        if ($this->eccubeConfig['eccube_category_nest_level'] < $Category->getHierarchy()) {
                            $this->addErrors(($data->key() + 1).'行目のカテゴリが最大レベルを超えているため設定できません。');

                            return $this->renderWithError($form, $headers);
                        }

                        if ($this->hasErrors()) {
                            return $this->renderWithError($form, $headers);
                        }
                        $this->entityManager->persist($Category);
                        $this->categoryRepository->save($Category);
                    }

                    $this->entityManager->getConnection()->commit();
                    log_info('カテゴリCSV登録完了');
                    $message = 'admin.common.csv_upload_complete';
                    $this->session->getFlashBag()->add('eccube.admin.success', $message);

                    $cacheUtil->clearDoctrineCache();
                }
            }
        }

        return $this->renderWithError($form, $headers);
    }

    /**
     * アップロード用CSV雛形ファイルダウンロード
     *
     * @Route("/%eccube_admin_route%/product/csv_template/{type}", requirements={"type" = "\w+"}, name="admin_product_csv_template", methods={"GET"})
     *
     * @param $type
     *
     * @return StreamedResponse
     */
    public function csvTemplate(Request $request, $type)
    {
        if ($type == 'product') {
            $headers = $this->getProductCsvHeader();
            $filename = 'product.csv';
        } elseif ($type == 'category') {
            $headers = $this->getCategoryCsvHeader();
            $filename = 'category.csv';
        } else {
            throw new NotFoundHttpException();
        }

        return $this->sendTemplateResponse($request, array_keys($headers), $filename);
    }

    /**
     * 登録、更新時のエラー画面表示
     *
     * @param FormInterface $form
     * @param array $headers
     * @param bool $rollback
     *
     * @return array
     *
     * @throws \Doctrine\DBAL\ConnectionException
     */
    protected function renderWithError($form, $headers, $rollback = true)
    {
        if ($this->hasErrors()) {
            if ($rollback) {
                $this->entityManager->getConnection()->rollback();
            }
        }

        $this->removeUploadedFile();

        if ($this->isSplitCsv) {
            return $this->json([
                'success' => !$this->hasErrors(),
                'success_message' => trans('admin.common.csv_upload_line_success', [
                    '%from%' => $this->convertLineNo(2),
                    '%to%' => $this->currentLineNo, ]),
                'errors' => $this->errors,
                'error_message' => trans('admin.common.csv_upload_line_error', [
                    '%from%' => $this->convertLineNo(2), ]),
            ]);
        }

        return [
            'form' => $form->createView(),
            'headers' => $headers,
            'errors' => $this->errors,
        ];
    }

    /**
     * 商品画像の削除、登録
     *
     * @param $row
     * @param Product $Product
     * @param CsvImportService $data
     * @param $headerByKey
     */
    protected function createProductImage($row, Product $Product, $data, $headerByKey)
    {
        $images = [];

        if ( isset($row["一覧-メイン画像"]) && StringUtil::isNotBlank($row["一覧-メイン画像"]) ) {
            $images[] = $row["一覧-メイン画像"];
        }
        if ( isset($row["詳細-メイン画像"]) && StringUtil::isNotBlank($row["詳細-メイン画像"]) ) {
            $images[] = $row["詳細-メイン画像"];
        }
        if ( isset($row["詳細-メイン拡大画像"]) && StringUtil::isNotBlank($row["詳細-メイン拡大画像"]) ) {
            $images[] = $row["詳細-メイン拡大画像"];
        }

        if ( empty($images) ) {
            return;
        }

        $ProductImages = $Product->getProductImage();
        foreach ($ProductImages as $ProductImage) {
            $Product->removeProductImage($ProductImage);
            $this->entityManager->remove($ProductImage);
        }

        $sortNo = 1;

        $pattern = "/\\$|^.*.\.\\\.*|\/$|^.*.\.\/\.*/";
        foreach ($images as $image) {
            $fileName = StringUtil::trimAll($image);

            // 商品画像名のフォーマットチェック
            if (strlen($fileName) > 0 && preg_match($pattern, $fileName)) {
                $message = trans('admin.common.csv_invalid_image', ['%line%' => $data->key() + 1, '%name%' => $headerByKey['product_image']]);
                $this->addErrors($message);
            } else {
                // 空文字は登録対象外
                if (!empty($fileName)) {
                    $ProductImage = new ProductImage();
                    $ProductImage->setFileName($fileName);
                    $ProductImage->setProduct($Product);
                    $ProductImage->setSortNo($sortNo);

                    $Product->addProductImage($ProductImage);
                    $sortNo++;
                    $this->entityManager->persist($ProductImage);
                }
            }
        }
    }

    /**
     * 商品カテゴリの削除、登録
     *
     * @param $row
     * @param Product $Product
     * @param CsvImportService $data
     * @param $headerByKey
     */
    protected function createProductCategory($row, Product $Product, $data, $headerByKey)
    {
        if (!isset($row["商品ステータス名"])) {
            return;
        }
        // カテゴリの削除
        $ProductCategories = $Product->getProductCategories();
        foreach ($ProductCategories as $ProductCategory) {
            $Product->removeProductCategory($ProductCategory);
            $this->entityManager->remove($ProductCategory);
            $this->entityManager->flush();
        }

        if (StringUtil::isNotBlank($row["商品ステータス名"])) {
            // カテゴリの登録
            $categories = explode(',', $row["商品ステータス名"]);
            $sortNo = 1;
            $categoriesIdList = [];
            foreach ($categories as $category) {
                $line = $data->key() + 1;
                if ( !is_null($category) ) {
                    $Category = $this->categoryRepository->findOneBy(['name' => $category]);
                    if (!$Category) {
                        $message = trans('admin.common.csv_invalid_not_found_target', [
                            '%line%' => $line,
                            '%name%' => "商品ステータス名",
                            '%target_name%' => $category,
                        ]);
                        $this->addErrors($message);
                    } else {
                        foreach ($Category->getPath() as $ParentCategory) {
                            if (!isset($categoriesIdList[$ParentCategory->getId()])) {
                                $ProductCategory = $this->makeProductCategory($Product, $ParentCategory, $sortNo);
                                $this->entityManager->persist($ProductCategory);
                                $sortNo++;

                                $Product->addProductCategory($ProductCategory);
                                $categoriesIdList[$ParentCategory->getId()] = true;
                            }
                        }
                        if (!isset($categoriesIdList[$Category->getId()])) {
                            $ProductCategory = $this->makeProductCategory($Product, $Category, $sortNo);
                            $sortNo++;
                            $this->entityManager->persist($ProductCategory);
                            $Product->addProductCategory($ProductCategory);
                            $categoriesIdList[$Category->getId()] = true;
                        }
                    }
                } else {
                    $message = trans('admin.common.csv_invalid_not_found_target', [
                        '%line%' => $line,
                        '%name%' => "商品ステータス名",
                        '%target_name%' => $category,
                    ]);
                    $this->addErrors($message);
                }
            }
        }
    }

    /**
     * タグの登録
     *
     * @param array $row
     * @param Product $Product
     * @param CsvImportService $data
     */
    protected function createProductTag($row, Product $Product, $data, $headerByKey)
    {
        if (!isset($row[$headerByKey['product_tag']])) {
            return;
        }
        // タグの削除
        $ProductTags = $Product->getProductTag();
        foreach ($ProductTags as $ProductTag) {
            $Product->removeProductTag($ProductTag);
            $this->entityManager->remove($ProductTag);
        }

        if (StringUtil::isNotBlank($row[$headerByKey['product_tag']])) {
            // タグの登録
            $tags = explode(',', $row[$headerByKey['product_tag']]);
            foreach ($tags as $tag_id) {
                $Tag = null;
                if (preg_match('/^\d+$/', $tag_id)) {
                    $Tag = $this->tagRepository->find($tag_id);

                    if ($Tag) {
                        $ProductTags = new ProductTag();
                        $ProductTags
                            ->setProduct($Product)
                            ->setTag($Tag);

                        $Product->addProductTag($ProductTags);

                        $this->entityManager->persist($ProductTags);
                    }
                }
                if (!$Tag) {
                    $message = trans('admin.common.csv_invalid_not_found_target', [
                        '%line%' => $data->key() + 1,
                        '%name%' => $headerByKey['product_tag'],
                        '%target_name%' => $tag_id,
                    ]);
                    $this->addErrors($message);
                }
            }
        }
    }

    /**
     * 商品規格分類1、商品規格分類2がnullとなる商品規格情報を作成
     *
     * @param $row
     * @param Product $Product
     * @param CsvImportService $data
     * @param $headerByKey
     * @param null $ClassCategory1
     * @param null $ClassCategory2
     *
     * @return ProductClass
     */
    protected function createProductClass($row, Product $Product, $data, $headerByKey, $ClassCategory1 = null, $ClassCategory2 = null)
    {
        // 規格分類1、規格分類2がnullとなる商品を作成
        $ProductClass = new ProductClass();
        $ProductClass->setProduct($Product);
        $ProductClass->setVisible(true);

        $line = $data->key() + 1;
        if (isset($row["商品種別ID"]) && StringUtil::isNotBlank($row["商品種別ID"])) {
            if (preg_match('/^\d+$/', $row["商品種別ID"])) {
                $SaleType = $this->saleTypeRepository->find($row["商品種別ID"]);
                if (!$SaleType) {
                    $message = trans('admin.common.csv_invalid_not_found', ['%line%' => $line, '%name%' => "商品種別ID"]);
                    $this->addErrors($message);
                } else {
                    $ProductClass->setSaleType($SaleType);
                }
            } else {
                $message = trans('admin.common.csv_invalid_not_found', ['%line%' => $line, '%name%' => "商品種別ID"]);
                $this->addErrors($message);
            }
        } else {
            $message = trans('admin.common.csv_invalid_required', ['%line%' => $line, '%name%' => "商品種別ID"]);
            $this->addErrors($message);
        }

        $ProductClass->setClassCategory1($ClassCategory1);
        $ProductClass->setClassCategory2($ClassCategory2);

        if (isset($row["発送日目安ID"]) && StringUtil::isNotBlank($row["発送日目安ID"])) {
            if (preg_match('/^\d+$/', $row["発送日目安ID"])) {
                $DeliveryDuration = $this->deliveryDurationRepository->find($row["発送日目安ID"]);
                if (!$DeliveryDuration) {
                    $message = trans('admin.common.csv_invalid_not_found', ['%line%' => $line, '%name%' => "発送日目安ID"]);
                    $this->addErrors($message);
                } else {
                    $ProductClass->setDeliveryDuration($DeliveryDuration);
                }
            } else {
                $message = trans('admin.common.csv_invalid_not_found', ['%line%' => $line, '%name%' => "発送日目安ID"]);
                $this->addErrors($message);
            }
        }

        if (isset($row["商品コード"]) && StringUtil::isNotBlank($row["商品コード"])) {
            $ProductClass->setCode(StringUtil::trimAll($row["商品コード"]));
        } else {
            $ProductClass->setCode(null);
        }

        if (!isset($row["在庫無制限フラグ"])
            || StringUtil::isBlank($row["在庫無制限フラグ"])
            || $row["在庫無制限フラグ"] == (string) Constant::DISABLED
        ) {
            $ProductClass->setStockUnlimited(false);
            // 在庫数が設定されていなければエラー
            if (isset($row["在庫数"]) && StringUtil::isNotBlank($row["在庫数"])) {
                $stock = str_replace(',', '', $row["在庫数"]);
                if (preg_match('/^\d+$/', $stock) && $stock >= 0) {
                    $ProductClass->setStock($stock);
                } else {
                    $message = trans('admin.common.csv_invalid_greater_than_zero', ['%line%' => $line, '%name%' => "在庫数"]);
                    $this->addErrors($message);
                }
            } else {
                $message = trans('admin.common.csv_invalid_required', ['%line%' => $line, '%name%' => "在庫数"]);
                $this->addErrors($message);
            }
        } elseif ($row["在庫無制限フラグ"] == (string) Constant::ENABLED) {
            $ProductClass->setStockUnlimited(true);
            $ProductClass->setStock(null);
        } else {
            $message = trans('admin.common.csv_invalid_required', ['%line%' => $line, '%name%' => "在庫無制限フラグ"]);
            $this->addErrors($message);
        }

        if (isset($row["販売制限数"]) && StringUtil::isNotBlank($row["販売制限数"])) {
            $saleLimit = str_replace(',', '', $row["販売制限数"]);
            if (preg_match('/^\d+$/', $saleLimit) && $saleLimit >= 0) {
                $ProductClass->setSaleLimit($saleLimit);
            } else {
                $message = trans('admin.common.csv_invalid_greater_than_zero', ['%line%' => $line, '%name%' => "販売制限数"]);
                $this->addErrors($message);
            }
        }

        if (isset($row["通常価格"]) && StringUtil::isNotBlank($row["通常価格"])) {
            $price01 = str_replace(',', '', $row["通常価格"]);
            $errors = $this->validator->validate($price01, new GreaterThanOrEqual(['value' => 0]));
            if ($errors->count() === 0) {
                $ProductClass->setPrice01($price01);
            } else {
                $message = trans('admin.common.csv_invalid_greater_than_zero', ['%line%' => $line, '%name%' => "通常価格"]);
                $this->addErrors($message);
            }
        }

        if (isset($row["販売価格"]) && StringUtil::isNotBlank($row["販売価格"])) {
            $price02 = str_replace(',', '', $row["販売価格"]);
            $errors = $this->validator->validate($price02, new GreaterThanOrEqual(['value' => 0]));
            if ($errors->count() === 0) {
                $ProductClass->setPrice02($price02);
            } else {
                $message = trans('admin.common.csv_invalid_greater_than_zero', ['%line%' => $line, '%name%' => "販売価格"]);
                $this->addErrors($message);
            }
        } else {
            $message = trans('admin.common.csv_invalid_required', ['%line%' => $line, '%name%' => "販売価格"]);
            $this->addErrors($message);
        }

        if ($this->BaseInfo->isOptionProductDeliveryFee()) {
            if (isset($row["送料"]) && StringUtil::isNotBlank($row["送料"])) {
                $delivery_fee = str_replace(',', '', $row["送料"]);
                $errors = $this->validator->validate($delivery_fee, new GreaterThanOrEqual(['value' => 0]));
                if ($errors->count() === 0) {
                    $ProductClass->setDeliveryFee($delivery_fee);
                } else {
                    $message = trans('admin.common.csv_invalid_greater_than_zero',
                        ['%line%' => $line, '%name%' => "送料"]);
                    $this->addErrors($message);
                }
            }
        }

        $Product->addProductClass($ProductClass);
        $ProductStock = new ProductStock();
        $ProductClass->setProductStock($ProductStock);
        $ProductStock->setProductClass($ProductClass);

        if (!$ProductClass->isStockUnlimited()) {
            $ProductStock->setStock($ProductClass->getStock());
        } else {
            // 在庫無制限時はnullを設定
            $ProductStock->setStock(null);
        }

        $this->entityManager->persist($ProductClass);
        $this->entityManager->persist($ProductStock);

        return $ProductClass;
    }

    /**
     * 商品規格情報を更新
     *
     * @param $row
     * @param Product $Product
     * @param ProductClass $ProductClass
     * @param CsvImportService $data
     *
     * @return ProductClass
     */
    protected function updateProductClass($row, Product $Product, ProductClass $ProductClass, $data, $headerByKey)
    {
        $ProductClass->setProduct($Product);

        $line = $data->key() + 1;
        if (!isset($row[$headerByKey['sale_type']]) || $row[$headerByKey['sale_type']] == '') {
            $message = trans('admin.common.csv_invalid_required', ['%line%' => $line, '%name%' => $headerByKey['sale_type']]);
            $this->addErrors($message);
        } else {
            if (preg_match('/^\d+$/', $row[$headerByKey['sale_type']])) {
                $SaleType = $this->saleTypeRepository->find($row[$headerByKey['sale_type']]);
                if (!$SaleType) {
                    $message = trans('admin.common.csv_invalid_not_found', ['%line%' => $line, '%name%' => $headerByKey['sale_type']]);
                    $this->addErrors($message);
                } else {
                    $ProductClass->setSaleType($SaleType);
                }
            } else {
                $message = trans('admin.common.csv_invalid_required', ['%line%' => $line, '%name%' => $headerByKey['sale_type']]);
                $this->addErrors($message);
            }
        }

        // 規格分類1、2をそれぞれセットし作成
        if (isset($row[$headerByKey['class_category1']]) && $row[$headerByKey['class_category1']] != '') {
            if (preg_match('/^\d+$/', $row[$headerByKey['class_category1']])) {
                $ClassCategory = $this->classCategoryRepository->find($row[$headerByKey['class_category1']]);
                if (!$ClassCategory) {
                    $message = trans('admin.common.csv_invalid_not_found', ['%line%' => $line, '%name%' => $headerByKey['class_category1']]);
                    $this->addErrors($message);
                } else {
                    $ProductClass->setClassCategory1($ClassCategory);
                }
            } else {
                $message = trans('admin.common.csv_invalid_not_found', ['%line%' => $line, '%name%' => $headerByKey['class_category1']]);
                $this->addErrors($message);
            }
        }

        if (isset($row[$headerByKey['class_category2']]) && $row[$headerByKey['class_category2']] != '') {
            if (preg_match('/^\d+$/', $row[$headerByKey['class_category2']])) {
                $ClassCategory = $this->classCategoryRepository->find($row[$headerByKey['class_category2']]);
                if (!$ClassCategory) {
                    $message = trans('admin.common.csv_invalid_not_found', ['%line%' => $line, '%name%' => $headerByKey['class_category2']]);
                    $this->addErrors($message);
                } else {
                    $ProductClass->setClassCategory2($ClassCategory);
                }
            } else {
                $message = trans('admin.common.csv_invalid_not_found', ['%line%' => $line, '%name%' => $headerByKey['class_category2']]);
                $this->addErrors($message);
            }
        }

        if (isset($row[$headerByKey['delivery_date']]) && $row[$headerByKey['delivery_date']] != '') {
            if (preg_match('/^\d+$/', $row[$headerByKey['delivery_date']])) {
                $DeliveryDuration = $this->deliveryDurationRepository->find($row[$headerByKey['delivery_date']]);
                if (!$DeliveryDuration) {
                    $message = trans('admin.common.csv_invalid_not_found', ['%line%' => $line, '%name%' => $headerByKey['delivery_date']]);
                    $this->addErrors($message);
                } else {
                    $ProductClass->setDeliveryDuration($DeliveryDuration);
                }
            } else {
                $message = trans('admin.common.csv_invalid_not_found', ['%line%' => $line, '%name%' => $headerByKey['delivery_date']]);
                $this->addErrors($message);
            }
        }

        if (isset($row[$headerByKey['product_code']]) && StringUtil::isNotBlank($row[$headerByKey['product_code']])) {
            $ProductClass->setCode(StringUtil::trimAll($row[$headerByKey['product_code']]));
        } else {
            $ProductClass->setCode(null);
        }

        if (!isset($row[$headerByKey['stock_unlimited']])
            || StringUtil::isBlank($row[$headerByKey['stock_unlimited']])
            || $row[$headerByKey['stock_unlimited']] == (string) Constant::DISABLED
        ) {
            $ProductClass->setStockUnlimited(false);
            // 在庫数が設定されていなければエラー
            if ($row[$headerByKey['stock']] == '') {
                $message = trans('admin.common.csv_invalid_required', ['%line%' => $line, '%name%' => $headerByKey['stock']]);
                $this->addErrors($message);
            } else {
                $stock = str_replace(',', '', $row[$headerByKey['stock']]);
                if (preg_match('/^\d+$/', $stock) && $stock >= 0) {
                    $ProductClass->setStock($row[$headerByKey['stock']]);
                } else {
                    $message = trans('admin.common.csv_invalid_greater_than_zero', ['%line%' => $line, '%name%' => $headerByKey['stock']]);
                    $this->addErrors($message);
                }
            }
        } elseif ($row[$headerByKey['stock_unlimited']] == (string) Constant::ENABLED) {
            $ProductClass->setStockUnlimited(true);
            $ProductClass->setStock(null);
        } else {
            $message = trans('admin.common.csv_invalid_required', ['%line%' => $line, '%name%' => $headerByKey['stock_unlimited']]);
            $this->addErrors($message);
        }

        if (isset($row[$headerByKey['sale_limit']]) && $row[$headerByKey['sale_limit']] != '') {
            $saleLimit = str_replace(',', '', $row[$headerByKey['sale_limit']]);
            if (preg_match('/^\d+$/', $saleLimit) && $saleLimit >= 0) {
                $ProductClass->setSaleLimit($saleLimit);
            } else {
                $message = trans('admin.common.csv_invalid_greater_than_zero', ['%line%' => $line, '%name%' => $headerByKey['sale_limit']]);
                $this->addErrors($message);
            }
        }

        if (isset($row[$headerByKey['price01']]) && $row[$headerByKey['price01']] != '') {
            $price01 = str_replace(',', '', $row[$headerByKey['price01']]);
            $errors = $this->validator->validate($price01, new GreaterThanOrEqual(['value' => 0]));
            if ($errors->count() === 0) {
                $ProductClass->setPrice01($price01);
            } else {
                $message = trans('admin.common.csv_invalid_greater_than_zero', ['%line%' => $line, '%name%' => $headerByKey['price01']]);
                $this->addErrors($message);
            }
        }

        if (!isset($row[$headerByKey['price02']]) || $row[$headerByKey['price02']] == '') {
            $message = trans('admin.common.csv_invalid_required', ['%line%' => $line, '%name%' => $headerByKey['price02']]);
            $this->addErrors($message);
        } else {
            $price02 = str_replace(',', '', $row[$headerByKey['price02']]);
            $errors = $this->validator->validate($price02, new GreaterThanOrEqual(['value' => 0]));
            if ($errors->count() === 0) {
                $ProductClass->setPrice02($price02);
            } else {
                $message = trans('admin.common.csv_invalid_greater_than_zero', ['%line%' => $line, '%name%' => $headerByKey['price02']]);
                $this->addErrors($message);
            }
        }

        $ProductStock = $ProductClass->getProductStock();

        if (!$ProductClass->isStockUnlimited()) {
            $ProductStock->setStock($ProductClass->getStock());
        } else {
            // 在庫無制限時はnullを設定
            $ProductStock->setStock(null);
        }

        return $ProductClass;
    }

    /**
     * 登録、更新時のエラー画面表示
     */
    protected function addErrors($message)
    {
        $this->errors[] = $message;
    }

    /**
     * @return array
     */
    protected function getErrors()
    {
        return $this->errors;
    }

    /**
     * @return boolean
     */
    protected function hasErrors()
    {
        return count($this->getErrors()) > 0;
    }

    /**
     * 商品登録CSVヘッダー定義
     *
     * @return array
     */
    protected function getProductCsvHeader()
    {
        return [
            trans('admin.product.product_csv.product_id_col') => [
                'id' => 'id',
                'description' => 'admin.product.product_csv.product_id_description',
                'required' => false,
            ],
            trans('admin.product.product_csv.display_status_col') => [
                'id' => 'status',
                'description' => 'admin.product.product_csv.display_status_description',
                'required' => true,
            ],
            trans('admin.product.product_csv.product_name_col') => [
                'id' => 'name',
                'description' => 'admin.product.product_csv.product_name_description',
                'required' => true,
            ],
            trans('admin.product.product_csv.shop_memo_col') => [
                'id' => 'note',
                'description' => 'admin.product.product_csv.shop_memo_description',
                'required' => false,
            ],
            trans('admin.product.product_csv.description_list_col') => [
                'id' => 'description_list',
                'description' => 'admin.product.product_csv.description_list_description',
                'required' => false,
            ],
            trans('admin.product.product_csv.description_detail_col') => [
                'id' => 'description_detail',
                'description' => 'admin.product.product_csv.description_detail_description',
                'required' => false,
            ],
            trans('admin.product.product_csv.keyword_col') => [
                'id' => 'search_word',
                'description' => 'admin.product.product_csv.keyword_description',
                'required' => false,
            ],
            trans('admin.product.product_csv.free_area_col') => [
                'id' => 'free_area',
                'description' => 'admin.product.product_csv.free_area_description',
                'required' => false,
            ],
            trans('admin.product.product_csv.delete_flag_col') => [
                'id' => 'product_del_flg',
                'description' => 'admin.product.product_csv.delete_flag_description',
                'required' => false,
            ],
            trans('admin.product.product_csv.product_image_col') => [
                'id' => 'product_image',
                'description' => 'admin.product.product_csv.product_image_description',
                'required' => false,
            ],
            trans('admin.product.product_csv.category_col') => [
                'id' => 'product_category',
                'description' => 'admin.product.product_csv.category_description',
                'required' => false,
            ],
            trans('admin.product.product_csv.tag_col') => [
                'id' => 'product_tag',
                'description' => 'admin.product.product_csv.tag_description',
                'required' => false,
            ],
            trans('admin.product.product_csv.sale_type_col') => [
                'id' => 'sale_type',
                'description' => 'admin.product.product_csv.sale_type_description',
                'required' => true,
            ],
            trans('admin.product.product_csv.class_category1_col') => [
                'id' => 'class_category1',
                'description' => 'admin.product.product_csv.class_category1_description',
                'required' => false,
            ],
            trans('admin.product.product_csv.class_category2_col') => [
                'id' => 'class_category2',
                'description' => 'admin.product.product_csv.class_category2_description',
                'required' => false,
            ],
            trans('admin.product.product_csv.delivery_duration_col') => [
                'id' => 'delivery_date',
                'description' => 'admin.product.product_csv.delivery_duration_description',
                'required' => false,
            ],
            trans('admin.product.product_csv.product_code_col') => [
                'id' => 'product_code',
                'description' => 'admin.product.product_csv.product_code_description',
                'required' => false,
            ],
            trans('admin.product.product_csv.stock_col') => [
                'id' => 'stock',
                'description' => 'admin.product.product_csv.stock_description',
                'required' => false,
            ],
            trans('admin.product.product_csv.stock_unlimited_col') => [
                'id' => 'stock_unlimited',
                'description' => 'admin.product.product_csv.stock_unlimited_description',
                'required' => false,
            ],
            trans('admin.product.product_csv.sale_limit_col') => [
                'id' => 'sale_limit',
                'description' => 'admin.product.product_csv.sale_limit_description',
                'required' => false,
            ],
            trans('admin.product.product_csv.normal_price_col') => [
                'id' => 'price01',
                'description' => 'admin.product.product_csv.normal_price_description',
                'required' => false,
            ],
            trans('admin.product.product_csv.sale_price_col') => [
                'id' => 'price02',
                'description' => 'admin.product.product_csv.sale_price_description',
                'required' => true,
            ],
            trans('admin.product.product_csv.delivery_fee_col') => [
                'id' => 'delivery_fee',
                'description' => 'admin.product.product_csv.delivery_fee_description',
                'required' => false,
            ],
            trans('admin.product.product_csv.tax_rate_col') => [
                'id' => 'tax_rate',
                'description' => 'admin.product.product_csv.tax_rate_description',
                'required' => false,
            ],
        ];
    }

    /**
     * カテゴリCSVヘッダー定義
     */
    protected function getCategoryCsvHeader()
    {
        return [
            trans('admin.product.category_csv.category_id_col') => [
                'id' => 'id',
                'description' => 'admin.product.category_csv.category_id_description',
                'required' => false,
            ],
            trans('admin.product.category_csv.category_name_col') => [
                'id' => 'category_name',
                'description' => 'admin.product.category_csv.category_name_description',
                'required' => true,
            ],
            trans('admin.product.category_csv.parent_category_id_col') => [
                'id' => 'parent_category_id',
                'description' => 'admin.product.category_csv.parent_category_id_description',
                'required' => false,
            ],
            trans('admin.product.category_csv.delete_flag_col') => [
                'id' => 'category_del_flg',
                'description' => 'admin.product.category_csv.delete_flag_description',
                'required' => false,
            ],
        ];
    }

    /**
     * ProductCategory作成
     *
     * @param \Eccube\Entity\Product $Product
     * @param \Eccube\Entity\Category $Category
     * @param int $sortNo
     *
     * @return ProductCategory
     */
    private function makeProductCategory($Product, $Category, $sortNo)
    {
        $ProductCategory = new ProductCategory();
        $ProductCategory->setProduct($Product);
        $ProductCategory->setProductId($Product->getId());
        $ProductCategory->setCategory($Category);
        $ProductCategory->setCategoryId($Category->getId());

        return $ProductCategory;
    }

    /**
     * @Route("/%eccube_admin_route%/product/csv_split", name="admin_product_csv_split", methods={"POST"})
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function splitCsv(Request $request)
    {
        $this->isTokenValid();

        if (!$request->isXmlHttpRequest()) {
            throw new BadRequestHttpException();
        }

        $form = $this->formFactory->createBuilder(CsvImportType::class)->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $dir = $this->eccubeConfig['eccube_csv_temp_realdir'];
            if (!file_exists($dir)) {
                $fs = new Filesystem();
                $fs->mkdir($dir);
            }

            $data = $form['import_file']->getData();
            $src = new \SplFileObject($data->getRealPath());
            $src->setFlags(\SplFileObject::READ_CSV | \SplFileObject::READ_AHEAD | \SplFileObject::SKIP_EMPTY);

            $fileNo = 1;
            $fileName = StringUtil::random(8);

            $dist = new \SplFileObject($dir.'/'.$fileName.$fileNo.'.csv', 'w');
            $header = $src->current();
            $src->next();
            $dist->fputcsv($header);

            $i = 0;
            while ($row = $src->current()) {
                $dist->fputcsv($row);
                $src->next();

                if (!$src->eof() && ++$i % $this->eccubeConfig['eccube_csv_split_lines'] === 0) {
                    $fileNo++;
                    $dist = new \SplFileObject($dir.'/'.$fileName.$fileNo.'.csv', 'w');
                    $dist->fputcsv($header);
                }
            }

            return $this->json(['success' => true, 'file_name' => $fileName, 'max_file_no' => $fileNo]);
        }

        return $this->json(['success' => false, 'message' => $form->getErrors(true, true)]);
    }

    /**
     * @Route("/%eccube_admin_route%/product/csv_split_import", name="admin_product_csv_split_import", methods={"POST"})
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function importCsv(Request $request, CsrfTokenManagerInterface $tokenManager)
    {
        $this->isTokenValid();

        if (!$request->isXmlHttpRequest()) {
            throw new BadRequestHttpException();
        }

        $choices = $this->getCsvTempFiles();

        $filename = $request->get('file_name');
        if (!isset($choices[$filename])) {
            throw new BadRequestHttpException();
        }

        $path = $this->eccubeConfig['eccube_csv_temp_realdir'].'/'.$filename;
        $request->files->set('admin_csv_import', ['import_file' => new UploadedFile(
            $path,
            'import.csv',
            'text/csv',
            filesize($path),
            null,
            true
        )]);

        $request->setMethod('POST');
        $request->request->set('admin_csv_import', [
            Constant::TOKEN_NAME => $tokenManager->getToken('admin_csv_import')->getValue(),
            'is_split_csv' => true,
            'csv_file_no' => $request->get('file_no'),
        ]);

        switch ( $request->request->get('csv_type') ) {
            case 'customer':
                return $this->forwardToRoute('admin_customer_csv_import');

            case 'order':
                return $this->forwardToRoute('admin_order_csv_import');

            case 'mail_history':
                return $this->forwardToRoute('admin_mail_history_csv_import');
        }
        return $this->forwardToRoute('admin_product_csv_import');
    }

    /**
     * @Route("/%eccube_admin_route%/product/csv_split_cleanup", name="admin_product_csv_split_cleanup", methods={"POST"})
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function cleanupSplitCsv(Request $request)
    {
        $this->isTokenValid();

        if (!$request->isXmlHttpRequest()) {
            throw new BadRequestHttpException();
        }

        $files = $request->get('files', []);
        $choices = $this->getCsvTempFiles();

        foreach ($files as $filename) {
            if (isset($choices[$filename])) {
                unlink($choices[$filename]);
            } else {
                return $this->json(['success' => false]);
            }
        }

        return $this->json(['success' => true]);
    }

    protected function getCsvTempFiles()
    {
        $files = Finder::create()
            ->in($this->eccubeConfig['eccube_csv_temp_realdir'])
            ->name('*.csv')
            ->files();

        $choices = [];
        foreach ($files as $file) {
            $choices[$file->getBaseName()] = $file->getRealPath();
        }

        return $choices;
    }

    protected function convertLineNo($currentLineNo)
    {
        if ($this->isSplitCsv) {
            return ($this->eccubeConfig['eccube_csv_split_lines']) * ($this->csvFileNo - 1) + $currentLineNo;
        }

        return $currentLineNo;
    }

    /**
     * 会員登録CSVアップロード
     *
     * @Route("/%eccube_admin_route%/product/product_csv_upload", name="admin_customer_csv_import", methods={"GET", "POST"})
     * @Template("@admin/Product/csv_product.twig")
     *
     * @return array
     *
     * @throws \Doctrine\DBAL\ConnectionException
     * @throws \Doctrine\ORM\NoResultException
     */
    public function csvCustomer(Request $request, CacheUtil $cacheUtil)
    {
        $form = $this->formFactory->createBuilder(CsvImportType::class)->getForm();
        $headers = [];
        if ('POST' === $request->getMethod()) {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $this->isSplitCsv = $form['is_split_csv']->getData();
                $this->csvFileNo = $form['csv_file_no']->getData();

                $formFile = $form['import_file']->getData();
                if (!empty($formFile)) {
                    log_info('会員CSV登録開始');
                    $data = $this->getImportData($formFile);
                    if ($data === false) {
                        $this->addErrors(trans('admin.common.csv_invalid_format'));

                        return $this->renderWithError($form, $headers, false);
                    }
                    $getId = function ($item) {
                        return $item['id'];
                    };
                    $requireHeader = array_keys(array_map($getId, array_filter($headers, function ($value) {
                        return $value['required'];
                    })));

                    $columnHeaders = $data->getColumnHeaders();

                    // if (count(array_diff($requireHeader, $columnHeaders)) > 0) {
                    //     $this->addErrors(trans('admin.common.csv_invalid_format'));

                    //     return $this->renderWithError($form, $headers, false);
                    // }

                    $size = count($data);

                    if ($size < 1) {
                        $this->addErrors(trans('admin.common.csv_invalid_no_data'));

                        return $this->renderWithError($form, $headers, false);
                    }

                    $headerSize = count($columnHeaders);
                    $headerByKey = array_flip(array_map($getId, $headers));
                    $deleteImages = [];

                    $this->entityManager->getConfiguration()->setSQLLogger(null);
                    $this->entityManager->getConnection()->beginTransaction();

                    $customerRepository = $this->getDoctrine()->getRepository(Customer::class);
                    $prefRepository = $this->getDoctrine()->getRepository(Pref::class);
                    $customerStatusRepository = $this->getDoctrine()->getRepository(CustomerStatus::class);
                    $CustomerStatus = $customerStatusRepository->find(CustomerStatus::REGULAR);
                    
                    if ( !$this->session->has('SESSION_TEMP_CUSTOMER_LOOP_ID') ) {
                        $loopCustomerId = 1;
                    } else {
                        $loopCustomerId = $this->session->get('SESSION_TEMP_CUSTOMER_LOOP_ID');
                    }

                    // CSVファイルの登録処理
                    foreach ($data as $row) {
                        $Customer = $customerRepository->find($row['ID']);
                        if ( $Customer ) {
                            $Customer->setPassword($row['password']);
                            $Customer->setSalt($row['salt']);
                            $Customer->setSecretKey($row['secret_key']);

                            if ( substr($Customer->getPhoneNumber(), 0, 1) != '0' ) {
                                $Customer->setPhoneNumber('0' . $Customer->getPhoneNumber());
                            }
                            $this->entityManager->persist($Customer);
                        }

                        continue;

                        $line = $this->convertLineNo($data->key() + 1);
                        $this->currentLineNo = $line;
                        if ($headerSize != count($row)) {
                            $message = trans('admin.common.csv_invalid_format_line', ['%line%' => $line]);
                            $this->addErrors($message);

                            return $this->renderWithError($form, $headers);
                        }

                        $i = 1;
                        while ( ($loopCustomerId + $i++) < intval($row['会員ID']) ) {
                            $this->createTempCustomer();
                        }
                        $loopCustomerId = $row['会員ID'];
                        $this->session->set('SESSION_TEMP_CUSTOMER_LOOP_ID', intval($row['会員ID']));

                        if (!isset($row["E-MAIL"]) || StringUtil::isBlank($row["E-MAIL"])) {
                            $Customer = new Customer();
                            $this->entityManager->persist($Customer);
                        } else {
                            if ( !is_null($row["E-MAIL"]) ) {
                                $Customer = $customerRepository->findOneBy(['email' => $row["E-MAIL"]]);
                                if (!$Customer) {
                                    $Customer = new Customer();
                                    $this->entityManager->persist($Customer);
                                }
                            } else {
                                $message = trans('admin.common.csv_invalid_not_found', ['%line%' => $line, '%name%' => "E-MAIL"]);
                                $this->addErrors($message);

                                return $this->renderWithError($form, $headers);
                            }
                        }

                        if (StringUtil::isBlank($row["お名前(姓)"])) {
                            // $message = trans('admin.common.csv_invalid_not_found', ['%line%' => $line, '%name%' => "お名前(姓)"]);
                            // $this->addErrors($message);

                            // return $this->renderWithError($form, $headers);
                            $Customer->setName01("Blank");
                        } else {
                            $Customer->setName01(StringUtil::trimAll($row["お名前(姓)"]));
                        }

                        if (StringUtil::isBlank($row["お名前(名)"])) {
                            // $message = trans('admin.common.csv_invalid_not_found', ['%line%' => $line, '%name%' => "お名前(名)"]);
                            // $this->addErrors($message);

                            // return $this->renderWithError($form, $headers);
                            $Customer->setName02("Blank");
                        } else {
                            $Customer->setName02(StringUtil::trimAll($row["お名前(名)"]));
                        }

                        if (StringUtil::isNotBlank($row["お名前(フリガナ・姓)"])) {
                            $Customer->setKana01(StringUtil::trimAll($row["お名前(フリガナ・姓)"]));
                        }

                        if (StringUtil::isNotBlank($row["お名前(フリガナ・名)"])) {
                            $Customer->setKana02(StringUtil::trimAll($row["お名前(フリガナ・名)"]));
                        }

                        if (StringUtil::isNotBlank($row["会社名"])) {
                            $Customer->setCompanyName(StringUtil::trimAll($row["会社名"]));
                        }

                        if (StringUtil::isNotBlank($row["郵便番号1"]) && StringUtil::isNotBlank($row["郵便番号2"])) {
                            $Customer->setPostalCode(StringUtil::trimAll( $row["郵便番号1"] . $row["郵便番号2"] ));
                        } else {
                            $message = trans('admin.common.csv_invalid_not_found', ['%line%' => $line, '%name%' => "郵便番号"]);
                            $this->addErrors($message);

                            return $this->renderWithError($form, $headers);
                        }

                        if (StringUtil::isNotBlank($row["都道府県"])) {
                            $Pref = $prefRepository->findOneBy(['name' => StringUtil::trimAll($row["都道府県"])]);
                            $Customer->setPref($Pref);
                        } else {
                            $message = trans('admin.common.csv_invalid_not_found', ['%line%' => $line, '%name%' => "都道府県"]);
                            $this->addErrors($message);

                            return $this->renderWithError($form, $headers);
                        }

                        if (StringUtil::isNotBlank($row["住所1"])) {
                            $Customer->setAddr01(StringUtil::trimAll($row["住所1"]));
                        } else {
                            $message = trans('admin.common.csv_invalid_not_found', ['%line%' => $line, '%name%' => "住所1"]);
                            $this->addErrors($message);

                            return $this->renderWithError($form, $headers);
                        }

                        if (StringUtil::isNotBlank($row["住所2"])) {
                            $Customer->setAddr02(StringUtil::trimAll($row["住所2"]));
                        }

                        if (StringUtil::isNotBlank($row["E-MAIL"])) {
                            $Customer->setEmail(StringUtil::trimAll($row["E-MAIL"]));
                        } else {
                            $message = trans('admin.common.csv_invalid_not_found', ['%line%' => $line, '%name%' => "E-MAIL"]);
                            $this->addErrors($message);

                            return $this->renderWithError($form, $headers);
                        }

                        if (StringUtil::isNotBlank($row["TEL1"]) && StringUtil::isNotBlank($row["TEL2"]) && StringUtil::isNotBlank($row["TEL3"])) {
                            $Customer->setPhoneNumber(StringUtil::trimAll( $row["TEL1"] . $row["TEL2"] . $row["TEL3"] ));
                        } else {
                            $message = trans('admin.common.csv_invalid_not_found', ['%line%' => $line, '%name%' => "電話番号"]);
                            $this->addErrors($message);

                            return $this->renderWithError($form, $headers);
                        }

                        if (StringUtil::isNotBlank($row["誕生日"])) {
                            $Customer->setBirth(new \DateTime(StringUtil::trimAll($row["誕生日"])));
                        }

                        if (StringUtil::isNotBlank($row["初回購入日"])) {
                            $Customer->setFirstBuyDate(new \DateTime(StringUtil::trimAll($row["初回購入日"])));
                        }

                        if (StringUtil::isNotBlank($row["最終購入日"])) {
                            $Customer->setLastBuyDate(new \DateTime(StringUtil::trimAll($row["最終購入日"])));
                        }

                        if (StringUtil::isNotBlank($row["購入回数"])) {
                            $Customer->setBuyTimes(StringUtil::trimAll($row["最終購入日"]));
                        }

                        
                        $password="test12345678";
                        $encoder = $this->encoderFactory->getEncoder($Customer);
                        $salt = $encoder->createSalt();
                        $password = $encoder->encodePassword($password, $salt);
                        $secretKey = $customerRepository->getUniqueSecretKey();

                        $Customer->setStatus($CustomerStatus);
                        $Customer
                            ->setSalt($salt)
                            ->setPassword($password)
                            ->setSecretKey($secretKey)
                            ->setPoint(0);
                    }
                    $this->entityManager->flush();
                    $this->entityManager->getConnection()->commit();

                    $cacheUtil->clearDoctrineCache();
                }
            }
        }

        return $this->renderWithError($form, $headers);
    }

    /**
     * Mail History CSVアップロード
     *
     * @Route("/%eccube_admin_route%/product/product_csv_upload", name="admin_mail_history_csv_import", methods={"GET", "POST"})
     * @Template("@admin/Product/csv_product.twig")
     *
     * @return array
     *
     * @throws \Doctrine\DBAL\ConnectionException
     * @throws \Doctrine\ORM\NoResultException
     */
    public function csvMailHistory(Request $request, CacheUtil $cacheUtil)
    {
        $form = $this->formFactory->createBuilder(CsvImportType::class)->getForm();
        $headers = [];
        if ('POST' === $request->getMethod()) {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $this->isSplitCsv = $form['is_split_csv']->getData();
                $this->csvFileNo = $form['csv_file_no']->getData();

                $formFile = $form['import_file']->getData();
                if (!empty($formFile)) {
                    log_info('会員CSV登録開始');
                    $data = $this->getImportData($formFile);
                    if ($data === false) {
                        $this->addErrors(trans('admin.common.csv_invalid_format'));

                        return $this->renderWithError($form, $headers, false);
                    }
                    $getId = function ($item) {
                        return $item['id'];
                    };

                    $columnHeaders = $data->getColumnHeaders();

                    $size = count($data);

                    if ($size < 1) {
                        $this->addErrors(trans('admin.common.csv_invalid_no_data'));

                        return $this->renderWithError($form, $headers, false);
                    }

                    $headerSize = count($columnHeaders);

                    $this->entityManager->getConfiguration()->setSQLLogger(null);
                    $this->entityManager->getConnection()->beginTransaction();

                    $mailHistoryRepository = $this->getDoctrine()->getRepository(MailHistory::class);
                    $orderRepository = $this->getDoctrine()->getRepository(Order::class);
                    $memberRepository = $this->getDoctrine()->getRepository(\Eccube\Entity\Member::class);
                    
                    $Creator = $memberRepository->find(2);
                    // CSVファイルの登録処理
                    foreach ($data as $row) {
                        $line = $this->convertLineNo($data->key() + 1);
                        $this->currentLineNo = $line;
                        if ($headerSize != count($row)) {
                            $message = trans('admin.common.csv_invalid_format_line', ['%line%' => $line]);
                            $this->addErrors($message);

                            return $this->renderWithError($form, $headers);
                        }

                        $Order = $orderRepository->find($row['order_id']);
                        $MailHistory = $mailHistoryRepository->findOneBy(['Order' => $Order]);

                        if ( !$MailHistory ) {
                            $MailHistory = new MailHistory();
                        }
                        $MailHistory->setOrder($Order);
                        $MailHistory->setCreator($Creator);

                        if (StringUtil::isNotBlank($row["subject"])) {
                            $MailHistory->setMailSubject($row["subject"]);
                        }

                        if (StringUtil::isNotBlank($row["mail_body"])) {
                            $MailHistory->setMailBody($row["mail_body"]);
                        }

                        if (StringUtil::isNotBlank($row["send_date"])) {
                            $MailHistory->setSendDate(new \DateTime($row["send_date"]));
                        }
                        $this->entityManager->persist($MailHistory);
                    }
                    $this->entityManager->flush();
                    $this->entityManager->getConnection()->commit();

                    $cacheUtil->clearDoctrineCache();
                }
            }
        }

        return $this->renderWithError($form, $headers);
    }
    

    /**
     * 会員登録CSVアップロード
     *
     * @Route("/%eccube_admin_route%/product/product_csv_upload", name="admin_order_csv_import", methods={"GET", "POST"})
     * @Template("@admin/Product/csv_product.twig")
     *
     * @return array
     *
     * @throws \Doctrine\DBAL\ConnectionException
     * @throws \Doctrine\ORM\NoResultException
     */
    public function csvOrder(Request $request, CacheUtil $cacheUtil)
    {
        $form = $this->formFactory->createBuilder(CsvImportType::class)->getForm();
        $headers = [];
        if ('POST' === $request->getMethod()) {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $this->isSplitCsv = $form['is_split_csv']->getData();
                $this->csvFileNo = $form['csv_file_no']->getData();

                $formFile = $form['import_file']->getData();
                if (!empty($formFile)) {
                    log_info('会員CSV登録開始');
                    $data = $this->getImportData($formFile);
                    if ($data === false) {
                        $this->addErrors(trans('admin.common.csv_invalid_format'));

                        return $this->renderWithError($form, $headers, false);
                    }
                    $getId = function ($item) {
                        return $item['id'];
                    };
                    $requireHeader = array_keys(array_map($getId, array_filter($headers, function ($value) {
                        return $value['required'];
                    })));

                    $columnHeaders = $data->getColumnHeaders();

                    // if (count(array_diff($requireHeader, $columnHeaders)) > 0) {
                    //     $this->addErrors(trans('admin.common.csv_invalid_format'));

                    //     return $this->renderWithError($form, $headers, false);
                    // }

                    $size = count($data);

                    if ($size < 1) {
                        $this->addErrors(trans('admin.common.csv_invalid_no_data'));

                        return $this->renderWithError($form, $headers, false);
                    }

                    $headerSize = count($columnHeaders);

                    $this->entityManager->getConfiguration()->setSQLLogger(null);
                    $this->entityManager->getConnection()->beginTransaction();

                    $ProductItemType = $this->orderItemTypeRepository->find(OrderItemType::PRODUCT);
                    $DiscountItemType = $this->orderItemTypeRepository->find(OrderItemType::DISCOUNT);
                    $DeliveryItemType = $this->orderItemTypeRepository->find(OrderItemType::DELIVERY_FEE);
                    $ChargeItemType = $this->orderItemTypeRepository->find(OrderItemType::CHARGE);

                    $RoundingType = $this->roundingTypeRepository->find(1);
                    $TaxType = $this->taxTypeRepository->find(1);
                    $TaxDisplay1 = $this->taxDisplayTypeRepository->find(1);
                    $TaxDisplay2 = $this->taxDisplayTypeRepository->find(2);

                    $Product = $this->productRepository->find(1);
                    $DeliveryFee0 = $this->deliveryRepository->find(2);
                    $Delivery = $this->deliveryRepository->find(1);
                    $PaymentCredit = $this->paymentRepository->find(8);
                    $PaymentBank = $this->paymentRepository->find(5);

                    if ( !$this->session->has('SESSION_TEMP_ORDER_LOOP_ID') ) {
                        $loopOrderId = 0;
                    } else {
                        $loopOrderId = $this->session->get('SESSION_TEMP_ORDER_LOOP_ID');
                    }

                    // CSVファイルの登録処理
                    foreach ($data as $key => $row) {
                        $line = $this->convertLineNo($data->key() + 1);

                        $this->currentLineNo = $line;
                        if ($headerSize != count($row)) {
                            $message = trans('admin.common.csv_invalid_format_line', ['%line%' => $line]);
                            $this->addErrors($message);

                            return $this->renderWithError($form, $headers);
                        }

                        $i = 1;
                        while ( ($loopOrderId + $i++) < intval($row['注文番号']) ) {
                            $this->createTempOrder();
                        }
                        $loopOrderId = intval($row['注文番号']);
                        $this->session->set('SESSION_TEMP_ORDER_LOOP_ID', intval($row['注文番号']));

                        $OrderStatus = $this->orderStatusRepository->find($row["対応状況"]);
                        $Order = new Order($OrderStatus);

                        $errorCustomer = $this->customerRepository->findOneBy(['email' => 'blank@error.com']);
                        $Customer = $this->customerRepository->findOneBy(['email' => $row["メールアドレス"]]);
                        if ( $Customer ) $Order->setCustomer($Customer);
                        else $Customer = $errorCustomer;

                        $DeviceType = $this->deviceTypeRepository->find( intval($row["端末種別ID"]) == 10 ? DeviceType::DEVICE_TYPE_PC : DeviceType::DEVICE_TYPE_MB );
                        $Order->setDeviceType($DeviceType);
                        $Order->setOrderNo($row["注文番号"]);

                        if (StringUtil::isNotBlank($row["注文日時"])) {
                            $Order->setOrderDate( new \DateTime($row["注文日時"]) );
                        }

                        if (StringUtil::isBlank($row["お名前(姓)"])) {
                            $Order->setName01("Blank");
                        } else {
                            $Order->setName01(StringUtil::trimAll($row["お名前(姓)"]));
                        }

                        if (StringUtil::isBlank($row["お名前(名)"])) {
                            $Order->setName02("Blank");
                        } else {
                            $Order->setName02(StringUtil::trimAll($row["お名前(名)"]));
                        }

                        if (StringUtil::isNotBlank($row["お名前(フリガナ・姓)"])) {
                            $Order->setKana01(StringUtil::trimAll($row["お名前(フリガナ・姓)"]));
                        }

                        if (StringUtil::isNotBlank($row["お名前(フリガナ名)"])) {
                            $Order->setKana02(StringUtil::trimAll($row["お名前(フリガナ名)"]));
                        }

                        if (StringUtil::isNotBlank($row["会社名"])) {
                            $Order->setCompanyName(StringUtil::trimAll($row["会社名"]));
                        }

                        if (StringUtil::isNotBlank($row["郵便番号1"]) && StringUtil::isNotBlank($row["郵便番号2"])) {
                            $Order->setPostalCode(StringUtil::trimAll( $row["郵便番号1"] . $row["郵便番号2"] ));
                        } else {
                            $Order->setPostalCode($errorCustomer->getPostalCode());
                        }

                        if (StringUtil::isNotBlank($row["都道府県"])) {
                            $Pref = $this->prefRepository->findOneBy(['name' => StringUtil::trimAll($row["都道府県"])]);
                            $Order->setPref($Pref);
                        } else {
                            $Order->setPref($errorCustomer->getPref());
                        }

                        if (StringUtil::isNotBlank($row["住所1"])) {
                            $Order->setAddr01(StringUtil::trimAll($row["住所1"]));
                        } else {
                            $Order->setAddr01($errorCustomer->getAddr01());
                        }

                        if (StringUtil::isNotBlank($row["住所2"])) {
                            $Order->setAddr02(StringUtil::trimAll($row["住所2"]));
                        }

                        if (StringUtil::isNotBlank($row["メールアドレス"])) {
                            $Order->setEmail(StringUtil::trimAll($row["メールアドレス"]));
                        } else {
                            $message = trans('admin.common.csv_invalid_not_found', ['%line%' => $line, '%name%' => "メールアドレス"]);
                            $this->addErrors($message);

                            return $this->renderWithError($form, $headers);
                        }

                        if (StringUtil::isNotBlank($row["電話番号1"]) && StringUtil::isNotBlank($row["電話番号2"]) && StringUtil::isNotBlank($row["電話番号3"])) {
                            $Order->setPhoneNumber(StringUtil::trimAll( $row["電話番号1"] . $row["電話番号2"] . $row["電話番号3"] ));
                        } else {
                            $Order->setPhoneNumber($errorCustomer->getPhoneNumber());
                        }

                        if (StringUtil::isNotBlank($row["生年月日"])) {
                            $Order->setBirth(new \DateTime(StringUtil::trimAll($row["生年月日"])));
                        }

                        if (StringUtil::isNotBlank($row["要望等"])) {
                            $Order->setMessage(StringUtil::trimAll($row["要望等"]));
                        }

                        $ProductClass = $Product->getProductClasses()[0];
            
                        $OrderItem = new OrderItem();
                        $OrderItem
                            ->setOrder($Order)
                            ->setProduct($Product)
                            ->setProductClass($ProductClass)
                            ->setProductName($Product->getName())
                            ->setProductCode($ProductClass->getCode())
                            ->setPrice($ProductClass->getPrice02())
                            ->setQuantity(1)
                            ->setOrderItemType($ProductItemType)
                            ->setRoundingType($RoundingType)
                            ->setTaxType($TaxType)
                            ->setTaxDisplayType($TaxDisplay1);
            
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
                        $this->entityManager->persist($OrderItem);
                        $Order->addOrderItem($OrderItem);

                        if (StringUtil::isNotBlank($row["値引き"])) {
                            $OrderItem = new OrderItem();
                            $OrderItem
                                ->setOrder($Order)
                                ->setProductName("値引き")
                                ->setOrderItemType($DiscountItemType)
                                ->setPrice($row["値引き"])
                                ->setQuantity(1)
                                ->setRoundingType($RoundingType)
                                ->setTaxType($TaxType)
                                ->setTaxDisplayType($TaxDisplay2);
                            $Order->addOrderItem($OrderItem);
                            $Order->setDiscount($row["値引き"]);
                            $this->entityManager->persist($OrderItem);
                        }

                        if (StringUtil::isNotBlank($row["手数料"])) {
                            $OrderItem = new OrderItem();
                            $OrderItem
                                ->setOrder($Order)
                                ->setProductName("手数料")
                                ->setOrderItemType($ChargeItemType)
                                ->setPrice($row["手数料"])
                                ->setQuantity(1)
                                ->setRoundingType($RoundingType)
                                ->setTaxType($TaxType)
                                ->setTaxDisplayType($TaxDisplay2);
                            $Order->addOrderItem($OrderItem);
                            $Order->setCharge($row["手数料"]);
                            $this->entityManager->persist($OrderItem);
                        }

                        if (StringUtil::isNotBlank($row["送料"])) {
                            $OrderItem = new OrderItem();
                            $OrderItem
                                ->setOrder($Order)
                                ->setProductName("送料")
                                ->setOrderItemType($DeliveryItemType)
                                ->setPrice($row["送料"])
                                ->setQuantity(1)
                                ->setRoundingType($RoundingType)
                                ->setTaxType($TaxType)
                                ->setTaxDisplayType($TaxDisplay2);
                            $Order->addOrderItem($OrderItem);
                            $Order->setDeliveryFeeTotal($row["送料"]);
                            $this->entityManager->persist($OrderItem);
                        }

                        $Shipping = $this->createShippingFromCustomer($Customer);
                        if (StringUtil::isNotBlank($row["発送完了日時"])) {
                            $Shipping->setShippingDate( new \DateTime($row["発送完了日時"]) );
                        }

                        $Shipping->setOrder($Order);

                        $tempDelivery = $Delivery;
                        if ( intval($row["送料"]) == 0 ) {
                            $tempDelivery = $DeliveryFee0;
                        }

                        $Shipping->setDelivery($tempDelivery);
                        $Shipping->setShippingDeliveryName($tempDelivery->getName());
                        $Order->addShipping($Shipping);
                        $this->entityManager->persist($Shipping);
                        
                        $tempPayment = $PaymentBank;
                        if ( $row["支払い方法"] == "クレジットカード決済" ) {
                            $tempPayment = $PaymentCredit;
                        }

                        // $Order->setOrderStatus($OrderStatus);
                        $Order->setPayment($tempPayment);
                        $Order->setPaymentMethod($tempPayment->getMethod())
                            ->setSubtotal($row["小計"])
                            ->setTotal($row["お支払い合計"])
                            ->setPaymentTotal($row["お支払い合計"]);

                        $this->entityManager->persist($Order);
                        try {
                            $this->entityManager->flush();
                        } catch (\Exception $e) {
                            print_r($row['注文番号'] . ', ');
                        }
                    }
                    $this->entityManager->getConnection()->commit();

                    $cacheUtil->clearDoctrineCache();
                }
            }
        }

        return $this->renderWithError($form, $headers);
    }

    /**
     * @param Customer $Customer
     *
     * @return Shipping
     */
    protected function createShippingFromCustomer(Customer $Customer)
    {
        $Shipping = new Shipping();
        $Shipping
            ->setName01($Customer->getName01())
            ->setName02($Customer->getName02())
            ->setKana01($Customer->getKana01())
            ->setKana02($Customer->getKana02())
            ->setCompanyName($Customer->getCompanyName())
            ->setPhoneNumber($Customer->getPhoneNumber())
            ->setPostalCode($Customer->getPostalCode())
            ->setPref($Customer->getPref())
            ->setAddr01($Customer->getAddr01())
            ->setAddr02($Customer->getAddr02());

        return $Shipping;
    }

    protected function createTempCustomer()
    {
        $Pref = $this->prefRepository->find(13);
        $Customer = new Customer();
        $Customer
            ->setName01('テスト')
            ->setName02('テスト')
            ->setKana01('テスト')
            ->setKana02('テスト')
            ->setPostalCode(1050013)
            ->setPref($Pref)
            ->setAddr01('港区浜松町')
            ->setAddr02('テスト')
            ->setEmail(time().mt_rand() . '@blank.com')
            ->setPhoneNumber('0300000000');
            
            $password="test12345678";
            $encoder = $this->encoderFactory->getEncoder($Customer);
            $salt = $encoder->createSalt();
            $password = $encoder->encodePassword($password, $salt);
            $secretKey = $this->customerRepository->getUniqueSecretKey();

            $CustomerStatus = $this->customerStatusRepository->find(CustomerStatus::WITHDRAWING);
            $Customer->setStatus($CustomerStatus);
            $Customer
                ->setSalt($salt)
                ->setPassword($password)
                ->setSecretKey($secretKey)
                ->setPoint(0);

        $this->entityManager->persist($Customer);
    }

    protected function createTempOrder()
    {
        $OrderStatus = $this->orderStatusRepository->find(\Eccube\Entity\Master\OrderStatus::CANCEL);
        $Customer = $this->customerRepository->findOneBy(['email' => 'blank@error.com']);

        $Order = new Order($OrderStatus);

        $Order->setName01($Customer->getName01());
        $Order->setName02($Customer->getName02());
        $Order->setKana01($Customer->getKana01());
        $Order->setKana02($Customer->getKana02());
        $Order->setPostalCode($Customer->getPostalCode());
        $Order->setPref($Customer->getPref());
        $Order->setAddr01($Customer->getAddr01());
        $Order->setAddr02($Customer->getAddr02());
        $Order->setEmail($Customer->getEmail());
        $Order->setPhoneNumber($Customer->getPhoneNumber());

        $Order->setCharge(0);
        $Order->setDeliveryFeeTotal(0);
        $Order->setSubtotal(0);
        $Order->setPaymentTotal(0);

        $this->entityManager->persist($Order);
    }
}
