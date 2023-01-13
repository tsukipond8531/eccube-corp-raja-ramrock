<?php

/*
 * Copyright(c) 2020 Shadow Enterprise, Inc. All rights reserved.
 * http://www.shadow-ep.co.jp/
 */

namespace Plugin\SeEnquete4;

use Eccube\Plugin\AbstractPluginManager;
use Eccube\Common\EccubeConfig;
use Eccube\Entity\Block;
use Eccube\Entity\BlockPosition;
use Eccube\Entity\Layout;
use Eccube\Entity\Master\DeviceType;
use Eccube\Entity\Page;
use Eccube\Entity\PageLayout;
use Eccube\Entity\Payment;
use Plugin\SeEnquete4\Entity\Enquete;
use Plugin\SeEnquete4\Entity\EnqueteConfig;
use Plugin\SeEnquete4\Entity\EnqueteItem;
use Plugin\SeEnquete4\Entity\EnqueteMeta;
use Plugin\SeEnquete4\Entity\EnqueteUser;
use Plugin\SeEnquete4\Util\CommonUtil;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;

class PluginManager extends AbstractPluginManager
{

    /**
     * Uninstall the plugin.
     *
     * @param array $meta
     * @param ContainerInterface $container
     */
    public function uninstall(array $meta, ContainerInterface $container)
    {
        CommonUtil::logInfo('PluginManager::uninstall start.');

        // ブロックの削除
        $this->removeDataBlock($container);
        $this->removeBlock($container);

        // ページレイアウトの削除
        $this->removePageLayout($container);

        CommonUtil::logInfo('PluginManager::uninstall end.');
    }

    /**
     * Update the plugin.
     *
     * @param array $meta
     * @param ContainerInterface $container
     */
    public function update(array $meta, ContainerInterface $container)
    {
        CommonUtil::logInfo('PluginManager::update start.');

        try {
            // プラグイン設定用等のレコードを生成
            $this->createConfig($container);
            $this->createPageLayout($container);
            $this->copyBlock($container);
        } catch (\Exception $e) {
            CommonUtil::logError($e->getMessage());
            throw $e;
        }

        CommonUtil::logInfo('PluginManager::update end.');
    }

    /**
     * Enable the plugin.
     *
     * @param array $meta
     * @param ContainerInterface $container
     */
    public function enable(array $meta, ContainerInterface $container)
    {
        CommonUtil::logInfo('PluginManager::enable start.');

        try {
            // プラグイン設定用のレコードを生成
            $this->createConfig($container);
            // 新規ページ用のデータを追加 dtb_page
            $this->createPageLayout($container);
            // 初回はサンプル用データを追加
            $this->createSampleProduct($container);
            // 全ページで利用できるJSファイルを設置 - app/template配下へブロックのtwigファイルを配置
            $this->copyBlock($container);
            // Block存在確認してなければLayout作成
            $this->createDataBlock($container);
        } catch (\Exception $e) {
            CommonUtil::logError($e->getMessage());
            throw $e;
        }

        CommonUtil::logInfo('PluginManager::enable end.');
    }

    /**
     * Disable the plugin.
     *
     * @param array $meta
     * @param ContainerInterface $container
     */
    public function disable(array $meta, ContainerInterface $container)
    {
        CommonUtil::logInfo('PluginManager::disable start.');

        $this->removeDataBlock($container);
        $this->removeBlock($container);

        CommonUtil::logInfo('PluginManager::disable end.');
    }

    /**
     * プラグイン設定用のレコードを生成
     */
    private function createConfig(ContainerInterface $container)
    {
        CommonUtil::logInfo('PluginManager::createConfig start.');

        $entityManager = $container->get('doctrine')->getManager();
        $EnqueteConfig = $entityManager->getRepository(EnqueteConfig::class)->findBy([]);

        // update の場合どうするか・・ToDo
        if ( $EnqueteConfig ) {
            CommonUtil::logInfo('EnqueteConfig found.');
            return;
        }

        $EccubeConfig = $container->get(EccubeConfig::class);

        $inisitalData = $EccubeConfig['Se_Enquete_config_initial_data'];

        $now = new \DateTime(date("Y-m-d H:i:s"), new \DateTimeZone('Asia/Tokyo'));

        $sortNo = 0;
        foreach ( $inisitalData as $key => $value ) {
            $EnqueteConfig = new EnqueteConfig();

            $EnqueteConfig->setTitle( $value['title'] );
            $EnqueteConfig->setKeyword( $value['keyword'] );
            $EnqueteConfig->setSortNo( $sortNo );
            $EnqueteConfig->setCreateDate( $now );
            $EnqueteConfig->setUpdateDate( $now );
            $EnqueteConfig->setDeleted( 0 );

            $entityManager->persist($EnqueteConfig);

            $sortNo++;
        }

        $entityManager->flush();

        CommonUtil::logInfo('PluginManager::createConfig end.');
    }

    /**
     * アンケート/リスト・詳細向けにページおよびページレイアウトを生成
     */
    private function createPageLayout(ContainerInterface $container)
    {
        CommonUtil::logInfo('PluginManager::createPageLayout start.');

        $entityManager = $container->get('doctrine')->getManager();

        $EccubeConfig = $container->get(EccubeConfig::class);

        $url_list = $EccubeConfig['Se_Enquete_page_initial_data']; 

        foreach ( $url_list as $url => $info ) {
            // 存在確認
            $Page = $entityManager
                ->getRepository(Page::class)->findOneBy(['url' => $url]);
            if ($Page) {
                CommonUtil::logInfo('Page found [' .$url .'].');
                continue;
            }

            // ページを生成
            $now = new \DateTime(date("Y-m-d H:i:s"), new \DateTimeZone('Asia/Tokyo'));
            $Page = new Page();
            $Page->setName( $info['name'] );
            $Page->setUrl($url);
            $Page->setFileName( '@' .$info['filename'] );
            $Page->setEditType(Page::EDIT_TYPE_DEFAULT);
            $Page->setCreateDate($now);
            $Page->setUpdateDate($now);
            $Page->setMetaRobots('noindex');

            // 保存
            $entityManager->persist($Page);
            $entityManager->flush();

            $layout_id = 2;

            // レイアウトを取得
            $Layout = $entityManager->find(Layout::class, $layout_id);

            // ページレイアウトのソート番号最大値を取得
            $MaxSortNoPageLayout = $entityManager->getRepository(PageLayout::class)
                ->findOneBy(['layout_id' => $layout_id], ['sort_no' => 'desc']);

            // ページレイアウトを生成
            $PageLayout = new PageLayout();
            $PageLayout->setPageId($Page->getId());
            $PageLayout->setPage($Page);
            $PageLayout->setLayoutId($layout_id);
            $PageLayout->setLayout($Layout);
            $PageLayout->setSortNo($MaxSortNoPageLayout->getSortNo() + 1);

            // 保存
            $entityManager->persist($PageLayout);
            $entityManager->flush();
        }

        CommonUtil::logInfo('PluginManager::createPageLayout end.');
    }


    /**
     * アンケート/リスト・詳細向けのページおよびページレイアウトを削除
     */
    private function removePageLayout(ContainerInterface $container)
    {
        CommonUtil::logInfo('PluginManager::removePageLayout start.');

        $entityManager = $container->get('doctrine')->getManager();

        $EccubeConfig = $container->get(EccubeConfig::class);

        $url_list = $EccubeConfig['Se_Enquete_page_initial_data'];

        foreach ( $url_list as $url => $info ) {
            // 存在確認
            $Page = $entityManager
                ->getRepository(Page::class)->findOneBy(['url' => $url]);
            if (!$Page) {
                CommonUtil::logInfo('Page not found [' .$url .'].');
                continue;
            }

            // レイアウトを取得
            $LayoutEntities = $entityManager
                    ->getRepository(PageLayout::class)->findBy([
                        'page_id' => $Page->getId()
                    ]);

            if ( $LayoutEntities ) {
                foreach ( $LayoutEntities as $LayoutEntity ) {
                    $entityManager->remove($LayoutEntity);
                    $entityManager->flush();
                }
            }

            $entityManager->remove($Page);
            $entityManager->flush();
        }

        CommonUtil::logInfo('PluginManager::removePageLayout end.');
    }

    /**
     * 初回はサンプルアンケートと回答を１件ずつを登録する
     *   plg_enquete / plg_enquete_meta / plg_enquete_item / plg_enquete_user
     */
    private function createSampleProduct(ContainerInterface $container)
    {
        CommonUtil::logInfo('PluginManager::createSampleProduct start.');

        $entityManager = $container->get('doctrine')->getManager();

        $EccubeConfig = $container->get(EccubeConfig::class);

        $now      = new \DateTime(date("Y-m-d H:i:s"), new \DateTimeZone('Asia/Tokyo'));    // create - update - start
        $end_date = new \DateTime(date("Y-m-d H:i:s"), new \DateTimeZone('Asia/Tokyo'));    // end ( 翌月末を終了日とする last day of next month )
        $end_date->modify('last day of next month');

        $article_list = $EccubeConfig['Se_Enquete_sample_article'];
        $answer_list  = $EccubeConfig['Se_Enquete_sample_answer'];

        // 存在確認 - 存在すれば登録しない
        $article = $entityManager
                ->getRepository(Enquete::class)->findOneBy([]);

        if ( $article ) {

            CommonUtil::logInfo('Sample enquete found.');

        } else {

            // アンケート本体を保存
            $Enquete = new Enquete();
            $childMeta = [];
            foreach ( $article_list as $key => $value ) {
                if ( $key == 'children' ) { $childMeta = $value; continue; }
                $Enquete->{'set' .CommonUtil::changeStrToCamelCase($key)}( $value );
            }

            // 不足分を補正
            $Enquete->setStartDate( $now );
            $Enquete->setEndDate( $end_date );
            $Enquete->setCreateDate( $now );
            $Enquete->setUpdateDate( $now );
            $Enquete->setDeleted( 0 );

            // 保存
            $entityManager->persist( $Enquete );
            $entityManager->flush();

            // アンケート設問を保存
            if ( $Enquete->getId() && $childMeta ) {

                $sortNoMeta = 1;
                foreach ( $childMeta as $Meta ) {

                    $EnqueteMeta = new EnqueteMeta();
                    $childItem = [];
                    foreach( $Meta as $key => $value ) {
                        if ( $key == 'children' ) { $childItem = $value ; continue; }
                        if ( $key == 'enquete_config' ) {
                            $value = $entityManager->getRepository(EnqueteConfig::class)->find($value);
                        }
                        $EnqueteMeta->{'set' .CommonUtil::changeStrToCamelCase($key)}( $value );
                    }

                    // 不足分を補正
                    $EnqueteMeta->setEnquete( $Enquete );
                    $EnqueteMeta->setCreateDate( $now );
                    $EnqueteMeta->setUpdateDate( $now );
                    $EnqueteMeta->setSortNo( $sortNoMeta );
                    $EnqueteMeta->setDeleted( 0 );

                    // 保存
                    $entityManager->persist( $EnqueteMeta );
                    $entityManager->flush();

                    if ( $EnqueteMeta->getId() && $childItem ) {

                        $sortNoItem = 1;
                        foreach ( $childItem as $Item ) {

                            $EnqueteItem = new EnqueteItem();
                            foreach( $Item as $ikey => $ivalue ) {
                                $EnqueteItem->{'set' .CommonUtil::changeStrToCamelCase($ikey)}( $ivalue );
                            }

                            // 不足分を補正
                            $EnqueteItem->setEnqueteMeta( $EnqueteMeta );
                            $EnqueteItem->setCreateDate( $now );
                            $EnqueteItem->setUpdateDate( $now );
                            $EnqueteItem->setSortNo( $sortNoItem );
                            $EnqueteItem->setDeleted( 0 );

                            // 保存
                            $entityManager->persist( $EnqueteItem );
                            $entityManager->flush();

                            $sortNoItem++;
                        }
                    }

                    $sortNoMeta++;
                }
            }

            // アンケート結果を保存
            if ( $Enquete->getId() ) {

                $EnqueteUser = new EnqueteUser();
                foreach ( $answer_list as $key => $value ) {
                    
                    $EnqueteUser->{'set' .CommonUtil::changeStrToCamelCase($key)}( $value );
                }

                // 不足分を補正
                $EnqueteUser->setEnquete( $Enquete );
                $EnqueteUser->setCreateDate( $now );
                $EnqueteUser->setUpdateDate( $now );
                $EnqueteUser->setDeleted( 0 );

                // 保存
                $entityManager->persist( $EnqueteUser );
                $entityManager->flush();

            }

        }

        CommonUtil::logInfo('PluginManager::createSampleProduct end.');
    }

    /**
     * 全ページでJSが起動されるようにBlock追加用ファイルをECCUBEデフォルトディレクトリへ設置する
     */
    private function copyBlock(ContainerInterface $container)
    {
        CommonUtil::logInfo('PluginManager::copyBlock start.');

        $EccubeConfig = $container->get(EccubeConfig::class);

        // 対象のブロックファイル
        $blockFile = __DIR__ .'/' .ltrim($EccubeConfig['Se_Enquete_org_block_file'], '/');

        // 現在のテーマのテンプレートにファイルを配置するため現在のテーマのディレクトリを取得します。
        $templateDir = $container->getParameter('eccube_theme_front_dir');

        // コピー先にファイルがない場合のみファイルをコピーします
        $file = new Filesystem();

        if ( !$file->exists( $templateDir .'/Block/' .basename($blockFile) ) ) {
            // app/template配下へブロックのtwigファイルを配置
            $file->copy( $blockFile, $templateDir .'/Block/' .basename($blockFile) );
            CommonUtil::logInfo('PluginManager::copyBlock copy success');
        } else {
            CommonUtil::logInfo('PluginManager::copyBlock file existed');
        }

        CommonUtil::logInfo('PluginManager::copyBlock end.');
    }

    /**
     * Block template を削除.
     *
     * @param ContainerInterface $container
     */
    private function removeBlock(ContainerInterface $container)
    {
        CommonUtil::logInfo('PluginManager::removeBlock start.');

        $EccubeConfig = $container->get(EccubeConfig::class);

        // 対象のブロックファイル名
        $blockFile = $EccubeConfig['Se_Enquete_org_block_file'];

        $templateDir = $container->getParameter('eccube_theme_front_dir');
        $file = new Filesystem();

        if ( $file->exists( $templateDir .'/Block/' .basename($blockFile) ) ) {
            $file->remove( $templateDir .'/Block/' .basename($blockFile) );
            CommonUtil::logInfo('PluginManager::removeBlock remove success');
        }

        CommonUtil::logInfo('PluginManager::removeBlock end.');
    }

    /**
     * ブロックを登録.
     *
     * @param ContainerInterface $container
     *
     * @throws \Exception
     */
    private function createDataBlock(ContainerInterface $container)
    {
        CommonUtil::logInfo('PluginManager::createDataBlock start.');

        $entityManager = $container->get('doctrine')->getManager();

        $EccubeConfig = $container->get(EccubeConfig::class);

        // 登録ファイル名(拡張子は除外)
        $blockFileName = basename($EccubeConfig['Se_Enquete_org_block_file'], '.twig');

        // 登録ファイル名称
        $blockName = $EccubeConfig['Se_Enquete_org_block_name'];

        $Block = $entityManager->getRepository(Block::class)->findOneBy(['file_name' => basename($blockFileName)]);

        if ($Block) {
            CommonUtil::logInfo('Block found [' .basename($blockFileName) .'].');
            CommonUtil::logInfo('PluginManager::createDataBlock end.');
            return;
        }

        $DeviceType = $entityManager->getRepository(DeviceType::class)->find(DeviceType::DEVICE_TYPE_PC);

        /** @var Block $Block */
        $Block = $entityManager->getRepository(Block::class)->newBlock($DeviceType);

        // Blockの登録
        $Block->setName($blockName)
            ->setFileName($blockFileName)
            ->setUseController(false)
            ->setDeletable(false);
        $entityManager->persist($Block);
        $entityManager->flush();

        try {

            // デフォルトの全てのレイアウトで追加処理
            $defaultLayoutList = [ /*Layout::DEFAULT_LAYOUT_PREVIEW_PAGE,*/ Layout::DEFAULT_LAYOUT_TOP_PAGE, Layout::DEFAULT_LAYOUT_UNDERLAYER_PAGE ];

            foreach ( $defaultLayoutList as $layoutId ) {

                $Layout = $Layout = $entityManager->getRepository(Layout::class)->find( $layoutId );

                // check exists block position
                $blockPos = $entityManager->getRepository(BlockPosition::class)->findOneBy([
                    'Block' => $Block,
                    'Layout' => $Layout
                ]);
                if ($blockPos) {
                    continue;
                }

                // BlockPositionの登録
                $blockPos = $entityManager->getRepository(BlockPosition::class)->findOneBy([
                    'section' => Layout::TARGET_ID_MAIN_BOTTOM, 
                    'Layout' => $Layout
                ], ['block_row' => 'DESC']);

                $BlockPosition = new BlockPosition();

                // ブロックの順序を変更
                $BlockPosition->setBlockRow(1);
                if ($blockPos) {
                    $blockRow = $blockPos->getBlockRow() + 1;
                    $BlockPosition->setBlockRow($blockRow);
                }

                $BlockPosition->setLayout($Layout)
                    ->setLayoutId($Layout->getId())
                    ->setSection(Layout::TARGET_ID_MAIN_BOTTOM)
                    ->setBlock($Block)
                    ->setBlockId($Block->getId());

                $entityManager->persist($BlockPosition);
                $entityManager->flush();
            }
        } catch (\Exception $e) {
            throw $e;
        }

        CommonUtil::logInfo('PluginManager::createDataBlock end.');
    }

    /**
     * ブロックを削除.
     *
     * @param ContainerInterface $container
     *
     * @throws \Exception
     */
    private function removeDataBlock(ContainerInterface $container)
    {
        CommonUtil::logInfo('PluginManager::removeDataBlock start.');

        $entityManager = $container->get('doctrine')->getManager();

        $EccubeConfig = $container->get(EccubeConfig::class);

        // 登録ファイル名(拡張子は除外)
        $blockFileName = basename($EccubeConfig['Se_Enquete_org_block_file'], '.twig');

        // Blockの取得(file_nameはアプリケーションの仕組み上必ずユニーク)
        /** @var \Eccube\Entity\Block $Block */
        $Block = $entityManager->getRepository(Block::class)->findOneBy(['file_name' => $blockFileName]);

        if (!$Block) {
            CommonUtil::logInfo('Block found [' .basename($blockFileName) .'].');
            CommonUtil::logInfo('PluginManager::removeDataBlock end.');
            return;
        }

        try {
            // BlockPositionの削除
            $blockPositions = $Block->getBlockPositions();
            /** @var \Eccube\Entity\BlockPosition $BlockPosition */
            foreach ($blockPositions as $BlockPosition) {
                $Block->removeBlockPosition($BlockPosition);
                $entityManager->remove($BlockPosition);
            }

            // Blockの削除
            $entityManager->remove($Block);
            $entityManager->flush();
        } catch (\Exception $e) {
            throw $e;
        }

        CommonUtil::logInfo('PluginManager::removeDataBlock end.');
    }

}
