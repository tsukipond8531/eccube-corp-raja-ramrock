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

use Eccube\Controller\AbstractController;
use Eccube\Entity\BaseInfo;
use Eccube\Entity\Master\CustomerStatus;
use Eccube\Event\EccubeEvents;
use Eccube\Event\EventArgs;
use Eccube\Form\Type\Front\EntryType;
use Eccube\Repository\BaseInfoRepository;
use Eccube\Repository\CustomerRepository;
use Eccube\Repository\Master\CustomerStatusRepository;
use Eccube\Repository\PageRepository;
use Eccube\Service\CartService;
use Eccube\Service\MailService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception as HttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnsupportedMediaTypeHttpException;

use Symfony\Component\HttpFoundation\File\File;
use Customize\Form\Type\Front\EnqueteType;

class EntryController extends AbstractController
{
    /**
     * @var CustomerStatusRepository
     */
    protected $customerStatusRepository;

    /**
     * @var ValidatorInterface
     */
    protected $recursiveValidator;

    /**
     * @var MailService
     */
    protected $mailService;

    /**
     * @var BaseInfo
     */
    protected $BaseInfo;

    /**
     * @var CustomerRepository
     */
    protected $customerRepository;

    /**
     * @var EncoderFactoryInterface
     */
    protected $encoderFactory;

    /**
     * @var TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * @var \Eccube\Service\CartService
     */
    protected $cartService;

    /**
     * @var PageRepository
     */
    protected $pageRepository;

    /**
     * EntryController constructor.
     *
     * @param CartService $cartService
     * @param CustomerStatusRepository $customerStatusRepository
     * @param MailService $mailService
     * @param BaseInfoRepository $baseInfoRepository
     * @param CustomerRepository $customerRepository
     * @param EncoderFactoryInterface $encoderFactory
     * @param ValidatorInterface $validatorInterface
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(
        CartService $cartService,
        CustomerStatusRepository $customerStatusRepository,
        MailService $mailService,
        BaseInfoRepository $baseInfoRepository,
        CustomerRepository $customerRepository,
        EncoderFactoryInterface $encoderFactory,
        ValidatorInterface $validatorInterface,
        TokenStorageInterface $tokenStorage,
        PageRepository $pageRepository
    ) {
        $this->customerStatusRepository = $customerStatusRepository;
        $this->mailService = $mailService;
        $this->BaseInfo = $baseInfoRepository->get();
        $this->customerRepository = $customerRepository;
        $this->encoderFactory = $encoderFactory;
        $this->recursiveValidator = $validatorInterface;
        $this->tokenStorage = $tokenStorage;
        $this->cartService = $cartService;
        $this->pageRepository = $pageRepository;
    }

    /**
     * 会員登録画面.
     *
     * @Route("/entry", name="entry", methods={"GET", "POST"})
     * @Route("/entry", name="entry_confirm", methods={"GET", "POST"})
     * @Template("Entry/index.twig")
     */
    public function index(Request $request)
    {
        if ($this->isGranted('ROLE_USER')) {
            log_info('認証済のためログイン処理をスキップ');

            return $this->redirectToRoute('mypage');
        }

        /** @var $Customer \Eccube\Entity\Customer */
        $Customer = $this->customerRepository->newCustomer();

        /* @var $builder \Symfony\Component\Form\FormBuilderInterface */
        $builder = $this->formFactory->createBuilder(EntryType::class, $Customer);
        $enqueteForm = $this->formFactory->createBuilder(EnqueteType::class)->getForm();

        $event = new EventArgs(
            [
                'builder' => $builder,
                'Customer' => $Customer,
            ],
            $request
        );
        $this->eventDispatcher->dispatch(EccubeEvents::FRONT_ENTRY_INDEX_INITIALIZE, $event);

        /* @var $form \Symfony\Component\Form\FormInterface */
        $form = $builder->getForm();

        $form->handleRequest($request);
        $enqueteForm->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid() && $enqueteForm->isSubmitted() && $enqueteForm->isValid()) {
            switch ($request->get('mode')) {
                case 'confirm':
                    log_info('会員登録確認開始');
                    log_info('会員登録確認完了');

                    // $add_images = $form->get('add_images')->getData();
                    // $form['images']->setData($add_images);

                    return $this->render(
                        'Entry/confirm.twig',
                        [
                            'form' => $form->createView(),
                            'enqueteForm' => $enqueteForm->createView(),
                            'Page' => $this->pageRepository->getPageByRoute('entry_confirm'),
                        ]
                    );

                case 'complete':
                    log_info('会員登録開始');

                    $encoder = $this->encoderFactory->getEncoder($Customer);
                    $salt = $encoder->createSalt();
                    $password = $encoder->encodePassword($Customer->getPassword(), $salt);
                    $secretKey = $this->customerRepository->getUniqueSecretKey();

                    $Customer
                        ->setSalt($salt)
                        ->setPassword($password)
                        ->setSecretKey($secretKey)
                        ->setPoint(0);

                    $images = $form->get('add_images')->getData();
                    foreach ($images as $key => $image) {
                        if ($key) {
                            $Customer->setImage2($image);
                            
                            // 移動
                            $file = new File($this->eccubeConfig['eccube_temp_image_dir'].'/'.$image);
                            $file->move($this->eccubeConfig['eccube_save_image_dir']);
                        } else {
                            $Customer->setImage1($image);

                            // 移動
                            $file = new File($this->eccubeConfig['eccube_temp_image_dir'].'/'.$image);
                            $file->move($this->eccubeConfig['eccube_save_image_dir']);
                        }
                    }

                    // $OldCustomer = $this->customerRepository->findOneBy(['email1' => $Customer->getEmail()]);
                    // if ($OldCustomer) {
                    //     // $Customer->setId($OldCustomer->getId());
                    //     $id = $OldCustomer->getId();
                    //     $OldCustomer = $Customer;
                    //     $OldCustomer->setId($Customer->getId());
                    //     $this->entityManager->persist($OldCustomer);
                    // }

                    $this->entityManager->persist($Customer);
                    $this->entityManager->flush();

                    log_info('会員登録完了');

                    $event = new EventArgs(
                        [
                            'form' => $form,
                            'enqueteForm' => $enqueteForm->createView(),
                            'Customer' => $Customer,
                        ],
                        $request
                    );
                    $this->eventDispatcher->dispatch(EccubeEvents::FRONT_ENTRY_INDEX_COMPLETE, $event);

                    $activateFlg = $this->BaseInfo->isOptionCustomerActivate();

                    // 仮会員設定が有効な場合は、確認メールを送信し完了画面表示.
                    if ($activateFlg) {
                        $activateUrl = $this->generateUrl('entry_activate', ['secret_key' => $Customer->getSecretKey()], UrlGeneratorInterface::ABSOLUTE_URL);

                        // メール送信
                        $this->mailService->sendCustomerConfirmMail($Customer, $activateUrl);

                        if ($event->hasResponse()) {
                            return $event->getResponse();
                        }

                        log_info('仮会員登録完了画面へリダイレクト');

                        return $this->redirectToRoute('entry_complete');
                    } else {
                        // 仮会員設定が無効な場合は、会員登録を完了させる.
                        $qtyInCart = $this->entryActivate($request, $Customer->getSecretKey());

                        // URLを変更するため完了画面にリダイレクト
                        return $this->redirectToRoute('entry_activate', [
                            'secret_key' => $Customer->getSecretKey(),
                            'qtyInCart' => $qtyInCart,
                        ]);
                    }
            }
        }

        return [
            'form' => $form->createView(),
            'enqueteForm' => $enqueteForm->createView(),
        ];
    }

    /**
     * 会員登録完了画面.
     *
     * @Route("/entry/complete", name="entry_complete", methods={"GET"})
     * @Template("Entry/complete.twig")
     */
    public function complete()
    {
        return [];
    }

    /**
     * 会員のアクティベート（本会員化）を行う.
     *
     * @Route("/entry/activate/{secret_key}/{qtyInCart}", name="entry_activate", methods={"GET"})
     * @Template("Entry/activate.twig")
     */
    public function activate(Request $request, $secret_key, $qtyInCart = null)
    {
        $errors = $this->recursiveValidator->validate(
            $secret_key,
            [
                new Assert\NotBlank(),
                new Assert\Regex(
                    [
                        'pattern' => '/^[a-zA-Z0-9]+$/',
                    ]
                ),
            ]
        );

        if (!is_null($qtyInCart)) {
            return [
                'qtyInCart' => $qtyInCart,
            ];
        } elseif ($request->getMethod() === 'GET' && count($errors) === 0) {
            // 会員登録処理を行う
            $qtyInCart = $this->entryActivate($request, $secret_key);

            return [
                'qtyInCart' => $qtyInCart,
            ];
        }

        throw new HttpException\NotFoundHttpException();
    }

    /**
     * 会員登録処理を行う
     *
     * @param Request $request
     * @param $secret_key
     *
     * @return \Eccube\Entity\Cart|mixed
     */
    private function entryActivate(Request $request, $secret_key)
    {
        log_info('本会員登録開始');
        $Customer = $this->customerRepository->getProvisionalCustomerBySecretKey($secret_key);
        if (is_null($Customer)) {
            throw new HttpException\NotFoundHttpException();
        }

        $CustomerStatus = $this->customerStatusRepository->find(CustomerStatus::REGULAR);
        $Customer->setStatus($CustomerStatus);
        $this->entityManager->persist($Customer);
        $this->entityManager->flush();

        log_info('本会員登録完了');

        $event = new EventArgs(
            [
                'Customer' => $Customer,
            ],
            $request
        );
        $this->eventDispatcher->dispatch(EccubeEvents::FRONT_ENTRY_ACTIVATE_COMPLETE, $event);

        // メール送信
        $this->mailService->sendCustomerCompleteMail($Customer);

        // Assign session carts into customer carts
        $Carts = $this->cartService->getCarts();
        $qtyInCart = 0;
        foreach ($Carts as $Cart) {
            $qtyInCart += $Cart->getTotalQuantity();
        }

        // 本会員登録してログイン状態にする
        $token = new UsernamePasswordToken($Customer, null, 'customer', ['ROLE_USER']);
        $this->tokenStorage->setToken($token);
        $request->getSession()->migrate(true);

        if ($qtyInCart) {
            $this->cartService->save();
        }

        log_info('ログイン済に変更', [$this->getUser()->getId()]);

        return $qtyInCart;
    }

    /**
     * @Route("/entry/image/add", name="entry_image_add", methods={"POST"})
     */
    public function addImage(Request $request)
    {
        if (!$request->isXmlHttpRequest() && $this->isTokenValid()) {
            throw new BadRequestHttpException();
        }

        $images = $request->files->get('entry');

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
