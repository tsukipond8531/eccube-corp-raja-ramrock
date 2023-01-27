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

namespace Customize\Controller;

use Customize\Form\Type\Front\EnqueteType;
use Eccube\Repository\PageRepository;
use Eccube\Service\MailService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Eccube\Controller\AbstractController;

class EnqueteController extends AbstractController
{
    /**
     * @var MailService
     */
    protected $mailService;

    /**
     * @var PageRepository
     */
    private $pageRepository;

    /**
     * EnqueteController constructor.
     *
     * @param MailService $mailService
     * @param PageRepository $pageRepository
     */
    public function __construct(
        MailService $mailService,
        PageRepository $pageRepository)
    {
        $this->mailService = $mailService;
        $this->pageRepository = $pageRepository;
    }

    /**
     * アンケート画面.
     *
     * @Route("/enquete", name="enquete", methods={"GET", "POST"})
     * @Route("/enquete", name="enquete_confirm", methods={"GET", "POST"})
     * @Template("Enquete/index.twig")
     */
    public function index(Request $request)
    {
        $builder = $this->formFactory->createBuilder(EnqueteType::class);

        if ( !$this->isGranted('ROLE_USER') ) {
            return $this->redirectToRoute('mypage_login');
        }

        $form = $builder->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            switch ($request->get('mode')) {
                case 'confirm':
                    return $this->render('Enquete/confirm.twig', [
                        'form' => $form->createView(),
                        'Page' => $this->pageRepository->getPageByRoute('enquete_confirm'),
                    ]);

                case 'complete':
                    $data = $form->getData();

                    $Customer = $this->getUser();
                    $Customer->setEnquete(true);
                    $Customer->setEnqueteBody(json_encode($data));

                    $this->entityManager->persist($Customer);
                    $this->entityManager->flush();

                    log_info('アンケート完了');

                    // メール送信
                    $this->mailService->sendEnqueteMail($Customer, $data);

                    return $this->redirect($this->generateUrl('enquete_complete'));
            }
        }

        return [
            'form' => $form->createView(),
        ];
    }

    /**
     * アンケート完了画面.
     *
     * @Route("/enquete/complete", name="enquete_complete", methods={"GET"})
     * @Template("Enquete/complete.twig")
     */
    public function complete()
    {
        return [];
    }
}
