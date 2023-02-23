<?php

/*
 * Copyright(c) 2020 Shadow Enterprise, Inc. All rights reserved.
 * http://www.shadow-ep.co.jp/
 */

namespace Plugin\SeEnquete4\Service;

use GuzzleHttp\Client;
use Eccube\Common\EccubeConfig;
use Eccube\Entity\BaseInfo;
use Eccube\Entity\Customer;
use Eccube\Event\EccubeEvents;
use Eccube\Event\EventArgs;
use Eccube\Repository\BaseInfoRepository;
use Eccube\Service\MailService;
use Eccube\Service\PluginService;
use Plugin\SeEnquete4\Repository\EnqueteRepository;
use Plugin\SeEnquete4\Util\CommonUtil;
use Symfony\Bundle\SwiftmailerBundle\DependencyInjection\SwiftmailerTransportFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class EnqueteMailService
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var \Swift_Mailer
     */
    protected $mailer;

    /**
     * @var EventDispatcher
     */
    protected $eventDispatcher;

    /**
     * @var BaseInfo
     */
    protected $BaseInfo;

    /**
     * @var EccubeConfig
     */
    protected $eccubeConfig;

    /**
     * @var EnqueteRepository
     */
    protected $enqueteRepository;

    /**
     * @var MailService
     */
    protected $mailService;

    /**
     * @var \Twig_Environment
     */
    protected $twig;

    /**
     * MailService constructor.
     *
     * @param ContainerInterface $container
     * @param \Swift_Mailer $mailer
     * @param BaseInfoRepository $baseInfoRepository
     * @param EventDispatcherInterface $eventDispatcher
     * @param EccubeConfig $eccubeConfig
     * @param EnqueteRepository $enqueteRepository
     * @param PluginService $pluginService
     * @param \Twig_Environment $twig
     * @param MailService $mailService
     */
    public function __construct(
        ContainerInterface $container,
        \Swift_Mailer $mailer,
        BaseInfoRepository $baseInfoRepository,
        EventDispatcherInterface $eventDispatcher,
        EccubeConfig $eccubeConfig,
        EnqueteRepository $enqueteRepository,
        PluginService $pluginService,
        \Twig_Environment $twig,
        MailService $mailService
    ) {
        $this->container = $container;
        $this->mailer = $mailer;
        $this->BaseInfo = $baseInfoRepository->get();
        $this->eventDispatcher = $eventDispatcher;
        $this->eccubeConfig = $eccubeConfig;
        $this->enqueteRepository = $enqueteRepository;
        $this->twig = $twig;
        $this->mailService = $mailService;
    }

    /**
     * Send manage mail.
     *
     * @param $enqueteId アンケート管理ID
     *  ECCUBEでのメール設定が自動で読まれないので直接解析
     */
    public function sendEnqueteManageMail( $enqueteId )
    {
        CommonUtil::logInfo('アンケート投稿メール送信開始');

        $enqueteEntity = $this->enqueteRepository->find( $enqueteId );

        // 回答の旨メール送信開始
        $addressList = $enqueteEntity->getAddressList();
        $mailBccList = explode( ',', trim(str_replace(['\r\n', '\r', '\n'], '', $addressList)) );

        if ( ( $enqueteEntity->getMailFlg() == 1 ) && !empty( $mailBccList ) && ( count($mailBccList) > 0 ) ) {

            $url     = $this->eccubeConfig['Swiftmailer_url'];
            $options = SwiftmailerTransportFactory::resolveOptions( [ 'url' => $url ] );

            if ( !$url || !$options || !isset($options['transport']) || empty($options['transport']) ) {

                CommonUtil::logInfo('メール設定情報が無いため処理を終了します');

            } else {

                $requestContext = ( isset($this->services['router.request_context']) ) ? $this->services['router.request_context'] : null ;
                $transport = SwiftmailerTransportFactory::createTransport( $options, $requestContext, new \Swift_Events_SimpleEventDispatcher() );

                $mailer = new \Swift_Mailer($transport);
                $this->mailer = $mailer;

                // ArrayLogger
                $logger = new \Swift_Plugins_Loggers_ArrayLogger();
                $this->mailer->registerPlugin(new \Swift_Plugins_LoggerPlugin($logger));

                // Echo Logger
                $logger = new \Swift_Plugins_Loggers_EchoLogger();
                $this->mailer->registerPlugin(new \Swift_Plugins_LoggerPlugin($logger));

                $mailSubject = '[' .$enqueteEntity->getTitle() .']へ投稿がありました';

                $nowDate = new \DateTime(date("Y-m-d H:i:s"), new \DateTimeZone('Asia/Tokyo'));

                // メール用テンプレート読み込み        
                $mailBody = $this->twig->render('@SeEnquete4/admin/mail.twig', [
                    'now_date' => $nowDate->format('Y-m-d H:i:s'),
                ]);

                $message = (new \Swift_Message())
                    ->setSubject( $mailSubject )
                    ->setFrom( [ $this->BaseInfo->getEmail01() => $this->BaseInfo->getShopName() ] )
                    //->setTo( [ ] )
                    ->setBcc( $mailBccList )
                    ->setReplyTo( $this->BaseInfo->getEmail03() )
                    ->setReturnPath( $this->BaseInfo->getEmail04() );

                // Plainテキストの場合
                $message->setBody( $mailBody );

                // HTMLの場合
                //$message->setContentType('text/plain; charset=UTF-8')
                    //->setBody($body, 'text/plain')
                    //->addPart($mailBody, 'text/html');

                CommonUtil::logInfo('アンケート投稿メール送信中..');

                try {
                    if ( !$count = $this->mailer->send( $message ) ) {
                        CommonUtil::logInfo('アンケート投稿メール送信失敗 [ logger => ' .$logger->dump() .' ]');
                    } else {
                        CommonUtil::logInfo('アンケート投稿メール送信成功 [ count => ' .$count .' ] ');
                    }
                } catch ( \Exception $e ) {
                    CommonUtil::logInfo('アンケート投稿メール送信失敗 [ exception => ' .$e->getMessage() .' ]');
                }
            }
        }

        CommonUtil::logInfo('アンケート投稿メール送信終了');

        return true;
    }

}
