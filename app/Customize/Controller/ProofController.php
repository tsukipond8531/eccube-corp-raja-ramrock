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

use Eccube\Entity\Customer;
use Eccube\Event\EccubeEvents;
use Eccube\Event\EventArgs;
use Customize\Form\Type\Front\ProofType;
use Eccube\Repository\PageRepository;
use Eccube\Service\MailService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Eccube\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\File;

class ProofController extends AbstractController
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
     * ProofController constructor.
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
     * お問い合わせ画面.
     *
     * @Route("/proof", name="proof", methods={"GET", "POST"})
     * @Route("/proof", name="proof_confirm", methods={"GET", "POST"})
     * @Template("Proof/index.twig")
     */
    public function index(Request $request)
    {
        $builder = $this->formFactory->createBuilder(ProofType::class);

        if ($this->isGranted('ROLE_USER')) {
            /** @var Customer $user */
            $user = $this->getUser();
            $builder->setData(
                [
                    'customer_id' => $user->getId(),
                    'name01' => $user->getName01(),
                    'name02' => $user->getName02(),
                    'email' => $user->getEmail(),
                ]
            );
        }

        // FRONT_PROOF_INDEX_INITIALIZE
        $event = new EventArgs(
            [
                'builder' => $builder,
            ],
            $request
        );
        // $this->eventDispatcher->dispatch(EccubeEvents::FRONT_PROOF_INDEX_INITIALIZE, $event);

        $form = $builder->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            switch ($request->get('mode')) {
                case 'confirm':
                    return $this->render('Proof/confirm.twig', [
                        'form' => $form->createView(),
                        'Page' => $this->pageRepository->getPageByRoute('proof_confirm'),
                    ]);

                case 'complete':
                    $images = $form->get('add_images')->getData();
                    foreach ($images as $key => $image) {
                        if ($key) {
                            $user->setImage2($image);
                            
                            // 移動
                            $file = new File($this->eccubeConfig['eccube_temp_image_dir'].'/'.$image);
                            $file->move($this->eccubeConfig['eccube_save_image_dir']);
                        } else {
                            $user->setImage1($image);

                            // 移動
                            $file = new File($this->eccubeConfig['eccube_temp_image_dir'].'/'.$image);
                            $file->move($this->eccubeConfig['eccube_save_image_dir']);
                        }
                    }
                    if ($this->isGranted('ROLE_USER') && count($images)) {
                        $this->entityManager->persist($user);
                        $this->entityManager->flush();
                    }

                    $data = $form->getData();

                    $event = new EventArgs(
                        [
                            'form' => $form,
                            'data' => $data,
                        ],
                        $request
                    );
                    // $this->eventDispatcher->dispatch(EccubeEvents::FRONT_PROOF_INDEX_COMPLETE, $event);

                    $data = $event->getArgument('data');

                    // メール送信
                    $this->mailService->sendProofMail($data);

                    return $this->redirect($this->generateUrl('proof_complete'));
            }
        }

        return [
            'form' => $form->createView(),
        ];
    }

    /**
     * お問い合わせ完了画面.
     *
     * @Route("/proof/complete", name="proof_complete", methods={"GET"})
     * @Template("Proof/complete.twig")
     */
    public function complete()
    {
        return [];
    }

    /**
     * @Route("/proof/image/add", name="proof_image_add", methods={"POST"})
     */
    public function addImage(Request $request)
    {
        if (!$request->isXmlHttpRequest() && $this->isTokenValid()) {
            throw new BadRequestHttpException();
        }

        $images = $request->files->get('proof');

        $allowExtensions = ['gif', 'jpg', 'jpeg', 'png'];
        $files = [];
        if (count($images) > 0) {
            foreach ($images as $img) {
                foreach ($img as $image) {
                    //ファイルフォーマット検証
                    $mimeType = $image->getMimeType();
                    if (0 !== strpos($mimeType, 'image')) {
                        throw new UnsupportedMediaTypeHttpException();
                    }

                    // 拡張子
                    $extension = $image->getClientOriginalExtension();
                    if (!in_array(strtolower($extension), $allowExtensions)) {
                        throw new UnsupportedMediaTypeHttpException();
                    }

                    $filename = date('mdHis').uniqid('_').'.'.$extension;
                    $image->move($this->eccubeConfig['eccube_temp_image_dir'], $filename);
                    $files[] = $filename;
                }
            }
        }

        $event = new EventArgs(
            [
                'images' => $images,
                'files' => $files,
            ],
            $request
        );
        $this->eventDispatcher->dispatch(EccubeEvents::ADMIN_PRODUCT_ADD_IMAGE_COMPLETE, $event);
        $files = $event->getArgument('files');

        return $this->json(['files' => $files], 200);
    }
}
