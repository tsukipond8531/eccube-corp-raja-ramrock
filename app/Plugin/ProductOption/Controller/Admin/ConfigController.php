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

use Plugin\ProductOption\Repository\ConfigRepository;
use Plugin\ProductOption\Form\Type\Admin\ConfigType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

class ConfigController extends \Eccube\Controller\AbstractController
{
    /**
     * @var ConfigRepository
     */
    private $configRepository;

    public function __construct(
            ConfigRepository $configRepository
            )
    {
        $this->configRepository = $configRepository;
    }

    /**
     * @Route("/%eccube_admin_route%/setting/product_option/config", name="product_option_admin_config")
     * @Template("@ProductOption/admin/Setting/config.twig")
     */
    public function index(Request $request)
    {
        $form = $this->formFactory
            ->createBuilder(ConfigType::class)
            ->getForm();

        $Configs = $this->configRepository->findAll();

        foreach($Configs as $config) {
            if(is_null($config->getValue()) || is_array($config->getValue())) continue;
            $form[$config->getName()]->setData($config->getValue());
        }

        if ('POST' === $request->getMethod()) {
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                //設定内容を一度クリア
                foreach($Configs as $config){
                    $this->entityManager->remove($config);
                }
                $this->entityManager->flush();

                //設定登録
                $Values = $form->getData();
                foreach($Values as $name => $value){
                    $Config = new \Plugin\ProductOption\Entity\ProductOptionConfig();
                    $Config->setName($name);
                    $Config->setValue($value);
                    $this->entityManager->persist($Config);
                }
                $this->entityManager->flush();
                $this->addSuccess('admin.setting.productoption.save.complete', 'admin');
            }
        }

        return [
            'form' => $form->createView(),
        ];
    }
}