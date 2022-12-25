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

namespace Customize\Service;

use Eccube\Common\EccubeConfig;
use Eccube\Entity\BaseInfo;
use Eccube\Entity\Customer;
use Eccube\Entity\MailHistory;
use Eccube\Entity\MailTemplate;
use Eccube\Entity\Order;
use Eccube\Entity\OrderItem;
use Eccube\Entity\Shipping;
use Eccube\Event\EccubeEvents;
use Eccube\Event\EventArgs;
use Eccube\Repository\BaseInfoRepository;
use Eccube\Repository\MailHistoryRepository;
use Eccube\Repository\MailTemplateRepository;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Eccube\Service\MailService as BaseService;

class MailService extends BaseService
{
    /**
     * MailService constructor.
     *
     * @param \Swift_Mailer $mailer
     * @param MailTemplateRepository $mailTemplateRepository
     * @param MailHistoryRepository $mailHistoryRepository
     * @param BaseInfoRepository $baseInfoRepository
     * @param EventDispatcherInterface $eventDispatcher
     * @param \Twig_Environment $twig
     * @param EccubeConfig $eccubeConfig
     */
    public function __construct(
        \Swift_Mailer $mailer,
        MailTemplateRepository $mailTemplateRepository,
        MailHistoryRepository $mailHistoryRepository,
        BaseInfoRepository $baseInfoRepository,
        EventDispatcherInterface $eventDispatcher,
        \Twig_Environment $twig,
        EccubeConfig $eccubeConfig
    ) {
        parent::__construct($mailer, $mailTemplateRepository, $mailHistoryRepository, $baseInfoRepository, $eventDispatcher, $twig, $eccubeConfig);
    }

    /**
     * Send cancel mail.
     *
     * @param $formData お試しキャンセル・解約申請手続き内容
     */
    public function sendCancelMail($formData)
    {
        log_info('お試しキャンセル・解約申請手続き受付メール送信開始');

        $MailTemplate = $this->mailTemplateRepository->find($this->eccubeConfig['eccube_cancel_mail_template_id']);

        $body = $this->twig->render($MailTemplate->getFileName(), [
            'data' => $formData,
            'BaseInfo' => $this->BaseInfo,
        ]);

        // 問い合わせ者にメール送信
        $message = (new \Swift_Message())
            ->setSubject('['.$this->BaseInfo->getShopName().'] '.$MailTemplate->getMailSubject())
            ->setFrom([$this->BaseInfo->getEmail02() => $this->BaseInfo->getShopName()])
            ->setTo([$formData['email']])
            ->setBcc($this->BaseInfo->getEmail02())
            ->setReplyTo($this->BaseInfo->getEmail02())
            ->setReturnPath($this->BaseInfo->getEmail04());

        // HTMLテンプレートが存在する場合
        $htmlFileName = $this->getHtmlTemplate($MailTemplate->getFileName());
        if (!is_null($htmlFileName)) {
            $htmlBody = $this->twig->render($htmlFileName, [
                'data' => $formData,
                'BaseInfo' => $this->BaseInfo,
            ]);

            $message
                ->setContentType('text/plain; charset=UTF-8')
                ->setBody($body, 'text/plain')
                ->addPart($htmlBody, 'text/html');
        } else {
            $message->setBody($body);
        }

        $event = new EventArgs(
            [
                'message' => $message,
                'formData' => $formData,
                'BaseInfo' => $this->BaseInfo,
            ],
            null
        );
        // $this->eventDispatcher->dispatch(EccubeEvents::MAIL_CANCEL, $event);

        $count = $this->mailer->send($message);

        log_info('お試しキャンセル・解約申請手続き受付メール送信完了', ['count' => $count]);

        return $count;
    }
}
