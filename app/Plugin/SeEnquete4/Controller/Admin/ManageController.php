<?php

/*
 * Copyright(c) 2020 Shadow Enterprise, Inc. All rights reserved.
 * http://www.shadow-ep.co.jp/
 */

namespace Plugin\SeEnquete4\Controller\Admin;

use Eccube\Common\Constant;
use Eccube\Controller\AbstractController;
use Eccube\Repository\Master\PageMaxRepository;
use Eccube\Repository\CustomerRepository;
use Eccube\Service\CsvExportService;
use Eccube\Util\FormUtil;
use Knp\Component\Pager\Paginator;
use Knp\Component\Pager\PaginatorInterface;
use Plugin\SeEnquete4\Entity\Enquete;
use Plugin\SeEnquete4\Entity\EnqueteConfig;
use Plugin\SeEnquete4\Entity\EnqueteItem;
use Plugin\SeEnquete4\Entity\EnqueteMeta;
use Plugin\SeEnquete4\Entity\EnqueteUser;
use Plugin\SeEnquete4\Form\Type\Admin\AddFormContentType;
use Plugin\SeEnquete4\Form\Type\Admin\ManageEditType;
use Plugin\SeEnquete4\Form\Type\Admin\SearchManageType;
use Plugin\SeEnquete4\Repository\EnqueteRepository;
use Plugin\SeEnquete4\Repository\EnqueteConfigRepository;
use Plugin\SeEnquete4\Repository\EnqueteItemRepository;
use Plugin\SeEnquete4\Repository\EnqueteMetaRepository;
use Plugin\SeEnquete4\Repository\EnqueteUserRepository;
use Plugin\SeEnquete4\Util\CommonUtil;
use Plugin\SeEnquete4\Util\ValidateUtil;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * アンケート管理
 */
class ManageController extends AbstractController
{

    const _DEFAULT_CHILD_ROW_ = 3;

    /**
     * @var CsvExportService
     */
    protected $csvExportService;

    /**
     * @var PageMaxRepository
     */
    protected $pageMaxRepository;

    /**
     * @var CustomerRepository
     */
    protected $customerRepository;

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
     * @var EnqueteUserRepository
     */
    protected $enqueteUserRepository;

    /**
     * ManageController constructor.
     *
     * @param CsvExportService $csvExportService
     * @param EnqueteRepository $enqueteRepository
     * @param EnqueteConfigRepository $enqueteConfigRepository
     * @param EnqueteItemRepository $enqueteItemRepository
     * @param EnqueteMetaRepository $enqueteMetaRepository
     * @param EnqueteUserRepository $enqueteUserRepository
     */
    public function __construct(
        CsvExportService $csvExportService,
        PageMaxRepository $pageMaxRepository,
        CustomerRepository $customerRepository,
        EnqueteRepository $enqueteRepository,
        EnqueteConfigRepository $enqueteConfigRepository,
        EnqueteItemRepository $enqueteItemRepository,
        EnqueteMetaRepository $enqueteMetaRepository,
        EnqueteUserRepository $enqueteUserRepository
    ) {
        $this->csvExportService = $csvExportService;
        $this->pageMaxRepository = $pageMaxRepository;
        $this->customerRepository = $customerRepository;
        $this->enqueteRepository = $enqueteRepository;
        $this->enqueteConfigRepository = $enqueteConfigRepository;
        $this->enqueteItemRepository = $enqueteItemRepository;
        $this->enqueteMetaRepository = $enqueteMetaRepository;
        $this->enqueteUserRepository = $enqueteUserRepository;
    }

    /**
     * アンケート一覧画面
     *
     * @Route("/%eccube_admin_route%/enquete/manage", name="se_enquete_admin_manage_index")
     * @Route("/%eccube_admin_route%/enquete/manage/{page_no}", requirements={"page_no" = "\d+"}, name="se_enquete_admin_manage_index_pageno")
     * @Template("@SeEnquete4/admin/Manage/index.twig")
     */
    public function index( Request $request, $page_no = 1, PaginatorInterface $paginator )
    {

        $searchForm = $this->createForm(SearchManageType::class);

        if ( !is_numeric($page_no) || ($page_no < 1) ) $page_no = 1 ;

        $page_count = $this->eccubeConfig['Se_Enquete_admin_manage_limit'];

        $searchForm->handleRequest($request);

        $searchData = [];

        if ( $searchForm->isSubmitted() && $searchForm->isValid() ) {
            $searchParams = $request->request->get($searchForm->getName());

            foreach ( $searchParams as $searchKey => $searchValue ) {
                if ( strlen($searchValue) < 1 ) continue ;
                switch ( $searchKey ) {
                    case 'keyword':
                        $searchData[ 'or' ] = [ 'title' => [ 'like', $searchValue ], 'sub_title' => [ 'like', $searchValue ] ];
                        break;
                    case 'start_date':
                        $searchData[ 'start_date' ] = [ 'ge', $searchValue ];
                        break;
                    case 'end_date':
                        $searchData[ 'end_date' ] = [ 'le', $searchValue ];
                        break;
                    case 'status':
                        $searchData[ 'status' ] = $searchValue;
                        break;
                }
            }
        }

        $searchData[ 'deleted' ] = 0 ;
        $enqueteEntities = $this->enqueteRepository->getFindCollection($searchData, [ 'id' => 'DESC' ], null, null, false, false );

        $pagination = $paginator->paginate(
            $enqueteEntities,
            $page_no,
            $page_count
        );

        return [
            'searchForm' => $searchForm->createView(),
            'pagination' => $pagination,
            'list_memberFlg' => CommonUtil::replaceTrans( $this->eccubeConfig['Se_Enquete_master_list']['list_memberFlg'] ),
            'list_mailFlg' => CommonUtil::replaceTrans( $this->eccubeConfig['Se_Enquete_master_list']['list_mailFlg'] ),
            'list_status' => CommonUtil::replaceTrans( $this->eccubeConfig['Se_Enquete_master_list']['list_status'] ),
            'list_personalFlg' => CommonUtil::replaceTrans( $this->eccubeConfig['Se_Enquete_master_list']['list_personalFlg'] ),
        ];
    }

    /**
     * アンケート新規・編集画面
     *
     * @Route("/%eccube_admin_route%/enquete/manage/new", name="se_enquete_admin_manage_new")
     * @Route("/%eccube_admin_route%/enquete/manage/{id}/edit", requirements={"id" = "\d+"}, name="se_enquete_admin_manage_edit")
     * @Template("@SeEnquete4/admin/Manage/edit.twig")
     */
    public function edit( Request $request, $id = null )
    {

        if ( !empty($id) ) {

            $enqueteEntity = $this->enqueteRepository->findOneBy([
                'id' => $id,
                'deleted' => 0,
            ]);

            if ( is_null($enqueteEntity) ) {
                throw new NotFoundHttpException();
            }

        } else {
            $enqueteEntity = new Enquete();
        }

        $enqueteConfig = $this->enqueteConfigRepository->findBy([
                'deleted' => 0
            ], [ 'sort_no' => 'ASC' ]);


        // 設問部分フォームHTMLとID
        $child_formHtml = $child_optionIDs = '';
        $newEditFlg = true;

        $options = [
            'list_memberFlg' => CommonUtil::replaceTrans( $this->eccubeConfig['Se_Enquete_master_list']['list_memberFlg'] ),
            'list_mailFlg' => CommonUtil::replaceTrans( $this->eccubeConfig['Se_Enquete_master_list']['list_mailFlg'] ),
            'list_status' => CommonUtil::replaceTrans( $this->eccubeConfig['Se_Enquete_master_list']['list_status'] ),
            'mime_types' => str_replace( ',', '/', $this->eccubeConfig['Se_Enquete_img_valid_extention'] ),
            'list_personalFlg' => CommonUtil::replaceTrans( $this->eccubeConfig['Se_Enquete_master_list']['list_personalFlg'] ),
        ];

        $form = $this->createForm( ManageEditType::class, $enqueteEntity, $options );

        $form->handleRequest($request);

        if ( $form->isSubmitted() && $form->isValid() ) {
            // 編集フラグをfalseへ変更(編集新規では無い)
            $newEditFlg = false;

            // 自身のフォームの全てのフォームを取得
            $formData  = $request->request->get($form->getName());

            // 独自でバリデーションチェック
            $form = $this->checkValidateEnquete( $form, $formData );

            // エラーが無ければファイルチェック
            if ( count($form->getErrors(true)) < 1 ) {

                // 画像削除フラグの確認
                if ( !empty($request->request->get('thumb_del')) ) {
                    $fileName = '';
                }

                $check = 'thumbnail';
                $uploadFile = $form->get($check)->getData();
                if ( $uploadFile ) {
                    $originalFilename = pathinfo($uploadFile->getClientOriginalName(), PATHINFO_FILENAME);

                    // this is needed to safely include the file name as part of the URL
                    $safeFilename = transliterator_transliterate('Any-Latin; Latin-ASCII; [^A-Za-z0-9_] remove; Lower()', $originalFilename);
                    $newFilename = $safeFilename.'-'.uniqid().'.'.$uploadFile->guessExtension();

                    // Move the file to the directory where brochures are stored
                    try {
                        $uploadFile->move(
                            //$this->getParameter('brochures_directory'),
                            $this->eccubeConfig['Se_Enquete_img_upload_dir'],
                            $newFilename
                        );
                        // set newFilename
                        $fileName = $newFilename;
                    } catch (FileException $e) {
                        // ... handle exception if something happens during file upload
                        $message = trans('se_enquete.common.message.failed_upload', [ '%label%' => trans('se_enquete.admin.common.enquete_' .$check) ]);
                        $form->get($check)->addError( new FormError( $message, $message ) );
                    }
                }
            }

            // エラーがあっても無くても設問部分のチェック
            $child_error = true;
            $child_entity_array = [];
            $optionids = $request->request->get('optionids');

            if ( $optionids && $explode = explode(',', $optionids) ) {
                foreach ( $explode as $uniq_id ) {
                    if ( empty($uniq_id) ) continue ;
                    $real_uniqid = str_replace('sort_', '', $uniq_id);

                    $editChildRow = 1;  // self::_DEFAULT_CHILD_ROW_;    /* この後自動追加するので1件だけをデフォルトに変更する */

                    $child_options = [ 
                        'identification_id' => $real_uniqid,
                        'list_required' => CommonUtil::replaceTrans( $this->eccubeConfig['Se_Enquete_master_list']['list_required'] ),
                        'list_radio' => CommonUtil::replaceTrans( $this->eccubeConfig['Se_Enquete_master_list']['list_radio'] ),
                        //'child_row' => self::_DEFAULT_CHILD_ROW_,
                        'child_row' => $editChildRow,
                    ];

                    $form_child = $this->createForm(AddFormContentType::class, [], $child_options);

                    // 追加された子が存在する場合後からhandleRequest後に追加出来ないのでここで対応する => テンプレ側にも個数を渡す
                    $form_child = $this->checkAddMoreForm( $form_child, $real_uniqid, $request->request->get('add_form_content'), $editChildRow );

                    // request値を追加
                    $form_child->handleRequest($request);

                    // エラーチェック  --> $form で submit されているのでここではValidチェックのみ
                    if ( !$form_child->isValid() ) {
                        $child_error = false;
                    } else {
                        // 自身のフォームの全てのフォームを取得 ※自動追加したフォームは取得できないので注意
                        $child_formData  = $request->request->get($form_child->getName());

                        // 独自でバリデーションチェック - 登録用の調整済み配列 update_keyval / View用の設問件数配列も取得する child_rows [ pulldown_row / radio_row / check_row ]
                        list( $form_child, $update_keyval, $child_rows ) = $this->checkValidateEnqueteChild( $form_child, $child_formData, $real_uniqid, $editChildRow );

                        // そもそものエラーや入力なしの場合はスルーする
                        if ( is_null($form_child) ) {
                            continue ;
                        }

                        // バリデーションでエラーがある場合は登録させない
                        if ( count($form_child->getErrors(true)) > 0 ) {
                            $child_error = false;
                        } else {
                            $child_entity_array[] = $update_keyval;  // 設問登録用を順番に追加
                        }

                        // 際表示用にテンプレート読み込み
                        $tplObj = $this->render('@SeEnquete4/admin/Block/add_form_content.twig', [
                            'form' => $form_child->createView(),
                            'Collection' => $enqueteConfig,
                            'formNamePrefix' => AddFormContentType::_FORM_NAME_PREFIX_,
                            'formUniqId' => $real_uniqid,
                            'pulldown_row' => ( isset($child_rows['pulldown_row']) ) ? $child_rows['pulldown_row'] : $editChildRow ,
                            'radio_row' => ( isset($child_rows['radio_row']) ) ? $child_rows['radio_row'] : $editChildRow ,
                            'check_row' => ( isset($child_rows['check_row']) ) ? $child_rows['check_row'] : $editChildRow ,
                        ]);

                        if ( $tplObj->getStatuscode() && ( $tplObj->getStatuscode() == 200 ) ) {
                            $child_formHtml  .= $tplObj->getContent();
                            $child_optionIDs .= ( !empty($child_optionIDs) ) ? ',' .$real_uniqid : $real_uniqid ;
                        }
                    }
                }
            }

            // エラーが無ければ登録へ
            if ( ( count($form->getErrors(true)) < 1 ) && $child_error ) {

                $now = new \DateTime(date("Y-m-d H:i:s"), new \DateTimeZone('Asia/Tokyo'));

                $entityManager = $this->getDoctrine()->getManager();

                try {
                    $entityManager->beginTransaction();
                    
                    $enqueteEntity
                        ->setTitle( $formData['title'] )
                        ->setMemberFlg( $formData['member_flg'] )
                        ->setMailFlg( $formData['mail_flg'] )
                        ->setPersonalFlg( $formData['personal_flg'] )
                        ->setSubmitTitle( $formData['submit_title'] )
                        ->setStartDate( new \DateTime( $formData['start_date'] .' 00:00:00', new \DateTimeZone('Asia/Tokyo') ) )
                        ->setEndDate( new \DateTime( $formData['end_date'] .' 00:00:00', new \DateTimeZone('Asia/Tokyo') ) )
                        ->setStatus( $formData['status'] )
                        ->setUpdateDate( $now );

                    // for option
                    if ( isset($formData['sub_title']) ) $enqueteEntity->setSubTitle( $formData['sub_title'] ) ;
                    if ( isset($formData['address_list']) ) $enqueteEntity->setAddressList( $formData['address_list'] ) ;
                    if ( isset($formData['personal_title']) ) $enqueteEntity->setPersonalTitle( $formData['personal_title'] ) ;
                    if ( isset($formData['personal_text']) ) {
                        $enqueteEntity->setPersonalText( $formData['personal_text'] );
                    } else {
                        $enqueteEntity->setPersonalText('');
                    }
                    if ( isset($fileName) ) $enqueteEntity->setThumbnail( $fileName );

                    if ( empty($id) ) {
                        $enqueteEntity
                            ->setCreateDate( $now )
                            ->setDeleted( 0 );
                    }

                    $entityManager->persist($enqueteEntity);
                    $entityManager->flush();


                    // 既存設問情報を一旦削除
                    $enqueteMetaEntities = $this->enqueteMetaRepository->findBy([
                        'Enquete' => $enqueteEntity,
                        'deleted' => 0
                    ]);
                    if ( $enqueteMetaEntities ) {
                        foreach ( $enqueteMetaEntities as $enqueteMetaEntity ) {
                            // 子供が存在すれば削除
                            $enqueteItemEntities = $this->enqueteItemRepository->findBy([
                                'EnqueteMeta' => $enqueteMetaEntity,
                                'deleted' => 0
                            ]);
                            if ( $enqueteItemEntities ) {
                                foreach( $enqueteItemEntities as $enqueteItemEntity ) {

                                    $enqueteItemEntity
                                        ->setUpdateDate( $now )
                                        ->setDeleted( 1 );

                                    $entityManager->persist($enqueteItemEntity);
                                    $entityManager->flush();
                                }
                            }

                            $enqueteMetaEntity
                                ->setUpdateDate( $now )
                                ->setDeleted( 1 );

                            $entityManager->persist($enqueteMetaEntity);
                            $entityManager->flush();
                        }
                    }

                    // 設問が存在すればここから登録
                    if ( isset($child_entity_array) && !empty($child_entity_array) ) {

                        $metaSortNo = 1;

                        foreach ( $child_entity_array as $metaArray ) {

                            $enqueteMetaEntity = new EnqueteMeta();

                            if ( isset($metaArray['config_id']) && !empty($metaArray['config_id']) ) {
                                $enqueteConfig = $this->enqueteConfigRepository->find($metaArray['config_id']);
                                $enqueteMetaEntity->setEnqueteConfig( $enqueteConfig );
                            }

                            if ( isset($metaArray['title']) && !empty($metaArray['title']) ) {
                                $enqueteMetaEntity->setTitle( $metaArray['title'] );
                            }
                            if ( isset($metaArray['text']) && !empty($metaArray['text']) ) {
                                $enqueteMetaEntity->setText( $metaArray['text'] );
                            }   
                            if ( isset($metaArray['placeholder']) && !empty($metaArray['placeholder']) ) {
                                $enqueteMetaEntity->setPlaceholder( $metaArray['placeholder'] );
                            }   
                            if ( isset($metaArray['ness_flg']) && !empty($metaArray['ness_flg']) ) {
                                $enqueteMetaEntity->setNessFlg( $metaArray['ness_flg'] );
                            }   

                            $enqueteMetaEntity
                                ->setEnquete( $enqueteEntity )
                                ->setSortNo( $metaSortNo )
                                ->setCreateDate( $now )
                                ->setUpdateDate( $now )
                                ->setDeleted( 0 );

                            $entityManager->persist($enqueteMetaEntity);
                            $entityManager->flush();

                            if ( isset($metaArray['children']) && !empty($metaArray['children']) ) {

                                $itemSortNo = 1;

                                foreach ( $metaArray['children'] as $itemArray ) {

                                    $enqueteItemEntity = new EnqueteItem();

                                    if ( isset($itemArray['values']) && !empty($itemArray['values']) ) {
                                        $enqueteItemEntity->setValues( $itemArray['values'] );
                                    }

                                    $enqueteItemEntity
                                        ->setEnqueteMeta( $enqueteMetaEntity )
                                        ->setSortNo( $itemSortNo )
                                        ->setCreateDate( $now )
                                        ->setUpdateDate( $now )
                                        ->setDeleted( 0 );

                                    $entityManager->persist($enqueteItemEntity);
                                    $entityManager->flush();

                                    $itemSortNo++;
                                }
                            }

                            $metaSortNo++;
                        }
                    }

                    $entityManager->commit();

                    $this->addSuccess('admin.common.save_complete', 'admin');

                    return $this->redirectToRoute('se_enquete_admin_manage_edit', [
                        'id' => $enqueteEntity->getId(),
                    ]);

                } catch ( Exception $e ) {

                    $entityManager->rollback();

                    $this->addError('admin.common.system_error');

                }

            } else {

                $errorFlg = true;

            }

        } else {
            $paramData = [];

        }


        // 子設問がある場合はここで取得（編集後はエラーの場合以外来ない）
        if ( $id && $newEditFlg ) {
            $enqueteMetaEntities = $this->enqueteMetaRepository->findBy([
                'Enquete' => $enqueteEntity,
                'deleted' => 0
            ], [ 'sort_no' => 'ASC' ]);

            if ( $enqueteMetaEntities ) {
                foreach ( $enqueteMetaEntities as $enqueteMetaEntity ) {

                    $uniqid = $this->uniqidReal();
                    $child_thumbnails = [];

                    $childDBDatas = [
                        'config_id' => $enqueteMetaEntity->getEnqueteConfig()->getId(),
                        'title_' .$enqueteMetaEntity->getEnqueteConfig()->getKeyword() => $enqueteMetaEntity->getTitle(),
                        'text_' .$enqueteMetaEntity->getEnqueteConfig()->getKeyword()  => $enqueteMetaEntity->getText(),
                        'placeholder_' .$enqueteMetaEntity->getEnqueteConfig()->getKeyword() => $enqueteMetaEntity->getPlaceholder(),
                        'ness_flg_' .$enqueteMetaEntity->getEnqueteConfig()->getKeyword() => $enqueteMetaEntity->getNessFlg(),
                    ];

                    $enqueteItemEntities = $this->enqueteItemRepository->findBy([
                        'EnqueteMeta' => $enqueteMetaEntity,
                        'deleted' => 0
                    ], [ 'sort_no' => 'ASC' ]);

                    if ( $enqueteItemEntities ) {
                        foreach( $enqueteItemEntities as $enqueteItemEntity ) {

                            if ( !isset($childDBDatas['children_' .$enqueteMetaEntity->getEnqueteConfig()->getKeyword()]) ) $childDBDatas['children_' .$enqueteMetaEntity->getEnqueteConfig()->getKeyword()] = [] ;
                            $thumbnail_flg = ( file_exists( $this->get('kernel')->getProjectDir() .'/' .$this->eccubeConfig['Se_Enquete_img_upload_dir'] .$enqueteItemEntity->getValues() ) ) ? 1 : 0 ;
                            $childDBDatas['children_' .$enqueteMetaEntity->getEnqueteConfig()->getKeyword()][] = [
                                'type' => $thumbnail_flg,
                                'values' => $enqueteItemEntity->getValues(),
                            ];
                            $child_thumbnails[] = ( $thumbnail_flg == 1 ) ? $enqueteItemEntity->getValues() : '' ;
                        }
                    }

                    $child_options = [ 
                        'identification_id' => $uniqid,
                        'list_required' => CommonUtil::replaceTrans( $this->eccubeConfig['Se_Enquete_master_list']['list_required'] ),
                        'list_radio' => CommonUtil::replaceTrans( $this->eccubeConfig['Se_Enquete_master_list']['list_radio'] ),
                        'child_row' => self::_DEFAULT_CHILD_ROW_,
                        'child_DBDatas' => $childDBDatas,
                    ];

                    $form_child = $this->createForm(AddFormContentType::class, [], $child_options);

                    // 際表示用にテンプレート読み込み
                    $tplObj = $this->render('@SeEnquete4/admin/Block/add_form_content.twig', [
                        'form' => $form_child->createView(),
                        'Collection' => $enqueteConfig,
                        'formNamePrefix' => AddFormContentType::_FORM_NAME_PREFIX_,
                        'formUniqId' => $uniqid,
                        'pulldown_row' => ( isset($childDBDatas['children_pulldown']) && ( count($childDBDatas['children_pulldown']) != self::_DEFAULT_CHILD_ROW_ ) ) ? count($childDBDatas['children_pulldown']) : self::_DEFAULT_CHILD_ROW_ ,
                        'radio_row' => ( isset($childDBDatas['children_radio']) && ( count($childDBDatas['children_radio']) != self::_DEFAULT_CHILD_ROW_ ) ) ? count($childDBDatas['children_radio']) : self::_DEFAULT_CHILD_ROW_ ,
                        'check_row' => ( isset($childDBDatas['children_check']) && ( count($childDBDatas['children_check']) != self::_DEFAULT_CHILD_ROW_ ) ) ? count($childDBDatas['children_check']) : self::_DEFAULT_CHILD_ROW_ ,
                        'thumbnails' => $child_thumbnails,
                    ]);

                    if ( $tplObj->getStatuscode() && ( $tplObj->getStatuscode() == 200 ) ) {
                        $child_formHtml  .= $tplObj->getContent();
                        $child_optionIDs .= ( !empty($child_optionIDs) ) ? ',' .$uniqid : $uniqid ;
                    }

                }
            }
        }


        return [
            'form' => $form->createView(),
            'child_formHtml' => $child_formHtml,
            'child_optionIDs' => $child_optionIDs,
            'thumbnail' => ( $enqueteEntity && $enqueteEntity->getThumbnail() ) ? $enqueteEntity->getThumbnail() : '',
            'editId' => ( $enqueteEntity && $enqueteEntity->getId() ) ? $enqueteEntity->getId() : '',
            'list_memberFlg' => $options['list_memberFlg'],
            'list_mailFlg' => $options['list_mailFlg'],
            'list_personalFlg' => $options['list_personalFlg'],
            'list_status' => $options['list_status'],
            'endFlg' => ( $enqueteEntity->getEndDate() && ( $enqueteEntity->getEndDate() < date('Y-m-d') ) ) ? true : false ,    // 終了したアンケートは編集不可とする
            'errorFlg' => ( isset($errorFlg) && ( $errorFlg == true ) ) ? true : false ,
        ];
    }

    /**
     * アンケート結果CSV出力
     *
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     * @Route("/%eccube_admin_route%/enquete/manage/export/{id}", requirements={"id" = "\d+"}, name="se_enquete_admin_manage_export")
     */
    public function export(Request $request, $id)
    {

        if ( !empty($id) && is_numeric($id) ) {
            $enqueteEntity = $this->enqueteRepository->find($id);
        }

        if ( !isset($enqueteEntity) || empty($enqueteEntity) ) {
            throw new NotFoundHttpException();
        }

        $dateObj = new \DateTime(date("Y-m-d H:i:s"), new \DateTimeZone('Asia/Tokyo'));
        $fileName = 'enquete_'.$dateObj->format('YmdHis').'.csv';

        // タイムアウトを無効にする.
        set_time_limit(0);
        ini_set('memory_limit', '-1');

        // sql loggerを無効にする.
        $em = $this->entityManager;
        $em->getConfiguration()->setSQLLogger(null);

        // アンケート結果がなくてもアンケート自体が存在すれば空ファイルを返却させる
        $response = new StreamedResponse();

        $response->setCallback(function () use ($request, $enqueteEntity) {
            /* ヘッダ行(アンケートタイトル)は回答期間中にフォーム変更がある可能性があるので出力しない */

            // 結果データ種等のクエリビルダを取得
            //$qb = $this->enqueteUserRepository
            $qb = $this->getDoctrine()->getManager()->getRepository(EnqueteUser::class)
                ->createQueryBuilder('u')
                  ->where( 'u.Enquete = :enquete' )
                    ->setParameter( 'enquete', $enqueteEntity )
                  ->andWhere( 'u.deleted = 0' )
                  ->orderBy( 'u.create_date', 'ASC')
                ->getQuery();

            // データ行の出力
            $file = new \SplFileObject('php://output', 'w');

            $config = $this->eccubeConfig;

            $lineNum = 1;
            foreach ($qb->iterate() as $iterableResult) {

                if ( $lineNum == 1 ) {
                    $row = [
                        trans('se_enquete.admin.manage.export_header_num'),
                        trans('se_enquete.admin.manage.export_header_customer'),
                        trans('se_enquete.admin.manage.export_header_answer'),
                        trans('se_enquete.admin.manage.export_header_useragent'),
                        trans('se_enquete.admin.manage.export_header_ip'),
                        trans('se_enquete.admin.manage.export_header_create_date'),
                    ];

                    $file->fputcsv( array_map( function($value) use ($config) {
                        return mb_convert_encoding(
                            (string) $value, $config['eccube_csv_export_encoding'], 'UTF-8'
                        );
                    }, $row ) );
                }

                /** @var EnqueteUser $enqueteUser */
                $enqueteUser = $iterableResult[0];

                // 会員IDがあれば名前を取得する
                $Customer = [];
                if ( $enqueteUser->getCustomerId() ) {
                    $Customer = $this->customerRepository->find( $enqueteUser->getCustomerId() );
                }

                // 回答内容は配列なのでここで調整
                $rowAnswer = '';
                $answer = $enqueteUser->getAnswerJson();
                foreach ( $answer as $key => $value ) {
                    if ( !empty($rowAnswer) ) $rowAnswer .= PHP_EOL;
                    $rowAnswer .= $key .' : ';
                    if ( is_array($value) ) {
                        foreach ( $value as $vkey => $vvalue ) {
                            if ( $vkey ) $rowAnswer .= ' / ';
                            $rowAnswer .= $vvalue ;
                        }
                    } else {
                        $rowAnswer .= $value ;
                    }
                }

                $row = [
                    $lineNum,
                    ( $Customer ) ? $Customer->__toString() : null ,
                    $rowAnswer ,
                    ( $enqueteUser->getUserAgent() ) ? $enqueteUser->getUserAgent() : null ,
                    ( $enqueteUser->getIP() ) ? $enqueteUser->getIP() : null ,
                    $enqueteUser->getCreateDate()->format('Y-m-d H:i:s') ,
                ];

                $file->fputcsv( array_map( function($value) use ($config) { 
                    return mb_convert_encoding(
                        (string) $value, $config['eccube_csv_export_encoding'], 'UTF-8'
                    );
                }, $row ) );
                $this->getDoctrine()->getManager()->detach($enqueteUser);
                flush();

                $lineNum++;

            };
        });

        $response->headers->set('Content-Type', 'application/octet-stream');
        $response->headers->set('Content-Disposition', 'attachment; filename='.$fileName);
        $response->send();

        return $response;
    }

    /**
     * アンケート結果画面
     *  url に id を含めたいが paginator で利用できないので request パラメータで処理させる
     *
     * @Route("/%eccube_admin_route%/enquete/manage/view/{page_no}", requirements={"page_no" = "\d+"}, name="se_enquete_admin_manage_view")
     * @Template("@SeEnquete4/admin/Manage/view.twig")
     */
//    public function view( Request $request, $page_no = 1, Paginator $paginator )
    public function view( Request $request, $page_no = 1, PaginatorInterface $paginator )
    {

        $id = $request->get('id');

        if ( !is_numeric($page_no) || ($page_no < 1) ) $page_no = 1 ;

        $page_count = $this->eccubeConfig['Se_Enquete_admin_view_limit'];

        // タイムアウトを無効にする.
        set_time_limit(0);
        ini_set('memory_limit', '-1');

        $enqueteUserEntities = [];
        if ( !empty($id) && is_numeric($id) ) {

            $enqueteEntity = $this->enqueteRepository->find($id);

            if ( $enqueteEntity ) {
                $enqueteUserEntities = $this->enqueteUserRepository->findBy([
                    'Enquete' => $enqueteEntity,
                    'deleted' => 0,
                ], [ 'create_date' => 'ASC' ]);
            }
        }

        // アンケート自体がなければNotFound
        if ( !isset($enqueteEntity) ) {
            throw new NotFoundHttpException();
        }

        // カスタマIDが存在するなら会員情報を取得する
        foreach ( $enqueteUserEntities as $enqueteUserEntity ) {
            if ( $enqueteUserEntity->getCustomerId() != 0 ) {
                $Customer = $this->customerRepository->find( $enqueteUserEntity->getCustomerId() );
                $enqueteUserEntity->setCustomer( $Customer );
            }
        }

        $pagination = $paginator->paginate(
            $enqueteUserEntities,
            $page_no,
            $page_count
        );

        return [
            'pagination' => $pagination,
        ];
    }

    /**
     * アンケート削除
     *
     * @Route("/%eccube_admin_route%/enquete/manage/{id}/delete", requirements={"id":"\d+"}, name="se_enquete_admin_manage_delete")
     */
    public function delete( Request $request, $id )
    {

        /* 削除してリダイレクト */
        if ( !empty($id) && is_numeric($id) ) {
            $enqueteEntity = $this->enqueteRepository->find($id);
        }

        if ( !isset($enqueteEntity) || empty($enqueteEntity) ) {
            //throw new NotFoundHttpException();
        }

        if ( !isset($enqueteEntity) || empty($enqueteEntity) || $enqueteEntity->getDeleted() != 0 ) {

            // 既に削除済み or 情報なし
            $this->addWarning('se_enquete.common.message.deleted', 'admin');

        } else {

            $now = new \DateTime(date("Y-m-d H:i:s"), new \DateTimeZone('Asia/Tokyo'));

            $entityManager = $this->getDoctrine()->getManager();

            try {
                $entityManager->beginTransaction();

                $enqueteEntity
                    ->setUpdateDate( $now )
                    ->setDeleted( 1 );

                $entityManager->persist($enqueteEntity);
                $entityManager->flush();

                $entityManager->commit();

                // 正常削除完了
                $this->addSuccess('admin.common.delete_complete', 'admin');

            } catch ( Exception $e ) {

                $entityManager->rollback();

                // 削除失敗
                $type = trans('se_enquete.admin.manage.button_delete');
                $message = trans('se_enquete.common.message.failed', [ '%type%' => $type ]);
                $this->addError($message, 'admin');

            }

        }

        return $this->redirectToRoute('se_enquete_admin_manage_index');

    }

    /**
     * アンケート設問取得
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @Route("/%eccube_admin_route%/enquete/manage/child/{uniqid}", requirements={"uniqid":"[0-9a-z]+"}, name="se_enquete_admin_manage_get_child_content")
     */
    public function child_content_handler(Request $request, $uniqid): Response
    {

        $output = [ 'status' => false ];

        $enqueteConfig = $this->enqueteConfigRepository->findBy([
                'deleted' => 0
            ], [ 'sort_no' => 'ASC' ]);

        $uniq_id = $uniqid;

        $options = [ 
            'identification_id' => $uniq_id, 
            'list_required' => CommonUtil::replaceTrans( $this->eccubeConfig['Se_Enquete_master_list']['list_required'] ),
            'list_radio' => CommonUtil::replaceTrans( $this->eccubeConfig['Se_Enquete_master_list']['list_radio'] ),
            'child_row' => self::_DEFAULT_CHILD_ROW_,
        ];

        $form = $this->createForm(AddFormContentType::class, [], $options);

        /* テンプレート読み込み */
        $tplObj = $this->render('@SeEnquete4/admin/Block/add_form_content.twig', [
            'form' => $form->createView(),
            'Collection' => $enqueteConfig,
            'formNamePrefix' => AddFormContentType::_FORM_NAME_PREFIX_,
            'formUniqId' => $uniq_id,
            'pulldown_row' => self::_DEFAULT_CHILD_ROW_ ,
            'radio_row' => self::_DEFAULT_CHILD_ROW_ ,
            'check_row' => self::_DEFAULT_CHILD_ROW_ ,
        ]);

        if ( $tplObj->getStatuscode() && ( $tplObj->getStatuscode() == 200 ) ) {
            $output['status'] = true;
            $output['content'] = $tplObj->getContent();
        }

        return $this->json($output);

    }


    /**
     * Validation チェック Enquete
     *  title / member_flg / mail_flg / address_list / personal_flg / personal_title / personal_text / submit_title / start_date / end_date / status
     **/
    private function checkValidateEnquete( $form, $formParam=[] ) // {{{
    {

        # title
        $check = 'title';
        if ( !isset($formParam[$check]) || !ValidateUtil::isNonNull($formParam[$check]) ) {
            $message = trans('se_enquete.common.message.empty', [ '%label%' => trans('se_enquete.admin.common.enquete_'.$check) ]);
            $form->get($check)->addError( new FormError( $message, $message ) );
        }

        # member_flg
        $check = 'mail_flg';
        if ( !isset($formParam[$check]) || !ValidateUtil::isValidNumeric($formParam[$check]) || !ValidateUtil::isValidIntSpecific($formParam[$check]) ) {
            $message = trans('se_enquete.common.message.empty', [ '%label%' => trans('se_enquete.admin.common.enquete_'.$check) ]);
            $form->get($check)->addError( new FormError( $message, $message ) );
        }

        # mail_flg / address_list
        $check = 'mail_flg';
        $check2 = 'address_list';
        if ( !isset($formParam[$check]) || !ValidateUtil::isValidNumeric($formParam[$check]) || !ValidateUtil::isValidIntSpecific($formParam[$check]) ) {
            $message = trans('se_enquete.common.message.empty', [ '%label%' => trans('se_enquete.admin.common.enquete_'.$check) ]);
            $form->get($check)->addError( new FormError( $message, $message ) );
        } else {
            if ( isset($formParam[$check2]) && ValidateUtil::isNonNull($formParam[$check2]) ) {
                $explode = explode(',', $formParam[$check2]);
                foreach ( $explode as $key => $value ) {
                    if ( !ValidateUtil::isValidEmail($value) ) {
                        $message = trans('se_enquete.common.message.invalid', [ '%label%' => trans('se_enquete.admin.common.enquete_'.$check2) ]);
                        $form->get($check2)->addError( new FormError( $message, $message ) );
                        break;
                    }
                }
            }
        }

        # personal_flg / personal_title / personal_text
        $check  = 'personal_flg';
        $check2 = 'personal_title';
        $check3 = 'personal_text';
        if ( !isset($formParam[$check]) || !ValidateUtil::isValidNumeric($formParam[$check]) || !ValidateUtil::isValidIntSpecific($formParam[$check]) ) {
            $message = trans('se_enquete.common.message.empty', [ '%label%' => trans('se_enquete.admin.common.enquete_'.$check) ]);
            $form->get($check)->addError( new FormError( $message, $message ) );
        } else 
        if ( $formParam[$check] == 1 ) {  /* 個人情報あり */
            if ( !isset($formParam[$check2]) || !ValidateUtil::isNonNull($formParam[$check2]) ) {
                $message = trans('se_enquete.common.message.empty', [ '%label%' => trans('se_enquete.admin.common.enquete_'.$check2) ]);
                $form->get($check2)->addError( new FormError( $message, $message ) );
            }
            //if ( !isset($formParam[$check3]) || !ValidateUtil::isNonNull($formParam[$check3]) ) {
            //    $message = trans('se_enquete.common.message.empty', [ '%label%' => trans('se_enquete.admin.common.enquete_'.$check3) ]);
            //    $form->get($check3)->addError( new FormError( $message, $message ) );
            //}
        }

        # submit_title
        $check = 'submit_title';
        if ( !isset($formParam[$check]) || !ValidateUtil::isNonNull($formParam[$check]) ) {
            $message = trans('se_enquete.common.message.empty', [ '%label%' => trans('se_enquete.admin.common.enquete_'.$check) ]);
            $form->get($check)->addError( new FormError( $message, $message ) );
        }

        # start_date
        $check = 'start_date';
        $start_date_check = true;
        if ( !isset($formParam[$check]) || !ValidateUtil::isNonNull($formParam[$check]) ) {
            $start_date_check = false;
            $message = trans('se_enquete.common.message.empty', [ '%label%' => trans('se_enquete.admin.common.enquete_'.$check) ]);
            $form->get($check)->addError( new FormError( $message, $message ) );
        } else 
        if ( !ValidateUtil::isValidDateTime($formParam[$check]) ) {
            $message = trans('se_enquete.common.message.invalid', [ '%label%' => trans('se_enquete.admin.common.enquete_'.$check) ]);
            $form->get($check)->addError( new FormError( $message, $message ) );
        }

        # end_date
        $check = 'end_date';
        if ( !isset($formParam[$check]) || !ValidateUtil::isNonNull($formParam[$check]) ) {
            $start_date_check = false;
            $message = trans('se_enquete.common.message.empty', [ '%label%' => trans('se_enquete.admin.common.enquete_'.$check) ]);
            $form->get($check)->addError( new FormError( $message, $message ) );
        } else 
        if ( !ValidateUtil::isValidDateTime($formParam[$check]) ) {
            $message = trans('se_enquete.common.message.invalid', [ '%label%' => trans('se_enquete.admin.common.enquete_'.$check) ]);
            $form->get($check)->addError( new FormError( $message, $message ) );
        } else 
        if ( $start_date_check ) {
            if ( (int)str_replace('-', '', $formParam['start_date']) > (int)str_replace('-', '', $formParam[$check]) ) {
                $message = trans('se_enquete.common.message.term_date');
                $form->get($check)->addError( new FormError( $message, $message ) );
            }
        }

        # status 
        $check = 'status';
        if ( !isset($formParam[$check]) || !ValidateUtil::isValidNumeric($formParam[$check]) || !ValidateUtil::isValidIntSpecific($formParam[$check]) ) {
            $message = trans('se_enquete.common.message.empty', [ '%label%' => trans('se_enquete.admin.common.enquete_'.$check) ]);
            $form->get($check)->addError( new FormError( $message, $message ) );
        }

        return $form;

    } // }}}


    /**
     * Validation チェック EnqueteChild
     *  child_form_type [ question / placeholder / required / answer_[] / view_type_[] / answer_text_[] / answer_thumbnail_[] / sentence ]
     **/
    private function checkValidateEnqueteChild( $form, $formParam=[], $uniqid='', $editChildRow=0 ) // {{{
    {

        if ( empty($uniqid) ) return null ;

        if ( empty($editChildRow) ) $editChildRow = self::_DEFAULT_CHILD_ROW_;

        $update_keyval = $child_rows = [];    // DB登録用の配列 と view側へ渡す各設問数配列

        // フォーム選択していない
        if ( !isset($formParam[AddFormContentType::_FORM_NAME_PREFIX_ .$uniqid .'_child_form_type']) ) {
            return [ null, $update_keyval, $child_rows ];
        }

        // フォーム選択が数値ではない（不正アクセス）
        $use_form_id = $formParam[AddFormContentType::_FORM_NAME_PREFIX_ .$uniqid .'_child_form_type'];
        if ( !ValidateUtil::isNonNull($use_form_id) || !ValidateUtil::isValidNumeric($use_form_id) ) {
            return [ null, $update_keyval, $child_rows ];
        }

        // DBからフォーム種を取得
        $enqueteConfigEntity = $this->enqueteConfigRepository->find($use_form_id);

        // フォームがDBに存在しない種類（不正アクセス）
        if ( !$enqueteConfigEntity ) {
            return [ null, $update_keyval, $child_rows ];
        }

        $default_add_keyval = [
            'config_id' => $enqueteConfigEntity->getId(),
        ];

        $keyPrefix = AddFormContentType::_FORM_NAME_PREFIX_ .$enqueteConfigEntity->getKeyword() .'_' .$uniqid .'_';

        switch ( $enqueteConfigEntity->getKeyword() ) {

            case 'text':
            case 'textarea':

                $add_keyval = $default_add_keyval;

                # question 
                $check = $keyPrefix .'question';
                if ( !isset($formParam[$check]) || !ValidateUtil::isNonNull($formParam[$check]) ) {
                    $message = trans('se_enquete.common.message.empty', [ '%label%' => trans('se_enquete.admin.common.addform_enquete_question') ]);
                    $form->get($check)->addError( new FormError( $message, $message ) );
                } else {
                    $add_keyval['title'] = $formParam[$check];
                }

                # placeholder
                $check = $keyPrefix .'placeholder';
                if ( !isset($formParam[$check]) || !ValidateUtil::isNonNull($formParam[$check]) ) {
                    //$message = trans('se_enquete.common.message.empty', [ '%label%' => trans('se_enquete.admin.common.addform_enquete_placeholder') ]);
                    //$form->get($check)->addError( new FormError( $message, $message ) );
                } else {
                    $add_keyval['placeholder'] = $formParam[$check];
                }

                # required
                $check = $keyPrefix .'required';
                if ( !isset($formParam[$check]) || !ValidateUtil::isValidNumeric($formParam[$check]) || !ValidateUtil::isValidIntSpecific($formParam[$check]) ) {
                    $message = trans('se_enquete.common.message.empty', [ '%label%' => trans('se_enquete.admin.common.addform_enquete_required_flg') ]);
                    $form->get($check)->addError( new FormError( $message, $message ) );
                } else {
                    $add_keyval['ness_flg'] = $formParam[$check];
                }

                // エラーがなければ登録用配列へ格納
                if ( count($form->getErrors(true)) < 1 ) {
                    $update_keyval = $add_keyval;
                }

                break;

            case 'pulldown':
            case 'check':

                $add_keyval = $default_add_keyval;

                # question
                $check = $keyPrefix .'question';
                if ( !isset($formParam[$check]) || !ValidateUtil::isNonNull($formParam[$check]) ) {
                    $message = trans('se_enquete.common.message.empty', [ '%label%' => trans('se_enquete.admin.common.addform_enquete_question') ]);
                    $form->get($check)->addError( new FormError( $message, $message ) );
                } else {
                    $add_keyval['title'] = $formParam[$check];
                }

                # required
                $check = $keyPrefix .'required';
                if ( !isset($formParam[$check]) || !ValidateUtil::isValidNumeric($formParam[$check]) || !ValidateUtil::isValidIntSpecific($formParam[$check]) ) {
                    $message = trans('se_enquete.common.message.empty', [ '%label%' => trans('se_enquete.admin.common.addform_enquete_required_flg') ]);
                    $form->get($check)->addError( new FormError( $message, $message ) );
                } else {
                    $add_keyval['ness_flg'] = $formParam[$check];
                }

                # answer_
                $checkPrefix = $keyPrefix .'answer';
                $check_flg = false;
                foreach ( $formParam as $key => $value ) {
                    if ( substr($key, 0, strlen($checkPrefix.'_')) == $checkPrefix.'_' ) {
                        // 対象の数値を取得
                        $targetNum = substr( $key, strrpos($key, '_') + 1 );

                        if ( $targetNum > $editChildRow ) {
                            $child_rows[ $enqueteConfigEntity->getKeyword() .'_row' ] = $targetNum ;
                        }

                        if ( isset($formParam[$checkPrefix .'_' .$targetNum]) && ValidateUtil::isNonNull($formParam[$checkPrefix .'_' .$targetNum]) ) {
                            $check_flg = true;

                            if ( !isset($add_keyval['children']) ) $add_keyval['children'] = [] ;
                            $add_keyval['children'][] = [ 'values' => $formParam[$checkPrefix .'_' .$targetNum] ];
                        } else {
                            $check_flg = false;
                        }
                    }
                }
                if ( !$check_flg ) {
                    $message  = trans('se_enquete.common.message.empty', [ '%label%' => trans('se_enquete.admin.common.addform_enquete_answer') ]);
                    $message .= ' / ' .trans('se_enquete.common.message.row_remove');
                    $form->get($checkPrefix)->addError( new FormError( $message, $message ) );
                }

                // エラーがなければ登録用配列へ格納
                if ( count($form->getErrors(true)) < 1 ) {
                    $update_keyval = $add_keyval;
                }

                break;

            case 'radio':

                $add_keyval = $default_add_keyval;

                # question
                $check = $keyPrefix .'question';
                if ( !isset($formParam[$check]) || !ValidateUtil::isNonNull($formParam[$check]) ) {
                    $message = trans('se_enquete.common.message.empty', [ '%label%' => trans('se_enquete.admin.common.addform_enquete_question') ]);
                    $form->get($check)->addError( new FormError( $message, $message ) );
                } else {
                    $add_keyval['title'] = $formParam[$check];
                }

                # required
                $check = $keyPrefix .'required';
                if ( !isset($formParam[$check]) || !ValidateUtil::isValidNumeric($formParam[$check]) || !ValidateUtil::isValidIntSpecific($formParam[$check]) ) {
                    $message = trans('se_enquete.common.message.empty', [ '%label%' => trans('se_enquete.admin.common.addform_enquete_required_flg') ]);
                    $form->get($check)->addError( new FormError( $message, $message ) );
                } else {
                    $add_keyval['ness_flg'] = $formParam[$check];
                }

                # view_type_ [ answer_text_ / answer_thumbnail_ ]
                $checkPrefix = $keyPrefix .'view_type';
                $check_flg = false;
                foreach ( $formParam as $key => $value ) {
                    if ( substr($key, 0, strlen($checkPrefix.'_')) == $checkPrefix.'_' ) {
                        if ( isset($formParam[$key]) && ValidateUtil::isValidNumeric($formParam[$key]) && ValidateUtil::isValidIntSpecific($formParam[$key]) ) {
                            // 対象の数値を取得
                            $targetNum = substr( $key, strrpos($key, '_') + 1 );

                            if ( $targetNum > $editChildRow ) {
                                $child_rows[ $enqueteConfigEntity->getKeyword() .'_row' ] = $targetNum ;
                            }

                            if ( $formParam[$checkPrefix .'_' .$targetNum] == 0 ) {
                                $check = $keyPrefix .'answer_text_' .$targetNum;
                                if ( isset($formParam[$check]) && ValidateUtil::isNonNull($formParam[$check]) ) {
                                    $check_flg = true;

                                    if ( !isset($add_keyval['children']) ) $add_keyval['children'] = [] ;
                                    $add_keyval['children'][] = [ 'values' => $formParam[$check] ];
                                } else {
                                    $check_flg = false;
                                }
                            } else
                            if ( $formParam[$checkPrefix .'_' .$targetNum] == 1 ) {
                                $check = $keyPrefix .'answer_thumbnail_' .$targetNum;
                                $check2 = $keyPrefix .'answer_hidden_' .$targetNum;

                                if ( $form->has($check) && $form->get($check)->getData() ) {

                                    $filename = '';
                                    $uploadFile = $form->get($check)->getData();

                                    if ( $uploadFile ) {
                                        $originalFilename = pathinfo($uploadFile->getClientOriginalName(), PATHINFO_FILENAME);

                                        // this is needed to safely include the file name as part of the URL
                                        $safeFilename = transliterator_transliterate('Any-Latin; Latin-ASCII; [^A-Za-z0-9_] remove; Lower()', $originalFilename);
                                        $newFilename = $safeFilename.'-'.uniqid().'.'.$uploadFile->guessExtension();

                                        // Move the file to the directory where brochures are stored
                                        try {
                                            $uploadFile->move(
                                                //$this->getParameter('brochures_directory'),
                                                $this->eccubeConfig['Se_Enquete_img_upload_dir'],
                                                $newFilename
                                            );
                                            // set newFilename
                                            $fileName = $newFilename;
                                        } catch (FileException $e) {
                                            // ... handle exception if something happens during file upload
                                        }
                                    }

                                    if ( $fileName ) {
                                        $check_flg = true;

                                        if ( !isset($add_keyval['children']) ) $add_keyval['children'] = [] ;
                                        $add_keyval['children'][] = [ 'values' => $fileName ];
                                    } else {
                                        $check_flg = false;
                                    }
                                } else
                                if ( $form->has($check2) && $form->get($check2)->getData() ) {

                                    $check_flg = true;
                                    if ( !isset($add_keyval['children']) ) $add_keyval['children'] = [] ;
                                    $add_keyval['children'][] = [ 'values' => $form->get($check2)->getData() ];

                                } else {
                                    $check_flg = false;
                                }
                            }
                        }
                    }
                }

                if ( !$check_flg ) {
                    $message  = trans('se_enquete.common.message.empty', [ '%label%' => trans('se_enquete.admin.common.addform_enquete_answer') ]);
                    $message .= ' / ' .trans('se_enquete.common.message.row_remove');
                    $form->get($checkPrefix)->addError( new FormError( $message, $message ) );
                }

                // エラーがなければ登録用配列へ格納
                if ( count($form->getErrors(true)) < 1 ) {
                    $update_keyval = $add_keyval;
                }

                break;

            case 'etc':

                $add_keyval = $default_add_keyval;

                # sentence
                $check = $keyPrefix .'sentence';
                if ( !isset($formParam[$check]) || !ValidateUtil::isNonNull($formParam[$check]) ) {
                    $message = trans('se_enquete.common.message.empty', [ '%label%' => trans('se_enquete.admin.common.addform_enquete_sentence') ]);
                    $form->get($check)->addError( new FormError( $message, $message ) );
                } else {
                    $add_keyval['text'] = $formParam[$check];
                }

                // エラーがなければ登録用配列へ格納
                if ( count($form->getErrors(true)) < 1 ) {
                    $update_keyval = $add_keyval;
                }

                break;

            default:
                break;

        }

        return [ $form, $update_keyval, $child_rows ];

    }  // }}}

    /*
     * Formに存在しない子設問がある場合は追加する
     *  answer_ / view_type_ / answer_text_ / answer_thumbnail_
     */
    private function checkAddMoreForm( $form, $uniqid='', $requestParam=[], $defaultNum=0 ) // {{{
    {

        if ( !$form || !$uniqid || !$requestParam || !is_array($requestParam) ) return $form ;

        if ( empty($defaultNum) ) $defaultNum = self::_DEFAULT_CHILD_ROW_;

        $check_lists_keyword = [ 'pulldown', 'check', 'radio' ];
        $check_lists_name = [ 'answer', 'view_type', 'answer_text', 'answer_thumbnail' ];

        $str_delemiter = '.';

        $count_check_array = [];

        foreach ( $requestParam as $key => $value ) {
            if ( strpos( $key, '_' .$uniqid .'_' ) !== false ) {
                foreach ( $check_lists_keyword as $ckeyword ) {
                    foreach ( $check_lists_name as $cname ) {
                        $keyPrefix = AddFormContentType::_FORM_NAME_PREFIX_ .$ckeyword .'_' .$uniqid .'_' .$cname .'_';
                        if ( substr($key, 0, strlen($keyPrefix)) == $keyPrefix ) {
                            if ( isset($count_check_array[$ckeyword .$str_delemiter .$cname]) ) {
                                $count_check_array[$ckeyword .$str_delemiter .$cname]++;
                            } else {
                                $count_check_array[$ckeyword .$str_delemiter .$cname] = 1;
                            }
                        }
                    }
                }
            }
        }

        if ( $count_check_array ) {
            foreach ( $count_check_array as $key => $count_row ) {
                if ( $count_row > $defaultNum ) {
                    // 追加のレコードが存在する
                    $key_name = explode( $str_delemiter, $key );
                    $keyPrefix = AddFormContentType::_FORM_NAME_PREFIX_ .$key_name[0] .'_' .$uniqid .'_' .$key_name[1] .'_';

                    for ( $i=($defaultNum + 1); $i<=$count_row; $i++ ) {
                        if ( !$form->has($keyPrefix .$i) ) {
                            switch ( $key_name[1] ) {
                                case 'answer':
                                    $form
                                        ->add($keyPrefix .$i, TextType::class, [
                                            'mapped' => false,
                                            'required' => false,
                                            'attr' => [
                                                'placeholder' => trans('se_enquete.common.addform_placeholder.choice'),
                                            ],
                                        ]);
                                    break;
                                case 'view_type':
                                    $form
                                        ->add($keyPrefix .$i, ChoiceType::class, [
                                            'mapped' => false,
                                            'required' => false,
                                            'choices' => array_flip( CommonUtil::replaceTrans( $this->eccubeConfig['Se_Enquete_master_list']['list_radio'] ) ),
                                            'expanded' => false,
                                            'multiple' => false,
                                            'placeholder' => false,
                                        ])
                                        ->add(AddFormContentType::_FORM_NAME_PREFIX_ .$key_name[0] .'_' .$uniqid .'_' .'answer_text' .'_' .$i, TextType::class, [
                                            'mapped' => false,
                                            'required' => false,
                                            'attr' => [
                                                'placeholder' => trans('se_enquete.common.addform_placeholder.choice'),
                                            ],
                                        ])
                                        ->add(AddFormContentType::_FORM_NAME_PREFIX_ .$key_name[0] .'_' .$uniqid .'_' .'answer_thumbnail' .'_' .$i, FileType::class, [
                                            'mapped' => false,
                                            'required' => false,
                                        ])
                                        ->add(AddFormContentType::_FORM_NAME_PREFIX_ .$key_name[0] .'_' .$uniqid .'_' .'answer_hidden' .'_' .$i, HiddenType::class, [
                                            'mapped' => false,
                                            'required' => false,
                                        ]);
                                    break;
                                case 'answer_text':
                                case 'answer_thumbnail':
                                    // view_type で処理されるので無視
                                    break;
                                default:
                                    break;
                            }
                        }
                    }
                }
            }
        }

        return $form;

    } // }}}


    private static function uniqidReal( $lenght = 13 ) // {{{
    {

        // uniqid gives 13 chars, but you could adjust it to your needs.
        if ( function_exists("random_bytes") ) {

            $bytes = random_bytes(ceil($lenght / 2));

        } else
        if ( function_exists("openssl_random_pseudo_bytes") ) {

            $bytes = openssl_random_pseudo_bytes(ceil($lenght / 2));

        } else {

            $alfaRange = range('a', 'z');
            $numRange = range(0, 9);
            $totalRange = array_merge( $alfaRange, $numRange );
            $uniqId = '';
            for ( $i=1; $i<=13; $i++ ) {
                shuffle($totalRange);
                $randKey = array_rand( $totalRange );
                $uniqId .= $totalRange[$randKey];
            }
            return $uniqId;

        }

        return substr(bin2hex($bytes), 0, $lenght);

    }

}
