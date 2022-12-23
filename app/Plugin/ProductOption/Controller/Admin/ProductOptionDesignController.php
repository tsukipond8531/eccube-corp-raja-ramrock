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

use Eccube\Util\StringUtil;
use Plugin\ProductOption\Form\Type\Admin\ProductOptionDesignType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Twig\Environment;

class ProductOptionDesignController extends \Eccube\Controller\AbstractController
{
    /**
     * @Route("/%eccube_admin_route%/content/productoption/option", name="admin_content_productoption_option")
     * @Template("@ProductOption/admin/Content/option.twig")
     */
    public function OptionDesign(Request $request, Environment $twig, FileSystem $fs)
    {
        $form = $this->formFactory
                ->createBuilder(ProductOptionDesignType::class)
                ->getForm();

        $html = $twig->getLoader()
                ->getSourceContext('Product/option.twig')
                ->getCode();

        $form->get('option_html')->setData($html);

        if ('POST' === $request->getMethod()) {
            switch($request->get('mode')){
                case 'regist':
                    $form->handleRequest($request);

                    $dir = sprintf('%s/app/template/%s/Product',
                        $this->getParameter('kernel.project_dir'),
                        $this->getParameter('eccube.theme'));

                    $file = $dir.'/option.twig';

                    $source = $form->get('option_html')->getData();
                    $source = StringUtil::convertLineFeed($source);
                    $fs->dumpFile($file, $source);

                    // twigキャッシュの削除
                    $cacheDir = $this->getParameter('kernel.cache_dir').'/twig';
                    $fs->remove($cacheDir);

                    $this->addSuccess('admin.content.productoption.save.complete', 'admin');
                    break;
                case 'init':
                    $requestData = $request->get('product_option_design');
                    $content = file_get_contents($this->getParameter('plugin_realdir') . '/ProductOption/Resource/template/default/Product/option.twig');
                    $requestData['option_html'] = $content;
                    $request->request->set('product_option_design',$requestData);
                    $form->handleRequest($request);
                    break;
                default:
                    break;
            }
        }

        return [
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route("/%eccube_admin_route%/content/productoption/description", name="admin_content_productoption_description")
     * @Template("@ProductOption/admin/Content/description.twig")
     */
    public function descriptionDesign(Request $request, Environment $twig, FileSystem $fs)
    {
        $form = $this->formFactory
                ->createBuilder(ProductOptionDesignType::class)
                ->getForm();

        $html = $twig->getLoader()
                ->getSourceContext('Product/option_description.twig')
                ->getCode();

        $form->get('description_html')->setData($html);

        if ('POST' === $request->getMethod()) {
            switch($request->get('mode')){
                case 'regist':
                    $form->handleRequest($request);

                    $dir = sprintf('%s/app/template/%s/Product',
                        $this->getParameter('kernel.project_dir'),
                        $this->getParameter('eccube.theme'));

                    $file = $dir.'/option_description.twig';

                    $source = $form->get('description_html')->getData();
                    $source = StringUtil::convertLineFeed($source);
                    $fs->dumpFile($file, $source);

                    // twigキャッシュの削除
                    $cacheDir = $this->getParameter('kernel.cache_dir').'/twig';
                    $fs->remove($cacheDir);

                    $this->addSuccess('admin.content.productoption.save.complete', 'admin');
                    break;
                case 'init':
                    $requestData = $request->get('product_option_design');
                    $content = file_get_contents($this->getParameter('plugin_realdir') . '/ProductOption/Resource/template/default/Product/option_description.twig');
                    $requestData['description_html'] = $content;
                    $request->request->set('product_option_design',$requestData);
                    $form->handleRequest($request);
                    break;
                default:
                    break;
            }
        }

        return [
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route("/%eccube_admin_route%/content/productoption/css", name="admin_content_productoption_css")
     * @Template("@ProductOption/admin/Content/css.twig")
     */
    public function cssDesign(Request $request, Environment $twig, FileSystem $fs)
    {
        $form = $this->formFactory
                ->createBuilder(ProductOptionDesignType::class)
                ->getForm();

        $html = $twig->getLoader()
                ->getSourceContext('Product/option_css.twig')
                ->getCode();

        $form->get('css_html')->setData($html);

        if ('POST' === $request->getMethod()) {
            switch($request->get('mode')){
                case 'regist':
                    $form->handleRequest($request);

                    $dir = sprintf('%s/app/template/%s/Product',
                        $this->getParameter('kernel.project_dir'),
                        $this->getParameter('eccube.theme'));

                    $file = $dir.'/option_css.twig';

                    $source = $form->get('css_html')->getData();
                    $source = StringUtil::convertLineFeed($source);
                    $fs->dumpFile($file, $source);

                    // twigキャッシュの削除
                    $cacheDir = $this->getParameter('kernel.cache_dir').'/twig';
                    $fs->remove($cacheDir);

                    $this->addSuccess('admin.content.productoption.save.complete', 'admin');
                    break;
                case 'init':
                    $requestData = $request->get('product_option_design');
                    $content = file_get_contents($this->getParameter('plugin_realdir') . '/ProductOption/Resource/template/default/Product/option_css.twig');
                    $requestData['css_html'] = $content;
                    $request->request->set('product_option_design',$requestData);
                    $form->handleRequest($request);
                    break;
                default:
                    break;
            }
        }

        return [
            'form' => $form->createView(),
        ];
    }
}