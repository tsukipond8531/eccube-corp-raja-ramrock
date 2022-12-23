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
use Plugin\ProductOption\Repository\OptionRepository;
use Plugin\ProductOption\Repository\OptionCategoryRepository;
use Plugin\ProductOption\Repository\OptionImageRepository;
use Plugin\ProductOption\Entity\OptionCategory;
use Plugin\ProductOption\Form\Type\Admin\OptionCategoryType;
use Plugin\ProductOption\Form\Type\Admin\OptionTextCategoryType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnsupportedMediaTypeHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;

class OptionCategoryController extends AbstractController
{

    /**
     * @var OptionRepository
     */
    private $optionRepository;

    /**
     * @var OptionCategoryRepository
     */
    private $optionCategoryRepository;

    private $optionImageRepository;

    private $router;

    /**
     * OptionController constructor.
     * @param OptionRepository $optionRepository
     */
    public function __construct(
            OptionRepository $optionRepository,
            OptionCategoryRepository $optionCategoryRepository,
            OptionImageRepository $optionImageRepository,
            RouterInterface $router
            )
    {
        $this->optionRepository = $optionRepository;
        $this->optionCategoryRepository = $optionCategoryRepository;
        $this->optionImageRepository = $optionImageRepository;
        $this->router = $router;
    }
    /**
     * @Route("/%eccube_admin_route%/product/option_category/{option_id}", requirements={"option_id" = "\d+"}, name="admin_product_option_category")
     * @Route("/%eccube_admin_route%/product/option_category/{option_id}/new", requirements={"option_id" = "\d+"}, name="admin_product_option_category_new")
     * @Route("/%eccube_admin_route%/product/option_category/{option_id}/{id}/edit", requirements={"id" = "\d+", "option_id" = "\d+"}, name="admin_product_option_category_edit")
     * @Template("@ProductOption/admin/Product/option_category.twig")
     */
    public function index(Request $request, $option_id, $id = null)
    {
        //
        $Option = $this->optionRepository->find($option_id);
        if (!$Option) {
            throw new NotFoundHttpException();
        }
        if ($id) {
            $TargetOptionCategory = $this->optionCategoryRepository->find($id);
            if (!$TargetOptionCategory || $TargetOptionCategory->getOption() != $Option) {
                throw new NotFoundHttpException();
            }
        } else {
            $TargetOptionCategory = new OptionCategory();
            $TargetOptionCategory->setOption($Option);
        }

        //
        $form = $this->formFactory
                ->createBuilder(OptionCategoryType::class, $TargetOptionCategory)
                ->getForm();

        // ファイルの登録
        $images = [];
        $OptionImages = $TargetOptionCategory->getOptionImages();
        foreach ($OptionImages as $OptionImage) {
            $images[] = $OptionImage->getFileName();
        }
        $form['images']->setData($images);

        if ($request->getMethod() === 'POST') {
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                // 画像の登録
                $add_images = $form->get('add_images')->getData();
                $sort_no = 0;
                foreach ($add_images as $add_image) {
                    $sort_no++;
                    $OptionImage = new \Plugin\ProductOption\Entity\OptionImage();
                    $OptionImage
                        ->setFileName($add_image)
                        ->setOptionCategory($TargetOptionCategory)
                        ->setSortNo($sort_no);
                    $TargetOptionCategory->addOptionImage($OptionImage);
                    $this->entityManager->persist($OptionImage);

                    // 移動
                    $file = new File($this->eccubeConfig['eccube_temp_image_dir'].'/'.$add_image);
                    $file->move($this->eccubeConfig['eccube_save_image_dir']);
                }

                // 画像の削除
                $delete_images = $form->get('delete_images')->getData();
                foreach ($delete_images as $delete_image) {
                    $OptionImage = $this->optionImageRepository
                        ->findOneBy(['file_name' => $delete_image]);

                    // 追加してすぐに削除した画像は、Entityに追加されない
                    if ($OptionImage instanceof \Plugin\ProductOption\Entity\OptionImage) {
                        $TargetOptionCategory->removeOptionImage($OptionImage);
                        $this->entityManager->remove($OptionImage);
                    }

                    // 削除
                    $fs = new Filesystem();
                    $fs->remove($this->eccubeConfig['eccube_save_image_dir'].'/'.$delete_image);
                }

                $sortNos = $request->get('sort_no_images');
                if ($sortNos) {
                    foreach ($sortNos as $sortNo) {
                        list($filename, $sortNo_val) = explode('//', $sortNo);
                        $OptionImage = $this->optionImageRepository
                            ->findOneBy([
                                'file_name' => $filename,
                                'OptionCategory' => $TargetOptionCategory,
                            ]);
                        if(!is_null($OptionImage)){
                            $OptionImage->setSortNo($sortNo_val);
                            $this->entityManager->persist($OptionImage);
                            $this->entityManager->flush($OptionImage);
                        }
                    }
                }

                $status = $this->optionCategoryRepository->save($TargetOptionCategory);

                if ($status) {
                    $this->addSuccess('admin.product.option_category.save.complete', 'admin');

                    if ($returnLink = $form->get('return_link')->getData()) {
                        try {
                            // $returnLinkはpathの形式で渡される. pathが存在するかをルータでチェックする.
                            $pattern = '/^'.preg_quote($request->getBasePath(), '/').'/';
                            $returnLink = preg_replace($pattern, '', $returnLink);
                            $result = $this->router->match($returnLink);
                            // パラメータのみ抽出
                            $params = array_filter($result, function ($key) {
                                return 0 !== \strpos($key, '_');
                            }, ARRAY_FILTER_USE_KEY);

                            // pathからurlを再構築してリダイレクト.
                            return $this->redirectToRoute($result['_route'], $params);
                        } catch (\Exception $e) {
                            // マッチしない場合はログ出力してスキップ.
                            log_warning('URLの形式が不正です。');
                        }
                    }

                    return $this->redirectToRoute('admin_product_option_category', ['option_id' => $Option->getId()]);
                } else {
                    $this->addError('admin.product.option_category.save.error', 'admin');
                }
            }
        }

        $OptionCategories = $this->optionCategoryRepository->getList($Option);

        return [
                    'form' => $form->createView(),
                    'Option' => $Option,
                    'OptionCategories' => $OptionCategories,
                    'TargetOptionCategory' => $TargetOptionCategory,
        ];
    }

    /**
     * @Route("/%eccube_admin_route%/product/option_text_category/{option_id}", requirements={"option_id" = "\d+"}, name="admin_product_option_text_category")
     * @Template("@ProductOption/admin/Product/option_text_category.twig")
     */
    public function textCategory(Request $request, $option_id)
    {
        $Option = $this->optionRepository->find($option_id);
        if (!$Option) {
            throw new NotFoundHttpException();
        }
        $OptionCategories = $Option->getOptionCategories();
        if(count($OptionCategories) > 0){
            $TargetOptionCategory = $OptionCategories[0];
        }else{
            $TargetOptionCategory = new OptionCategory();
            $TargetOptionCategory->setOption($Option);
        }

        //
        $form = $this->formFactory
                ->createBuilder(OptionTextCategoryType::class, $TargetOptionCategory)
                ->getForm();

        // ファイルの登録
        $images = [];
        $OptionImages = $TargetOptionCategory->getOptionImages();
        foreach ($OptionImages as $OptionImage) {
            $images[] = $OptionImage->getFileName();
        }
        $form['images']->setData($images);

        if ($request->getMethod() === 'POST') {
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                // 画像の登録
                $add_images = $form->get('add_images')->getData();
                $sort_no = 0;
                foreach ($add_images as $add_image) {
                    $sort_no++;
                    $OptionImage = new \Plugin\ProductOption\Entity\OptionImage();
                    $OptionImage
                        ->setFileName($add_image)
                        ->setOptionCategory($TargetOptionCategory)
                        ->setSortNo($sort_no);
                    $TargetOptionCategory->addOptionImage($OptionImage);
                    $this->entityManager->persist($OptionImage);

                    // 移動
                    $file = new File($this->eccubeConfig['eccube_temp_image_dir'].'/'.$add_image);
                    $file->move($this->eccubeConfig['eccube_save_image_dir']);
                }

                // 画像の削除
                $delete_images = $form->get('delete_images')->getData();
                foreach ($delete_images as $delete_image) {
                    $OptionImage = $this->optionImageRepository
                        ->findOneBy(['file_name' => $delete_image]);

                    // 追加してすぐに削除した画像は、Entityに追加されない
                    if ($OptionImage instanceof \Plugin\ProductOption\Entity\OptionImage) {
                        $TargetOptionCategory->removeOptionImage($OptionImage);
                        $this->entityManager->remove($OptionImage);
                    }

                    // 削除
                    $fs = new Filesystem();
                    $fs->remove($this->eccubeConfig['eccube_save_image_dir'].'/'.$delete_image);
                }
                $sortNos = $request->get('sort_no_images');
                if ($sortNos) {
                    foreach ($sortNos as $sortNo) {
                        list($filename, $sortNo_val) = explode('//', $sortNo);
                        $OptionImage = $this->optionImageRepository
                            ->findOneBy([
                                'file_name' => $filename,
                                'OptionCategory' => $TargetOptionCategory,
                            ]);
                        if(!is_null($OptionImage)){
                            $OptionImage->setSortNo($sortNo_val);
                            $this->entityManager->persist($OptionImage);
                            $this->entityManager->flush($OptionImage);
                        }
                    }
                }


                $status = $this->optionCategoryRepository->save($TargetOptionCategory);

                if ($status) {
                    $this->addSuccess('admin.product.option_text_category.save.complete', 'admin');

                    if ($returnLink = $form->get('return_link')->getData()) {
                        try {
                            // $returnLinkはpathの形式で渡される. pathが存在するかをルータでチェックする.
                            $pattern = '/^'.preg_quote($request->getBasePath(), '/').'/';
                            $returnLink = preg_replace($pattern, '', $returnLink);
                            $result = $this->router->match($returnLink);
                            // パラメータのみ抽出
                            $params = array_filter($result, function ($key) {
                                return 0 !== \strpos($key, '_');
                            }, ARRAY_FILTER_USE_KEY);

                            // pathからurlを再構築してリダイレクト.
                            return $this->redirectToRoute($result['_route'], $params);
                        } catch (\Exception $e) {
                            // マッチしない場合はログ出力してスキップ.
                            log_warning('URLの形式が不正です。');
                        }
                    }

                    return $this->redirectToRoute('admin_product_option_text_category', ['option_id' => $Option->getId()]);
                } else {
                    $this->addError('admin.product.option_text_category.save.error', 'admin');
                }
            }
        }

        return [
                    'form' => $form->createView(),
                    'Option' => $Option,
                    'TargetOptionCategory' => $TargetOptionCategory,
        ];
    }

    /**
     * @Route("/%eccube_admin_route%/product/option_category/{option_id}/{id}/delete", requirements={"option_id" = "\d+","id" = "\d+"}, name="admin_product_option_category_delete", methods={"DELETE"})
     */
    public function delete(Request $request, $option_id, $id)
    {
        $this->isTokenValid();

        $Option = $this->optionRepository->find($option_id);
        if (!$Option) {
            throw new NotFoundHttpException();
        }

        $OptionCategory = $this->optionCategoryRepository->find($id);
        if (!$OptionCategory || $OptionCategory->getOption() != $Option) {
            throw new NotFoundHttpException();
        }
        //
        $status = false;
        $status = $this->optionCategoryRepository->delete($OptionCategory);

        if ($status === true) {
            $this->addSuccess('admin.product.option_category.delete.complete', 'admin');
        } else {
            $this->addError('admin.product.option_category.delete.error', 'admin');
        }

        return $this->redirectToRoute('admin_product_option_category', ['option_id' => $Option->getId()]);
    }

    /**
     * @Route("/%eccube_admin_route%/product/option_category/sort_no/move", name="admin_product_option_category_sort_no_move",methods={"POST"})
     */
    public function moveSortNo(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            $sortNos = $request->request->all();
            foreach ($sortNos as $optionCategoryId => $sortNo) {
                $OptionCategory = $this->optionCategoryRepository
                        ->find($optionCategoryId);
                $OptionCategory->setSortNo($sortNo);
                $this->entityManager->persist($OptionCategory);
            }
            $this->entityManager->flush();
        }
        return new Response();
    }

    /**
     * @Route("/%eccube_admin_route%/product/option_category/image/add", name="admin_product_option_category_image_add", methods={"POST"})
     */
    public function addImage(Request $request)
    {
        if (!$request->isXmlHttpRequest()) {
            throw new BadRequestHttpException();
        }
        log_info('画像アップロード開始');
        $images = $request->files->get('option_category');

        $files = [];
        if (count($images) > 0) {
            foreach ($images as $img) {
                foreach ($img as $image) {
                    //ファイルフォーマット検証
                    $mimeType = $image->getMimeType();
                    if (0 !== strpos($mimeType, 'image')) {
                        throw new UnsupportedMediaTypeHttpException('ファイル形式が不正です');
                    }

                    $extension = $image->getClientOriginalExtension();
                    $filename = date('mdHis') . uniqid('_') . '.' . $extension;
                    $image->move($this->eccubeConfig['eccube_temp_image_dir'], $filename);
                    $files[] = $filename;
                }
            }
        }
        log_info('画像アップロード完了');

        return $this->json(['files' => $files], 200);
    }

    /**
     * @Route("/%eccube_admin_route%/product/option_text_category/image/add", name="admin_product_option_text_category_image_add", methods={"POST"})
     */
    public function addTextImage(Request $request)
    {
        if (!$request->isXmlHttpRequest()) {
            throw new BadRequestHttpException();
        }
        log_info('画像アップロード開始');
        $images = $request->files->get('option_text_category');

        $files = [];
        if (count($images) > 0) {
            foreach ($images as $img) {
                foreach ($img as $image) {
                    //ファイルフォーマット検証
                    $mimeType = $image->getMimeType();
                    if (0 !== strpos($mimeType, 'image')) {
                        throw new UnsupportedMediaTypeHttpException('ファイル形式が不正です');
                    }

                    $extension = $image->getClientOriginalExtension();
                    $filename = date('mdHis') . uniqid('_') . '.' . $extension;
                    $image->move($this->eccubeConfig['eccube_temp_image_dir'], $filename);
                    $files[] = $filename;
                }
            }
        }
        log_info('画像アップロード完了');

        return $this->json(['files' => $files], 200);
    }

}
