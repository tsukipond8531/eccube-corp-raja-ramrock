<?php

/*
 * Copyright(c) 2020 Shadow Enterprise, Inc. All rights reserved.
 * http://www.shadow-ep.co.jp/
 */

namespace Plugin\SeEnquete4\Controller;

use Eccube\Common\Constant;
use Eccube\Controller\AbstractController;
use Eccube\Repository\BaseInfoRepository;
use Knp\Bundle\PaginatorBundle\Pagination\SlidingPagination;
use Knp\Component\Pager\Paginator;
use Plugin\SeEnquete4\Entity\EnqueteUser;
use Plugin\SeEnquete4\Form\Type\EnqueteType;
use Plugin\SeEnquete4\Repository\EnqueteRepository;
use Plugin\SeEnquete4\Repository\EnqueteConfigRepository;
use Plugin\SeEnquete4\Repository\EnqueteItemRepository;
use Plugin\SeEnquete4\Repository\EnqueteMetaRepository;
use Plugin\SeEnquete4\Service\EnqueteMailService;
use Plugin\SeEnquete4\Util\CommonUtil;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Eccube\Service\MailService;


!defined('ENQUETE_ID') && define('ENQUETE_ID', 2);

class EnqueteController extends AbstractController
{

    /**
     * @var BaseInfo
     */
    public $BaseInfo;

    /**
     * @var \Swift_Mailer
     */
    protected $mailer;

    /**
     * @var EnqueteRepository
     */
    protected $enqueteRepository;

    /**
     * @var EnqueteConfigRepository
     */
    protected $enqueteConfigRepository;

    /**
     * @var EnqueteItemRepository
     */
    protected $enqueteItemRepository;

    /**
     * @var EnqueteMetaRepository
     */
    protected $enqueteMetaRepository;

    /**
     * @var EnqueteMailService
     */
    protected $enqueteMailService;

    /*
     * enqueteConfig
     */
    protected $enqueteConfig;

    /**
     * @var MailService
     */
    protected $mailService;

    /**
     * EnqueteController constructor.
     *
     * @param \Swift_Mailer $mailer
     * @param BaseInfoRepository $baseInfoRepository
     * @param EnqueteRepository $enqueteRepository
     * @param EnqueteConfig $enqueteConfigRepository
     * @param EnqueteItemRepository $enqueteItemRepository
     * @param EnqueteMetaRepository $enqueteMetaRepository
     * @param EnqueteMailService $enqueteMailService
     */
    public function __construct(
        \Swift_Mailer $mailer,
        BaseInfoRepository $baseInfoRepository,
        EnqueteRepository $enqueteRepository,
        EnqueteConfigRepository $enqueteConfigRepository,
        EnqueteItemRepository $enqueteItemRepository,
        EnqueteMetaRepository $enqueteMetaRepository,
        MailService $mailService,
        EnqueteMailService $enqueteMailService
    ) {
        $this->mailer = $mailer;
        $this->BaseInfo = $baseInfoRepository->get();
        $this->enqueteRepository = $enqueteRepository;
        $this->enqueteConfigRepository = $enqueteConfigRepository;
        $this->enqueteItemRepository = $enqueteItemRepository;
        $this->enqueteMetaRepository = $enqueteMetaRepository;
        $this->enqueteMailService = $enqueteMailService;
        $this->mailService = $mailService;

        // 設定情報を取得
        $this->enqueteConfig = $this->enqueteConfigRepository->findBy( [], [ 'sort_no' => 'ASC' ] );
    }

    /**
     * アンケート一覧画面
     *
     * @Route("/enquete/list", name="se_enquete_list")
     * @Route("/enquete/list/{page}", name="se_enquete_list_page", requirements={"page" = "\d+"})
     * @Template("@SeEnquete4/list.twig")
     */
    public function index(Request $request, $page = 1)
    {

        $now = new \DateTime(date("Y-m-d H:i:s"), new \DateTimeZone('Asia/Tokyo'));

        $whereLists = [
            'status' => 1,
            'start_date' => [ '<=', $now ],
            'end_date' => [ '>', $now ],
            'deleted' => 0
        ];

        // 会員でなければ会員専用は除外
        if ( !$this->getUser() ) {
            $whereLists[ 'member_flg' ] = 0 ;
        }

        $limit = 0;
        $offset = 0;
        $Collection = $this->enqueteRepository->getFindCollection( $whereLists, [ 'start_date' => 'DESC'], $limit, $offset, $to_camel_case=false, $ucfirst=false );

        return [
            'Collection' => $Collection,
        ];
    }

    /**
     * アンケート詳細画面
     *
     * @Route("/enquete/detail/{id}", name="se_enquete_detail", requirements={"id" = "\d+"})
     * @Template("@SeEnquete4/detail.twig")
     *
     * @param Request $request
     *
     * @return array
     */
    public function detail(Request $request, $id = null)
    {

        if ( !empty($id) && is_numeric($id) ) {

            $now = new \DateTime(date("Y-m-d H:i:s"), new \DateTimeZone('Asia/Tokyo'));

            $Collection = $this->enqueteRepository->getFindCollection([
                'id' => $id,
                'status' => 1,
                'start_date' => [ '<=', $now ],
                'end_date' => [ '>', $now ],
                'deleted' => 0
            ], [], $limit=1, $offset=0, $to_camel_case=false, $ucfirst=false );

            $enqueteEntity = ( $Collection && isset($Collection[0]) ) ? $Collection[0] : [] ;

        }

        // アンケートがなければNotFound
        if ( !isset($enqueteEntity) || !$enqueteEntity ) {
            throw new NotFoundHttpException();
        }

        // 非会員が会員専用にアクセスしてきたらNotFound
        if ( ( $enqueteEntity->getMemberFlg() == 1 ) && ( !$this->getUser() ) ) {
            throw new NotFoundHttpException();
        }

        // 設問情報を取得
        $enqueteMetaEntities = $this->enqueteMetaRepository->findBy([
            'Enquete' => $enqueteEntity,
            'deleted' => 0
        ], [ 'sort_no' => 'ASC' ]);

        // ページで使用するフォームの作成
        $form = $this->createForm(EnqueteType::class);
        $keyList = [];

        // データ登録時に使用できるように別配列へ id => title を格納する
        $metaInfo = $itemInfo = [];

        // 必須のみ確認出来るように配列へ
        $metaNessIds = [];

        if ( $enqueteMetaEntities ) {
            foreach ( $enqueteMetaEntities as $enqueteMetaEntity ) {
                $enqueteItemEntities = $this->enqueteItemRepository->findBy([
                    'EnqueteMeta' => $enqueteMetaEntity,
                    'deleted' => 0,
                ], [ 'sort_no' => 'ASC' ]);

                // フォームを動的追加
                list($form, $keyList) = $this->getAddForms($form, $keyList, $enqueteMetaEntity, $enqueteItemEntities);

                $metaInfo[ $enqueteMetaEntity->getId() ] = $enqueteMetaEntity->getTitle() ;
                if ( $enqueteItemEntities ) {
                    foreach ( $enqueteItemEntities as $enqueteItemEntity ) {
                        $itemInfo[ $enqueteItemEntity->getId() ] = $enqueteItemEntity->getValues() ;
                    }
                }

                if ( $enqueteMetaEntity->getNessFlg() == 1 ) {
                    $metaNessIds[ $enqueteMetaEntity->getId() ] = true;
                }
            }
        }

        // 個人情報の取得が存在すればチェックボックスを追加する
        if ( $enqueteEntity->getPersonalFlg() == 1 ) {
            $extraKey = EnqueteType::_FORM_NAME_PREFIX_ .'personal';

            // 個人情報の取扱い同意
            $form->add($extraKey, CheckboxType::class, [
                'mapped' => false,
                'required' => true,
                'label' => ( $enqueteEntity->getPersonalTitle() ) ? $enqueteEntity->getPersonalTitle() : trans('se_enquete.common.message.agree', [ '%label%' => trans('se_enquete.admin.common.enquete_personal_text') ]),
            ]);

            $keyList[] = [
                'key' => $extraKey,
                //'label' => ( $enqueteEntity->getPersonalTitle() ) ? $enqueteEntity->getPersonalTitle() : trans('se_enquete.common.message.agree', [ '%label%' => trans('se_enquete.admin.common.enquete_personal_text') ]),
                'required' => true,
            ];
        }

        // リクエストの内容をマージ
        $form->handleRequest($request);

        if ( $form->isSubmitted() && $form->isValid() ) {
            // 自身のフォームの全てのフォームを取得
            $formData  = $request->request->get($form->getName());

            // 閲覧時に参照しやすいように配列へ移す(管理側でFormを変更するとおかしくなるので現状のデータで保存する)
            $userData = [];
            foreach ( $formData as $key => $value ) {
                if ( substr($key, 0, strlen(EnqueteType::_FORM_NAME_PREFIX_)) == EnqueteType::_FORM_NAME_PREFIX_ ) {
                    $metaId = substr($key, strlen(EnqueteType::_FORM_NAME_PREFIX_));
                    if ( is_numeric($metaId) && isset($metaInfo[$metaId]) ) {
                        if ( is_array($value) ) {
                            $new_values = [];
                            foreach ( $value as $vkey => $vvalue ) {
                                if ( isset($itemInfo[$vvalue]) ) {
                                    $new_values[] = $itemInfo[$vvalue] ;
                                } else {
                                    $new_values[] = $vvalue ;
                                }
                            }
                            $userData[ $metaInfo[$metaId] ] = $new_values ;
                        } else {
                            if ( isset($itemInfo[$value]) ) {
                                $userData[ $metaInfo[$metaId] ] = $itemInfo[$value] ;
                            } else {
                                $userData[ $metaInfo[$metaId] ] = $value ;
                            }
                        }
                        if ( isset($metaNessIds[$metaId]) ) unset( $metaNessIds[$metaId] ) ;
                    } else
                    if ( $metaId == 'personal' ) {
                        $userData[ trans('se_enquete.common.label.personal_existance') ] = trans('se_enquete.common.label.agree');
                    }
                }
            }

            // 必須配列が空で無ければ未入力項目が存在する
            if ( isset($metaNessIds) && !empty($metaNessIds) ) {
                foreach ( $enqueteMetaEntities as $enqueteMetaEntity ) {
                    if ( isset($metaNessIds[$enqueteMetaEntity->getId()]) ) {
                        switch ( $enqueteMetaEntity->getEnqueteConfig()->getKeyword() ) {
                            case 'pulldown':
                            case 'radio':
                            case 'check':
                                $message = trans('se_enquete.common.message.nochoice', [ '%label%' => $enqueteMetaEntity->getTitle() ]);
                                break;
                            default:
                                $message = trans('se_enquete.common.message.empty', [ '%label%' => $enqueteMetaEntity->getTitle() ]);
                                break;
                        }
                        $form->get( EnqueteType::_FORM_NAME_PREFIX_ .$enqueteMetaEntity->getId() )->addError( new FormError( $message, $message ) );
                    }
                }
            }

            // エラーが無ければ登録
            if ( count($form->getErrors(true)) < 1 ) {

                $now = new \DateTime(date("Y-m-d H:i:s"), new \DateTimeZone('Asia/Tokyo'));

                $enqueteUserEntity = new EnqueteUser();

                $entityManager = $this->getDoctrine()->getManager();

                $answer_flg = false;

                try {
                    $entityManager->beginTransaction();

                    $enqueteUserEntity
                        ->setEnquete( $enqueteEntity )
                        ->setAnswerJson( $userData )
                        ->setUserAgent( $request->server->get('HTTP_USER_AGENT') )
                        ->setIp( $request->server->get('REMOTE_ADDR') )
                        ->setCreateDate( $now )
                        ->setUpdateDate( $now )
                        ->setDeleted( 0 );

                    $Customer = $this->getUser();
                    if ( $Customer ) {
                        $enqueteUserEntity->setCustomerId( $Customer->getId() );
                        $this->mailService->sendEnqueteMail($Customer, $userData);
                    } else {
                        $enqueteUserEntity->setCustomerId( 0 );
                    }

                    $entityManager->persist($enqueteUserEntity);
                    $entityManager->flush();

                    $answer_flg = true;

                    $entityManager->commit();

                } catch ( Exception $e ) {

                    $entityManager->rollback();

                }

                // 正常完了の場合で担当者メール送信フラグがあればメールを送信する
                if ( $answer_flg && ( $enqueteEntity->getMailFlg() == 1 ) && ( $enqueteEntity->getAddressList() != '' ) ) {

                    // 回答の旨メール送信開始
                    $result = $this->enqueteMailService->sendEnqueteManageMail( $enqueteEntity->getId() );

                }

                // DB登録の有無に関わらず完了ページへ遷移させる
                return $this->redirectToRoute('se_enquete_complete');

            }
        }


        return [
            'form' => $form->createView(),
            'keyList' => $keyList,
            'Enquete' => $enqueteEntity,
            'personaliCheckKey' => ( isset($extraKey) ) ? $extraKey : '',
        ];
    }

    /**
     * アンケート完了画面
     *
     * @Route("/enquete/complete", name="se_enquete_complete")
     * @Template("@SeEnquete4/complete.twig")
     */
    public function complete(Request $request)
    {

        return [];

    }

    /**
     * アンケートブロック取得
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @Route("/enquete/block/{id}", name="se_enquete_block", requirements={"id" = "\d+"})
     *   return json
     */
    public function block(Request $request, $id)
    {

        $output = [ 'status' => false ];

        if ( !empty($id) && is_numeric($id) ) {

            $now = new \DateTime(date("Y-m-d H:i:s"), new \DateTimeZone('Asia/Tokyo'));

            $Collection = $this->enqueteRepository->getFindCollection([
                'id' => $id,
                'status' => 1,
                'start_date' => [ '<=', $now ],
                'end_date' => [ '>', $now ],
                'deleted' => 0
            ], [], $limit=1, $offset=0, $to_camel_case=false, $ucfirst=false );

            $enqueteEntity = ( $Collection && isset($Collection[0]) ) ? $Collection[0] : [] ;

        }

        // 情報なし
        if ( !isset($enqueteEntity) || !$enqueteEntity ) {
            return $this->json($output);
        }

        // 会員専用
        if ( ( $enqueteEntity->getMemberFlg() == 1 ) && ( !$this->getUser() ) ) {
            return $this->json($output);
        }

        // 画像の有無
        if ( $enqueteEntity->getThumbnail() ) {
            $uploadDir = $this->get('kernel')->getProjectDir() . '/' .$this->eccubeConfig['Se_Enquete_img_upload_dir'];
            if ( file_exists( $uploadDir .$enqueteEntity->getThumbnail() ) ) {
                $imageSize = getimagesize( $uploadDir .$enqueteEntity->getThumbnail() );
                $thumbnail = '/' .$this->eccubeConfig['Se_Enquete_img_upload_dir'] .$enqueteEntity->getThumbnail();
            }
        }

        $genetareUrl = $this->generateUrl('se_enquete_detail', [ 'id' => $enqueteEntity->getId() ]);

        // HTMLタグの構成
        $returnHtml = '';
        $returnHtml .= '<a href="' .$genetareUrl .'">';
        if ( isset($thumbnail) ) {
          $baseStyle = 'max-width:' .$imageSize[0] .'px; max-height:' .$imageSize[1] .'px; width: 100%;';
          $returnHtml .= '<p class="enquete-list-img"><img src="' .$thumbnail .'" style="' .$baseStyle .'" alt="' .str_replace('"', '', $enqueteEntity->getTitle() ) .'"/></p>';
        } else {
          $returnHtml .= '<p class="enquete-list-title">' .$enqueteEntity->getTitle() .'</p>';
        }
        $returnHtml .= '</a>';
        if ( $enqueteEntity->getSubTitle() ) {
            $returnHtml .= '<span class="enquete-list-catch">' .$enqueteEntity->getSubTitle() .'</span>';
        }
        if ( $enqueteEntity->getStartDate() ) {
            $returnHtml .= '<div class="col text-right enquete-list-term">';
            $returnHtml .= $enqueteEntity->getStartDate()->format('Y/m/d') .' 〜 ';
            if ( $enqueteEntity->getEndDate() ) {
                $returnHtml .= $enqueteEntity->getEndDate()->format('Y/m/d');
            }
            $returnHtml .= '</div>';
        }

        $output = [ 
            'status' => true,
            'html' => $returnHtml 
        ];

        return $this->json($output);

    }

    /*
     * アンケート用のフォームを動的に生成する
     *  key命名規則 : prefix + metaId
     */
    private function getAddForms( $form, $keyList=[], $enqueteMetaEntity=[], $enqueteItemEntities=[] ) // {{{
    {

        $keyPrefix = EnqueteType::_FORM_NAME_PREFIX_;

        $childList = [];
        if ( $enqueteItemEntities ) {
            foreach ( $enqueteItemEntities as $enqueteItemEntity ) {
                //$childList[] = [
                //    $enqueteItemEntity->getValues() => $enqueteItemEntity->getId(),
                //];
                $childList[$enqueteItemEntity->getValues()] = $enqueteItemEntity->getId();
            }
        }

        $form_add_flg = true;

        switch ( $enqueteMetaEntity->getEnqueteConfig()->getKeyword() ) {
            case 'text':
                $form
                    ->add($keyPrefix .$enqueteMetaEntity->getId(), TextType::class, [
                        //'mapped' => false,
                        'label' => $enqueteMetaEntity->getTitle(),
                        'required' => ( $enqueteMetaEntity->getNessFlg() == 1 ) ? true : false,
                        'attr' => [
                            'placeholder' => $enqueteMetaEntity->getPlaceholder(),
                            'class' => 'enquete-form-'.$enqueteMetaEntity->getEnqueteConfig()->getKeyword(),
                        ],
                    ]);
                break;
            case 'textarea':
                $form
                    ->add($keyPrefix .$enqueteMetaEntity->getId(), TextareaType::class, [
                        //'mapped' => false,
                        'label' => $enqueteMetaEntity->getTitle(),
                        'required' => ( $enqueteMetaEntity->getNessFlg() == 1 ) ? true : false,
                        'attr' => [
                            'placeholder' => $enqueteMetaEntity->getPlaceholder(),
                            'class' => 'enquete-form-'.$enqueteMetaEntity->getEnqueteConfig()->getKeyword(),
                        ],
                    ]);
                break;
            case 'pulldown':

                // 文字列の長さからフォームのクラスを指定する
                $max_length = 0;
                foreach ( $childList as $ckey => $cvalue ) {
                    if ( mb_strlen($ckey) > $max_length ) $max_length = mb_strlen($ckey) ;
                }

                $add_class = '';
                if ( $max_length <= 11 ) { $add_class .= ' mw-2'; }
                else if ( $max_length <= 25 ) { $add_class .= ' mw-4'; }
                else if ( $max_length <= 40 ) { $add_class .= ' mw-6'; }
                else if ( $max_length <= 54 ) { $add_class .= ' mw-8'; }

                $form
                    ->add($keyPrefix .$enqueteMetaEntity->getId(), ChoiceType::class, [
                        //'mapped' => false,
                        'label' => $enqueteMetaEntity->getTitle(),
                        'required' => ( $enqueteMetaEntity->getNessFlg() == 1 ) ? true : false,
                        'choices' => $childList,
                        'expanded' => false,
                        'multiple' => false,
                        //'data' => $childList[array_key_first($childList)],
                        'placeholder' => '----',
                        'attr' => [
                            'class' => 'enquete-form-'.$enqueteMetaEntity->getEnqueteConfig()->getKeyword() .$add_class 
                        ],
                    ]);
                break;
            case 'radio':
                $form
                    ->add($keyPrefix .$enqueteMetaEntity->getId(), ChoiceType::class, [
                        //'mapped' => false,
                        'label' => $enqueteMetaEntity->getTitle(),
                        'required' => ( $enqueteMetaEntity->getNessFlg() == 1 ) ? true : false,
                        'choices' => $childList,
                        'expanded' => true,
                        'multiple' => false,
                        //'data' => $childList[array_key_first($childList)],
                        'placeholder' => false,
                        'attr' => [
                            'class' => 'check-thumbnail enquete-form-'.$enqueteMetaEntity->getEnqueteConfig()->getKeyword()
                        ],
                    ]);
                break;
            case 'check':
                $form
                    ->add($keyPrefix .$enqueteMetaEntity->getId(), ChoiceType::class, [
                        //'mapped' => false,
                        'label' => $enqueteMetaEntity->getTitle(),
                        'required' => ( $enqueteMetaEntity->getNessFlg() == 1 ) ? true : false,
                        'choices' => $childList,
                        'expanded' => true,
                        'multiple' => true,
                        //'data' => $childList[array_key_first($childList)],
                        'placeholder' => false,
                        'attr' => [
                            'class' => 'enquete-form-'.$enqueteMetaEntity->getEnqueteConfig()->getKeyword()
                        ],
                    ]);
                break;
            case 'etc':
                $form_add_flg = false;

                // イレギュラー（フォームなし）
                $keyList[] = [
                    'text' => $enqueteMetaEntity->getText(),
                ];

                break;
            default:
                $form_add_flg = false;
                break;
        }

        if ( $form_add_flg ) {
            $keyList[] = [
                'key' => $keyPrefix .$enqueteMetaEntity->getId(),
                'label' => $enqueteMetaEntity->getTitle(),
                'required' => ( $enqueteMetaEntity->getNessFlg() == 1 ) ? true : false,
            ];
        }

        return [ $form, $keyList ];

    } // }}}


}
