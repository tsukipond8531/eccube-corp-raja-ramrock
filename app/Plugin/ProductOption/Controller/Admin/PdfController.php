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

use Eccube\Controller\AbstractController;
use Eccube\Form\Type\Admin\OrderPdfType;
use Eccube\Repository\OrderPdfRepository;
use Plugin\ProductOption\Service\ProductOptionOrderPdfService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PdfController extends AbstractController
{
    protected $orderPdfRepository;

    public function __construct(
        OrderPdfRepository $orderPdfRepository
    ) {
        $this->orderPdfRepository = $orderPdfRepository;
    }

    /**
     * @Route("/%eccube_admin_route%/order/export/pdf/download", name="admin_order_pdf_download")
     * @Template("@admin/Order/order_pdf.twig")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function exportPdfDownload(Request $request, ProductOptionOrderPdfService $orderPdfService)
    {
        /**
         * @var FormBuilder
         */
        $builder = $this->formFactory->createBuilder(OrderPdfType::class);

        /* @var \Symfony\Component\Form\Form $form */
        $form = $builder->getForm();
        $form->handleRequest($request);

        // Validation
        if (!$form->isValid()) {
            log_info('The parameter is invalid!');

            return $this->render('@admin/Order/order_pdf.twig', [
                'form' => $form->createView(),
            ]);
        }

        $arrData = $form->getData();

        // 購入情報からPDFを作成する
        $status = $orderPdfService->makePdf($arrData);

        // 異常終了した場合の処理
        if (!$status) {
            $this->addError('admin.order.export.pdf.download.failure', 'admin');
            log_info('Unable to create pdf files! Process have problems!');

            return $this->render('@admin/Order/order_pdf.twig', [
                'form' => $form->createView(),
            ]);
        }

        // ダウンロードする
        $response = new Response(
            $orderPdfService->outputPdf(),
            200,
            ['content-type' => 'application/pdf']
        );

        $downloadKind = $form->get('download_kind')->getData();

        // レスポンスヘッダーにContent-Dispositionをセットし、ファイル名を指定
        if ($downloadKind == 1) {
            $response->headers->set('Content-Disposition', 'attachment; filename="'.$orderPdfService->getPdfFileName().'"');
        } else {
            $response->headers->set('Content-Disposition', 'inline; filename="'.$orderPdfService->getPdfFileName().'"');
        }

        log_info('OrderPdf download success!', ['Order ID' => implode(',', $request->get('ids', []))]);

        $isDefault = isset($arrData['default']) ? $arrData['default'] : false;
        if ($isDefault) {
            // Save input to DB
            $arrData['admin'] = $this->getUser();
            $this->orderPdfRepository->save($arrData);
        }

        return $response;
    }
}
