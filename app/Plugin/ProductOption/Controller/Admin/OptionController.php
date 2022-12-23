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

use Eccube\Repository\CsvRepository;
use Eccube\Repository\Master\CsvTypeRepository;
use Plugin\ProductOption\Repository\OptionRepository;
use Plugin\ProductOption\Form\Type\Admin\OptionType;
use Plugin\ProductOption\Entity\Option;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class OptionController extends \Eccube\Controller\AbstractController
{

    /**
     * @var OptionRepository
     */
    private $optionRepository;

    private $csvRepository;

    /**
     * OptionController constructor.
     * @param OptionRepository $optionRepository
     */
    public function __construct(
            OptionRepository $optionRepository,
            CsvRepository $csvRepository,
            CsvTypeRepository $csvTypeRepository
            )
    {
        $this->optionRepository = $optionRepository;
        $this->csvRepository = $csvRepository;
        $this->csvTypeRepository = $csvTypeRepository;
    }

    /**
     * @Route("/%eccube_admin_route%/product/option", name="admin_product_option")
     * @Route("/%eccube_admin_route%/product/option/new", name="admin_product_option_new")
     * @Route("/%eccube_admin_route%/product/option/{id}/edit", requirements={"id" = "\d+"}, name="admin_product_option_edit")
     * @Template("@ProductOption/admin/Product/option.twig")
     */
    public function index(Request $request, $id = null)
    {
        if ($id) {
            $TargetOption = $this->optionRepository->find($id);
            if (is_null($TargetOption)) {
                throw new NotFoundHttpException();
            }
        } else {
            $TargetOption = new Option();
        }

        $OriginOption = clone $TargetOption;

        $form = $this->formFactory
                ->createBuilder(OptionType::class, $TargetOption)
                ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isSubmitted() && $form->isValid()) {
                $status = $this->optionRepository->save($TargetOption);

                if ($status) {
                    if($OriginOption->getId() > 0){
                        if($OriginOption->getType() != $TargetOption->getType()){
                            if($OriginOption->getType() == Option::CHECKBOX_TYPE){
                                $OptionCategories = $TargetOption->getOptionCategories();
                                if(count($OptionCategories) > 0){
                                    foreach($OptionCategories as $OptionCategory){
                                        $OptionCategory->setInitFlg(0);
                                        $this->entityManager->persist($OptionCategory);
                                    }
                                }
                            }
                            if(
                                ($OriginOption->getType() == Option::SELECT_TYPE ||
                                 $OriginOption->getType() == Option::RADIO_TYPE ||
                                 $OriginOption->getType() == Option::CHECKBOX_TYPE) &&
                                ($TargetOption->getType() == Option::TEXT_TYPE ||
                                 $TargetOption->getType() == Option::TEXTAREA_TYPE ||
                                 $TargetOption->getType() == Option::DATE_TYPE ||
                                 $TargetOption->getType() == Option::NUMBER_TYPE
                                )
                               ){
                                $OptionCategories = $TargetOption->getOptionCategories();
                                if(count($OptionCategories) > 0){
                                    foreach($OptionCategories as $OptionCategory){
                                        $TargetOption->removeOptionCategory($OptionCategory);
                                        $this->entityManager->remove($OptionCategory);
                                    }
                                }
                                $this->entityManager->persist($TargetOption);
                            }
                        }
                    }
                    $this->entityManager->flush();

                    if (!$id) {
                        $now = new \DateTime();
                        //CSV項目追加
                        $Csv = new \Eccube\Entity\Csv();
                        $CsvType = $this->csvTypeRepository->find(\Eccube\Entity\Master\CsvType::CSV_TYPE_ORDER);
                        $sort_no = 0;
                        try {
                            $sort_no = $this->entityManager->createQueryBuilder()
                                ->select('MAX(c.sort_no)')
                                ->from('Eccube\Entity\Csv','c')
                                ->where('c.CsvType = :csvType')
                                ->setParameter(':csvType',$CsvType)
                                ->getQuery()
                                ->getSingleScalarResult();
                        } catch (\Exception $exception) {
                        }
                        if (!$sort_no) {
                            $sort_no = 0;
                        }
                        $Csv->setCsvType($CsvType);
                        $Csv->setEntityName('Plugin\\ProductOption\\Entity\\OrderItemOption');
                        $Csv->setFieldName('OrderItemOption');
                        $Csv->setReferenceFieldName($TargetOption->getId());
                        $Csv->setDispName($TargetOption->getBackendName());
                        $Csv->setEnabled(false);
                        $Csv->setSortNo(++$sort_no);
                        $Csv->setCreateDate($now);
                        $Csv->setUpdateDate($now);
                        $this->entityManager->persist($Csv);

                        $Csv = new \Eccube\Entity\Csv();
                        $CsvType = $this->csvTypeRepository->find(\Eccube\Entity\Master\CsvType::CSV_TYPE_SHIPPING);
                        $sort_no = 0;
                        try {
                            $sort_no = $this->entityManager->createQueryBuilder()
                                ->select('MAX(c.sort_no)')
                                ->from('Eccube\Entity\Csv','c')
                                ->where('c.CsvType = :csvType')
                                ->setParameter(':csvType',$CsvType)
                                ->getQuery()
                                ->getSingleScalarResult();
                        } catch (\Exception $exception) {
                        }
                        if (!$sort_no) {
                            $sort_no = 0;
                        }
                        $Csv->setCsvType($CsvType);
                        $Csv->setEntityName('Plugin\\ProductOption\\Entity\\OrderItemOption');
                        $Csv->setFieldName('OrderItemOption');
                        $Csv->setReferenceFieldName($TargetOption->getId());
                        $Csv->setDispName($TargetOption->getBackendName());
                        $Csv->setEnabled(false);
                        $Csv->setSortNo(++$sort_no);
                        $Csv->setCreateDate($now);
                        $Csv->setUpdateDate($now);
                        $this->entityManager->persist($Csv);
                        $this->entityManager->flush();
                    }else{
                        $CsvType = $this->csvTypeRepository->find(\Eccube\Entity\Master\CsvType::CSV_TYPE_ORDER);
                        $Csv = $this->csvRepository->findOneBy(['CsvType' => $CsvType, 'entity_name' => 'Plugin\\ProductOption\\Entity\\OrderItemOption' , 'reference_field_name' => $TargetOption->getId()]);
                        if($Csv){
                            $Csv->setDispName($TargetOption->getBackendName());
                            $this->entityManager->persist($Csv);
                            $this->entityManager->flush($Csv);
                        }

                        $CsvType = $this->csvTypeRepository->find(\Eccube\Entity\Master\CsvType::CSV_TYPE_SHIPPING);
                        $Csv = $this->csvRepository->findOneBy(['CsvType' => $CsvType, 'entity_name' => 'Plugin\\ProductOption\\Entity\\OrderItemOption' , 'reference_field_name' => $TargetOption->getId()]);
                        if($Csv){
                            $Csv->setDispName($TargetOption->getBackendName());
                            $this->entityManager->persist($Csv);
                            $this->entityManager->flush($Csv);
                        }
                    }
                    $this->addSuccess('admin.product.option.save.complete', 'admin');

                    return $this->redirectToRoute('admin_product_option');
                } else {
                    $this->addError('admin.product.option.save.error', 'admin');
                }
            }
        }

        $Options = $this->optionRepository->getList();

        return [
                    'form' => $form->createView(),
                    'Options' => $Options,
                    'TargetOption' => $TargetOption,
        ];
    }

    /**
     * @Route("/%eccube_admin_route%/product/option/{id}/delete", requirements={"id" = "\d+"}, name="admin_product_option_delete",methods={"DELETE"})
     */
    public function delete(Request $request, $id)
    {
        $this->isTokenValid();

        $Option = $this->optionRepository->find($id);
        if (!$Option) {
            throw new NotFoundHttpException();
        }

        $status = false;
        $status = $this->optionRepository->delete($Option);

        if ($status === true) {
            $Csvs = $this->csvRepository->findBy(['field_name' => 'OrderItemOption', 'reference_field_name' => $id]);
            foreach($Csvs as $Csv){
                $this->entityManager->remove($Csv);
            }
            $this->entityManager->flush();
            $this->addSuccess('admin.product.option.delete.complete', 'admin');
        } else {
            $this->addError('admin.product.option.delete.error', 'admin');
        }

        return $this->redirectToRoute('admin_product_option');
    }

    /**
     * @Route("/%eccube_admin_route%/product/option/sort_no/move", name="admin_product_option_sort_no_move",methods={"POST"})
     */
    public function moveSortNo(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            $sortNos = $request->request->all();
            foreach ($sortNos as $optionId => $sortNo) {
                $Option = $this->optionRepository
                        ->find($optionId);
                $Option->setSortNo($sortNo);
                $this->entityManager->persist($Option);
            }
            $this->entityManager->flush();
        }
        return new Response();
    }

}
