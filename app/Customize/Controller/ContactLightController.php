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
use Customize\Form\Type\Front\ContactLightType;
use Eccube\Repository\PageRepository;
use Eccube\Service\MailService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Eccube\Controller\AbstractController;

class ContactLightController extends AbstractController
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
     * ContactLightController constructor.
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
     * @Route("/contact-light", name="contact_light", methods={"GET", "POST"})
     * @Route("/contact-light", name="contact_light_confirm", methods={"GET", "POST"})
     * @Template("ContactLight/index.twig")
     */
    public function index(Request $request)
    {
        $builder = $this->formFactory->createBuilder(ContactLightType::class);

        if ($this->isGranted('ROLE_USER')) {
            /** @var Customer $user */
            $user = $this->getUser();
            $builder->setData(
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
        }

        $form = $builder->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            switch ($request->get('mode')) {
                case 'confirm':
                    return $this->render('ContactLight/confirm.twig', [
                        'form' => $form->createView(),
                        'Page' => $this->pageRepository->getPageByRoute('contact_light_confirm'),
                    ]);

                case 'complete':
                    $data = $form->getData();

                    // メール送信
                    $this->mailService->sendContactLightMail($data);

                    return $this->redirect($this->generateUrl('contact_light_complete'));
            }
        }

        return [
            'form' => $form->createView(),
        ];
    }

    /**
     * お問い合わせ完了画面.
     *
     * @Route("/contact-light/complete", name="contact_light_complete", methods={"GET"})
     * @Template("ContactLight/complete.twig")
     */
    public function complete()
    {
        return [];
    }
}
