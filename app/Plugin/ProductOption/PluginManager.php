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

namespace Plugin\ProductOption;

use Eccube\Plugin\AbstractPluginManager;
use Eccube\Entity\Master\CsvType;
use Eccube\Entity\Csv;
use Plugin\ProductOption\Entity\Option;
use Plugin\ProductOption\Entity\ProductOptionConfig;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Yaml\Yaml;

class PluginManager extends AbstractPluginManager
{
    public function install(array $meta, ContainerInterface $container)
    {
        $file = new Filesystem();
        try {
            $file->copy($container->getParameter('plugin_realdir'). '/ProductOption/Resource/template/default/Product/option.twig', $container->getParameter('eccube_theme_front_dir'). '/Product/option.twig', true);
            $file->copy($container->getParameter('plugin_realdir'). '/ProductOption/Resource/template/default/Product/option_description.twig', $container->getParameter('eccube_theme_front_dir'). '/Product/option_description.twig', true);
            $file->copy($container->getParameter('plugin_realdir'). '/ProductOption/Resource/template/default/Product/option_css.twig', $container->getParameter('eccube_theme_front_dir'). '/Product/option_css.twig', true);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function uninstall(array $meta, ContainerInterface $container)
    {
        if(file_exists($container->getParameter('eccube_theme_front_dir') . '/Product/option.twig'))
                unlink($container->getParameter('eccube_theme_front_dir') . '/Product/option.twig');
        if(file_exists($container->getParameter('eccube_theme_front_dir') . '/Product/option_description.twig'))
                unlink($container->getParameter('eccube_theme_front_dir') . '/Product/option_description.twig');
        if(file_exists($container->getParameter('eccube_theme_front_dir') . '/Product/option_css.twig'))
                unlink($container->getParameter('eccube_theme_front_dir') . '/Product/option_css.twig');
    }

    public function enable(array $meta, ContainerInterface $container)
    {
        // 初回有効時に設定の初期値を設定
        $entityManager = $container->get('doctrine.orm.entity_manager');
        $Configs = $entityManager->getRepository(ProductOptionConfig::class)->findAll();
        if(count($Configs) == 0){
            $SetConfigs = [
                ProductOptionConfig::RANGE_NAME => ProductOptionConfig::BY_ALL,
                    ];
            foreach($SetConfigs as $name => $value){
                $Config = new ProductOptionConfig();
                $Config->setName($name);
                $Config->setValue($value);
                $entityManager->persist($Config);
            }
            $entityManager->flush();
        }
        $this->addCsv($container);
    }

    public function disable(array $meta, ContainerInterface $container)
    {
        $entityManager = $container->get('doctrine.orm.entity_manager');
        $Csvs = $entityManager->getRepository(Csv::class)->findBy(['field_name' => 'OrderItemOption']);
        foreach($Csvs as $Csv){
            $entityManager->remove($Csv);
        }
        $Csvs = $entityManager->getRepository(Csv::class)->findBy(['entity_name' => 'Plugin\\ProductOption\\Entity\\ProductOption']);
        foreach($Csvs as $Csv){
            $entityManager->remove($Csv);
        }
        $entityManager->flush();
    }

    private function addCsv($container)
    {
        $translator = $container->get('translator');
        $ymlPath = $container->getParameter('plugin_realdir') . '/ProductOption/Resource/locale/messages.'.$translator->getLocale().'.yaml';
        if(!file_exists($ymlPath))$ymlPath = $container->getParameter('plugin_realdir') . '/ProductOption/Resource/locale/messages.ja.yaml';
        $messages = Yaml::parse(file_get_contents($ymlPath));

        $entityManager = $container->get('doctrine.orm.entity_manager');
        $Options = $entityManager->getRepository(Option::class)->findAll();

        $now = new \DateTime();
        //CSV項目追加
        $Csv = new Csv();
        $CsvType = $entityManager->getRepository(CsvType::class)->find(CsvType::CSV_TYPE_ORDER);
        $sort_no = $entityManager->createQueryBuilder()
            ->select('MAX(c.sort_no)')
            ->from('Eccube\Entity\Csv','c')
            ->where('c.CsvType = :csvType')
            ->setParameter(':csvType',$CsvType)
            ->getQuery()
            ->getSingleScalarResult();
        if (!$sort_no) {
            $sort_no = 0;
        }
        $Csv->setCsvType($CsvType);
        $Csv->setEntityName('Plugin\\ProductOption\\Entity\\OrderItemOption');
        $Csv->setFieldName('OrderItemOption');
        $Csv->setDispName($messages['productoption.csv.common.label']);
        $Csv->setEnabled(false);
        $Csv->setSortNo(++$sort_no);
        $Csv->setCreateDate($now);
        $Csv->setUpdateDate($now);
        $entityManager->persist($Csv);

        foreach($Options as $Option){
            $Csv = new Csv();
            $Csv->setCsvType($CsvType);
            $Csv->setEntityName('Plugin\\ProductOption\\Entity\\OrderItemOption');
            $Csv->setFieldName('OrderItemOption');
            $Csv->setReferenceFieldName($Option->getId());
            $Csv->setDispName($Option->getBackendName());
            $Csv->setEnabled(false);
            $Csv->setSortNo(++$sort_no);
            $Csv->setCreateDate($now);
            $Csv->setUpdateDate($now);
            $entityManager->persist($Csv);
        }

        $Csv = new Csv();
        $CsvType = $entityManager->getRepository(CsvType::class)->find(CsvType::CSV_TYPE_SHIPPING);
        $sort_no = $entityManager->createQueryBuilder()
            ->select('MAX(c.sort_no)')
            ->from('Eccube\Entity\Csv','c')
            ->where('c.CsvType = :csvType')
            ->setParameter(':csvType',$CsvType)
            ->getQuery()
            ->getSingleScalarResult();
        if (!$sort_no) {
            $sort_no = 0;
        }
        $Csv->setCsvType($CsvType);
        $Csv->setEntityName('Plugin\\ProductOption\\Entity\\OrderItemOption');
        $Csv->setFieldName('OrderItemOption');
        $Csv->setDispName($messages['productoption.csv.common.label']);
        $Csv->setEnabled(false);
        $Csv->setSortNo(++$sort_no);
        $Csv->setCreateDate($now);
        $Csv->setUpdateDate($now);
        $entityManager->persist($Csv);

        foreach($Options as $Option){
            $Csv = new Csv();
            $Csv->setCsvType($CsvType);
            $Csv->setEntityName('Plugin\\ProductOption\\Entity\\OrderItemOption');
            $Csv->setFieldName('OrderItemOption');
            $Csv->setReferenceFieldName($Option->getId());
            $Csv->setDispName($Option->getBackendName());
            $Csv->setEnabled(false);
            $Csv->setSortNo(++$sort_no);
            $Csv->setCreateDate($now);
            $Csv->setUpdateDate($now);
            $entityManager->persist($Csv);
        }

        //CSV項目追加
        $CsvType = $entityManager->getRepository(CsvType::class)->find(CsvType::CSV_TYPE_PRODUCT);
        $sort_no = $entityManager->createQueryBuilder()
            ->select('MAX(c.sort_no)')
            ->from('Eccube\Entity\Csv','c')
            ->where('c.CsvType = :csvType')
            ->setParameter(':csvType',$CsvType)
            ->getQuery()
            ->getSingleScalarResult();
        if (!$sort_no) {
            $sort_no = 0;
        }

        $Csv = new Csv();
        $Csv->setCsvType($CsvType);
        $Csv->setEntityName('Plugin\\ProductOption\\Entity\\ProductOption');
        $Csv->setFieldName('product_option_id');
        $Csv->setDispName($messages['productoption.csv.product.label.id']);
        $Csv->setEnabled(false);
        $Csv->setSortNo(++$sort_no);
        $Csv->setCreateDate($now);
        $Csv->setUpdateDate($now);
        $entityManager->persist($Csv);

        $Csv = new Csv();
        $Csv->setCsvType($CsvType);
        $Csv->setEntityName('Plugin\\ProductOption\\Entity\\ProductOption');
        $Csv->setFieldName('product_option_name');
        $Csv->setDispName($messages['productoption.csv.product.label.name']);
        $Csv->setEnabled(false);
        $Csv->setSortNo(++$sort_no);
        $Csv->setCreateDate($now);
        $Csv->setUpdateDate($now);
        $entityManager->persist($Csv);

        $entityManager->flush();
    }
}
