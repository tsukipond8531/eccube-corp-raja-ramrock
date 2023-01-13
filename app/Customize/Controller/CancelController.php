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
use Eccube\Repository\PageRepository;
use Eccube\Service\MailService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

use Eccube\Controller\AbstractController;
use Customize\Form\Type\Front\CancelType;
use Eccube\Repository\OrderRepository;
use Eccube\Repository\Master\OrderStatusRepository;

class CancelController extends AbstractController
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
     * @var OrderRepository
     */
    private $orderRepository;

    /**
     * @var OrderStatusRepository
     */
    private $orderStatusRepository;

    /**
     * CancelController constructor.
     *
     * @param MailService $mailService
     * @param PageRepository $pageRepository
     * @param OrderRepository $orderRepository
     * @param OrderStatusRepository $orderStatusRepository
     */
    public function __construct(
        MailService $mailService,
        PageRepository $pageRepository,
        OrderRepository $orderRepository,
        OrderStatusRepository $orderStatusRepository)
    {
        $this->mailService = $mailService;
        $this->pageRepository = $pageRepository;
        $this->orderRepository = $orderRepository;
        $this->orderStatusRepository = $orderStatusRepository;
    }

    /**
     * お問い合わせ画面.
     *
     * @Route("/cancel", name="cancel", methods={"GET", "POST"})
     * @Route("/cancel", name="cancel_confirm", methods={"GET", "POST"})
     * @Template("Cancel/index.twig")
     */
    public function index(Request $request)
    {
        $builder = $this->formFactory->createBuilder(CancelType::class);
        $Orders = null;

        if ($this->isGranted('ROLE_USER')) {
            /** @var Customer $user */
            $user = $this->getUser();
            $builder->setData(
                [
                    'customer_id' => $user->getId(),
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
            
            $Orders = $this->orderRepository
                ->getActiveOrdersByCustomer($user)
                ->getQuery()
                ->getResult();
        }

        // FRONT_CANCEL_INDEX_INITIALIZE
        $event = new EventArgs(
            [
                'builder' => $builder,
            ],
            $request
        );
        // $this->eventDispatcher->dispatch(EccubeEvents::FRONT_CANCEL_INDEX_INITIALIZE, $event);

        $form = $builder->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            switch ($request->get('mode')) {
                case 'confirm':
                    return $this->render('Cancel/confirm.twig', [
                        'form' => $form->createView(),
                        'Page' => $this->pageRepository->getPageByRoute('cancel_confirm'),
                        'Orders' => $Orders,
                    ]);

                case 'complete':
                    $data = $form->getData();
                    $cancelOrders = explode(',', $form['cancel_orders']->getData());

                    foreach($Orders as $Order) {
                        if (in_array($Order->getId(), $cancelOrders)) {
                            $Order->setOrderStatus($this->orderStatusRepository->find(\Eccube\Entity\Master\OrderStatus::CANCEL));

                            $this->entityManager->persist($Order);
                        }
                    }
                    $this->entityManager->flush();

                    $event = new EventArgs(
                        [
                            'form' => $form,
                            'data' => $data,
                        ],
                        $request
                    );
                    // $this->eventDispatcher->dispatch(EccubeEvents::FRONT_CANCEL_INDEX_COMPLETE, $event);

                    $data = $event->getArgument('data');

                    // メール送信
                    $this->mailService->sendCancelMail($data);

                    return $this->redirect($this->generateUrl('cancel_complete'));
            }
        }

        return [
            'form' => $form->createView(),
            'Orders' => $Orders,
        ];
    }

    /**
     * お問い合わせ完了画面.
     *
     * @Route("/cancel/complete", name="cancel_complete", methods={"GET"})
     * @Template("Cancel/complete.twig")
     */
    public function complete()
    {
        return [];
    }
}
