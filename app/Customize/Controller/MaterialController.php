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
use Customize\Form\Type\Front\MaterialType;
use Customize\Form\Type\Front\CareManagerType;
use Eccube\Repository\PageRepository;
use Eccube\Service\MailService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

use Eccube\Controller\AbstractController;

class MaterialController extends AbstractController
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
     * MaterialController constructor.
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
     * @Route("/material", name="material", methods={"GET", "POST"})
     * @Route("/material", name="material_confirm", methods={"GET", "POST"})
     * @Template("Material/index.twig")
     */
    public function index(Request $request)
    {
        $materialBuilder = $this->formFactory->createBuilder(MaterialType::class);
        $careManagerBuilder = $this->formFactory->createBuilder(CareManagerType::class);

        if ($this->isGranted('ROLE_USER')) {
            /** @var Customer $user */
            $user = $this->getUser();
            $materialBuilder->setData(
                [
                    'name01' => $user->getName01(),
                    'name02' => $user->getName02(),
                    'kana01' => $user->getKana01(),
                    'kana02' => $user->getKana02(),
                    'postal_code' => $user->getPostalCode(),
                    'pref' => $user->getPref(),
                    'addr01' => $user->getAddr01(),
                    'addr02' => $user->getAddr02(),
                    'phone_number' => $user->getPhoneNumber(),
                    'email' => $user->getEmail(),
                ]
            );
            
            $careManagerBuilder->setData(
                [
                    'name01' => $user->getName01(),
                    'name02' => $user->getName02(),
                    'kana01' => $user->getKana01(),
                    'kana02' => $user->getKana02(),
                    'postal_code' => $user->getPostalCode(),
                    'pref' => $user->getPref(),
                    'addr01' => $user->getAddr01(),
                    'addr02' => $user->getAddr02(),
                    'email' => $user->getEmail(),
                ]
            );
        }

        // FRONT_MATERIAL_INDEX_INITIALIZE
        // $event = new EventArgs(
        //     [
        //         'builder' => $builder,
        //     ],
        //     $request
        // );
        // $this->eventDispatcher->dispatch(EccubeEvents::FRONT_MATERIAL_INDEX_INITIALIZE, $event);

        $materialForm = $materialBuilder->getForm();
        $careManagerForm = $careManagerBuilder->getForm();

        $materialForm->handleRequest($request);
        $careManagerForm->handleRequest($request);

        if ($materialForm->isSubmitted() && $materialForm->isValid()) {
            switch ($request->get('mode')) {
                case 'confirm':

                    return $this->render('Material/confirm.twig', [
                        'form' => $materialForm->createView(),
                        'Page' => $this->pageRepository->getPageByRoute('material_confirm'),
                    ]);

                case 'complete':
                    $data = $materialForm->getData();

                    $event = new EventArgs(
                        [
                            'form' => $materialForm,
                            'data' => $data,
                        ],
                        $request
                    );
                    // $this->eventDispatcher->dispatch(EccubeEvents::FRONT_MATERIAL_INDEX_COMPLETE, $event);

                    $data = $event->getArgument('data');

                    // メール送信
                    $this->mailService->sendMaterialMail($data, [ 'type' => 'material' ]);

                    return $this->redirect($this->generateUrl('material_complete'));
            }
        } else if ($materialForm->isSubmitted() && $request->get('mode') == 'complete') {
            $data = $materialForm->getData();

            $event = new EventArgs(
                [
                    'form' => $materialForm,
                    'data' => $data,
                ],
                $request
            );
            // $this->eventDispatcher->dispatch(EccubeEvents::FRONT_MATERIAL_INDEX_COMPLETE, $event);

            $data = $event->getArgument('data');

            // メール送信
            $this->mailService->sendMaterialMail($data, [ 'type' => 'material' ]);

            return $this->redirect($this->generateUrl('material_complete'));
        }

        if ($careManagerForm->isSubmitted() && $careManagerForm->isValid()) {
            switch ($request->get('mode')) {
                case 'confirm':
                    return $this->render('Material/confirm.twig', [
                        'form' => $careManagerForm->createView(),
                        'Page' => $this->pageRepository->getPageByRoute('material_confirm'),
                    ]);

                case 'complete':
                    $data = $careManagerForm->getData();

                    $event = new EventArgs(
                        [
                            'form' => $careManagerForm,
                            'data' => $data,
                        ],
                        $request
                    );
                    // $this->eventDispatcher->dispatch(EccubeEvents::FRONT_MATERIAL_INDEX_COMPLETE, $event);

                    $data = $event->getArgument('data');

                    // メール送信
                    $this->mailService->sendMaterialMail($data, [ 'type' => 'care_manager' ]);

                    return $this->redirect($this->generateUrl('material_complete'));
            }
        }

        return [
            'materialForm' => $materialForm->createView(),
            'careManagerForm' => $careManagerForm->createView(),
        ];
    }

    /**
     * お問い合わせ完了画面.
     *
     * @Route("/material/complete", name="material_complete", methods={"GET"})
     * @Template("Material/complete.twig")
     */
    public function complete()
    {
        return [];
    }
}
