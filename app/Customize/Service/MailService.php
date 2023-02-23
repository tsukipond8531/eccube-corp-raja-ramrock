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
            ->setSubject('[みまもりCUBE] '.$MailTemplate->getMailSubject())
            ->setFrom(['customer@ramrock-eyes.jp' => $this->BaseInfo->getShopName()])
            ->setTo([$formData['email']])
            // ->setReplyTo('customer@ramrock-eyes.jp')
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

        $count = $this->mailer->send($message);

        log_info('お試しキャンセル・解約申請手続き受付メール送信完了', ['count' => $count]);
        
        // 自動返信メール
        $message
            ->setFrom([$formData['email']])
            ->setTo(['customer@ramrock.co.jp' => $this->BaseInfo->getShopName()])
            ->setBcc('customer@ramrock-eyes.jp');
        $this->mailer->send($message);

        return $count;
    }
    
    /**
     * Send proof mail.
     *
     * @param $formData 本人確認書類内容
     */
    public function sendProofMail($formData)
    {
        log_info('本人確認書類受付メール送信開始');

        $MailTemplate = $this->mailTemplateRepository->find($this->eccubeConfig['eccube_proof_mail_template_id']);

        $body = $this->twig->render($MailTemplate->getFileName(), [
            'data' => $formData,
            'BaseInfo' => $this->BaseInfo,
        ]);

        // 問い合わせ者にメール送信
        $message = (new \Swift_Message())
            ->setSubject('[みまもりCUBE] '.$MailTemplate->getMailSubject())
            ->setFrom(['customer@ramrock-eyes.jp' => $this->BaseInfo->getShopName()])
            ->setTo([$formData['email']])
            // ->setReplyTo('customer@ramrock-eyes.jp')
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

        $count = $this->mailer->send($message);

        log_info('本人確認書類受付メール送信完了', ['count' => $count]);
        
        // 自動返信メール
        $message
            ->setFrom([$formData['email']])
            ->setTo(['customer@ramrock.co.jp' => $this->BaseInfo->getShopName()])
            ->setBcc('customer@ramrock-eyes.jp');
        $this->mailer->send($message);

        return $count;
    }
    
    /**
     * Send plan change mail.
     *
     * @param $formData プラン変更内容
     */
    public function sendPlanChangeMail($formData)
    {
        log_info('プラン変更手続き通知メール送信開始');

        $MailTemplate = $this->mailTemplateRepository->find($this->eccubeConfig['eccube_plan_change_mail_template_id']);

        // $orderRepository = $this->em->getRepository(Order::class);
        // dd($orderRepository);

        $body = $this->twig->render($MailTemplate->getFileName(), [
            'data' => $formData,
            'BaseInfo' => $this->BaseInfo,
        ]);

        // 問い合わせ者にメール送信
        $message = (new \Swift_Message())
            ->setSubject('[みまもりCUBE] '.$MailTemplate->getMailSubject())
            ->setFrom(['customer@ramrock-eyes.jp' => $this->BaseInfo->getShopName()])
            ->setTo([$formData['email']])
            // ->setReplyTo('customer@ramrock-eyes.jp')
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

        $count = $this->mailer->send($message);

        log_info('プラン変更手続き通知メール送信完了', ['count' => $count]);
        
        // 自動返信メール
        $message
            ->setFrom([$formData['email']])
            ->setTo(['customer@ramrock-eyes.jp' => $this->BaseInfo->getShopName()])
            ->setBcc('customer@ramrock-eyes.jp');
        $this->mailer->send($message);

        return $count;
    }

    /**
     * Send material mail.
     *
     * @param $formData 一般・介護保険レンタル資料請求内容
     */
    public function sendMaterialMail($formData, $options = null)
    {
        log_info('一般・介護保険レンタル資料請求返信メール開始');

        $MailTemplate = $this->mailTemplateRepository->find($this->eccubeConfig['eccube_material_mail_template_id']);

        $body = $this->twig->render($MailTemplate->getFileName(), [
            'data' => $formData,
            'BaseInfo' => $this->BaseInfo,
            'type' => $options['type'],
        ]);

        // 一般・介護保険レンタル資料請求返信メール
        $message = (new \Swift_Message())
            ->setSubject('[みまもりCUBE] '.$MailTemplate->getMailSubject())
            ->setFrom(['customer@ramrock-eyes.jp' => $this->BaseInfo->getShopName()])
            ->setTo([$formData['email']])
            // ->setReplyTo('customer@ramrock-eyes.jp')
            ->setReturnPath('customer@ramrock-eyes.jp');

        // HTMLテンプレートが存在する場合
        $htmlFileName = $this->getHtmlTemplate($MailTemplate->getFileName());
        if (!is_null($htmlFileName)) {
            $htmlBody = $this->twig->render($htmlFileName, [
                'data' => $formData,
                'BaseInfo' => $this->BaseInfo,
                'type' => $options['type'],
            ]);

            $message
                ->setContentType('text/plain; charset=UTF-8')
                ->setBody($body, 'text/plain')
                ->addPart($htmlBody, 'text/html');
        } else {
            $message->setBody($body);
        }

        $count = $this->mailer->send($message);
        
        // 自動返信メール
        $message
            ->setFrom([$formData['email']])
            ->setTo(['mimamori-cube@ramrock.co.jp' => $this->BaseInfo->getShopName()])
            ->setBcc('mimamori-cube@ramrock.co.jp');
        $this->mailer->send($message);

        log_info('自動返信メール完了', ['count' => $count]);

        return $count;
    }
    
    /**
     * Send opeation cetner mail.
     *
     * @param $formData オペレーションセンターお問合せ内容
     */
    public function sendOperationCenterMail($formData)
    {
        log_info('オペレーションセンターお問合せメール送信開始');

        $MailTemplate = $this->mailTemplateRepository->find($this->eccubeConfig['eccube_operation_center_mail_template_id']);

        $body = $this->twig->render($MailTemplate->getFileName(), [
            'data' => $formData,
            'BaseInfo' => $this->BaseInfo,
        ]);

        // オペレーションセンターお問合せメール送信
        $message = (new \Swift_Message())
            ->setSubject('[みまもりCUBE] '.$MailTemplate->getMailSubject())
            ->setFrom(['customer@ramrock-eyes.jp' => $this->BaseInfo->getShopName()])
            ->setTo([$formData['email']])
            // ->setReplyTo('customer@ramrock-eyes.jp')
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

        $count = $this->mailer->send($message);

        log_info('オペレーションセンターお問合せメール送信完了', ['count' => $count]);
        
        // 自動返信メール
        $message
            ->setFrom([$formData['email']])
            ->setTo(['customer@ramrock.co.jp' => $this->BaseInfo->getShopName()])
            ->setBcc('customer@ramrock-eyes.jp');
        $this->mailer->send($message);

        return $count;
    }

    /**
     * Send material mail.
     *
     * @param $formData 会員情報変更メール
     */
    public function sendCustomerChangeMail($formData)
    {
        log_info('会員情報変更メール送信開始');

        $MailTemplate = $this->mailTemplateRepository->find($this->eccubeConfig['eccube_customer_change_mail_template_id']);

        $body = $this->twig->render($MailTemplate->getFileName(), [
            'data' => $formData,
            'BaseInfo' => $this->BaseInfo,
        ]);

        // 会員情報変更メール送信
        $message = (new \Swift_Message())
            ->setSubject('[みまもりCUBE] '.$MailTemplate->getMailSubject())
            ->setFrom(['customer@ramrock-eyes.jp' => $this->BaseInfo->getShopName()])
            ->setTo([$formData['email']])
            // ->setReplyTo('customer@ramrock-eyes.jp')
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

        $count = $this->mailer->send($message);

        log_info('会員情報変更メール送信完了', ['count' => $count]);
        
        // 自動返信メール
        $message
            ->setFrom([$formData['email']])
            ->setTo(['customer@ramrock.co.jp' => $this->BaseInfo->getShopName()])
            ->setBcc('customer@ramrock-eyes.jp');
        $this->mailer->send($message);

        return $count;
    }

    /**
     * Send order mail.
     *
     * @param \Eccube\Entity\Order $Order 受注情報
     *
     * @return \Swift_Message
     */
    public function sendOrderMail(Order $Order)
    {
        log_info('受注メール送信開始');

        $MailTemplate = $this->mailTemplateRepository->find($this->eccubeConfig['eccube_order_mail_template_id']);

        $body = $this->twig->render($MailTemplate->getFileName(), [
            'Order' => $Order,
        ]);

        $message = (new \Swift_Message())
            ->setSubject('[みまもりCUBE] '.$MailTemplate->getMailSubject())
            ->setFrom([$this->BaseInfo->getEmail01() => $this->BaseInfo->getShopName()])
            ->setTo([$Order->getEmail()])
            ->setReplyTo($this->BaseInfo->getEmail03())
            ->setReturnPath($this->BaseInfo->getEmail04());

        // HTMLテンプレートが存在する場合
        $htmlFileName = $this->getHtmlTemplate($MailTemplate->getFileName());
        if (!is_null($htmlFileName)) {
            $htmlBody = $this->twig->render($htmlFileName, [
                'Order' => $Order,
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
                'Order' => $Order,
                'MailTemplate' => $MailTemplate,
                'BaseInfo' => $this->BaseInfo,
            ],
            null
        );
        $this->eventDispatcher->dispatch(EccubeEvents::MAIL_ORDER, $event);

        $count = $this->mailer->send($message);

        $MailHistory = new MailHistory();
        $MailHistory->setMailSubject($message->getSubject())
            ->setMailBody($message->getBody())
            ->setOrder($Order)
            ->setSendDate(new \DateTime());

        // HTML用メールの設定
        $multipart = $message->getChildren();
        if (count($multipart) > 0) {
            $MailHistory->setMailHtmlBody($multipart[0]->getBody());
        }

        $this->mailHistoryRepository->save($MailHistory);

        $message
            ->setFrom([$Order->getEmail()])
            ->setTo(['customer@ramrock.co.jp' => $this->BaseInfo->getShopName()])
            ->setBcc('customer@ramrock-eyes.jp');

        $this->mailer->send($message);

        log_info('受注メール送信完了', ['count' => $count]);

        return $message;
    }

    /**
     * Send contact mail.
     *
     * @param $formData お問い合わせ内容
     */
    public function sendContactMail($formData)
    {
        log_info('お問い合わせ受付メール送信開始');

        $MailTemplate = $this->mailTemplateRepository->find($this->eccubeConfig['eccube_contact_mail_template_id']);

        $body = $this->twig->render($MailTemplate->getFileName(), [
            'data' => $formData,
            'BaseInfo' => $this->BaseInfo,
        ]);

        // 問い合わせ者にメール送信
        $message = (new \Swift_Message())
            ->setSubject('[みまもりCUBE] '.$MailTemplate->getMailSubject())
            ->setFrom(['customer@ramrock-eyes.jp' => $this->BaseInfo->getShopName()])
            ->setTo([$formData['email']])
            // ->setReplyTo('customer@ramrock-eyes.jp')
            ->setReturnPath('customer@ramrock-eyes.jp');

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
        $this->eventDispatcher->dispatch(EccubeEvents::MAIL_CONTACT, $event);

        $count = $this->mailer->send($message);
        
        // 自動返信メール
        $message
            ->setFrom([$formData['email']])
            ->setTo(['mimamori-cube@ramrock.co.jp' => $this->BaseInfo->getShopName()])
            ->setBcc('mimamori-cube@ramrock.co.jp');
        $this->mailer->send($message);

        log_info('お問い合わせ受付メール送信完了', ['count' => $count]);

        return $count;
    }

    /**
     * Send customer confirm mail.
     *
     * @param $Customer 会員情報
     * @param string $activateUrl アクティベート用url
     */
    public function sendCustomerConfirmMail(Customer $Customer, $activateUrl)
    {
        log_info('仮会員登録メール送信開始');

        $MailTemplate = $this->mailTemplateRepository->find($this->eccubeConfig['eccube_entry_confirm_mail_template_id']);

        $body = $this->twig->render($MailTemplate->getFileName(), [
            'Customer' => $Customer,
            'BaseInfo' => $this->BaseInfo,
            'activateUrl' => $activateUrl,
        ]);

        $message = (new \Swift_Message())
            ->setSubject('['.$this->BaseInfo->getShopName().'] '.$MailTemplate->getMailSubject())
            ->setFrom(['customer@ramrock-eyes.jp' => $this->BaseInfo->getShopName()])
            ->setTo([$Customer->getEmail()])
            ->setReplyTo($this->BaseInfo->getEmail03())
            ->setReturnPath($this->BaseInfo->getEmail04());

        // HTMLテンプレートが存在する場合
        $htmlFileName = $this->getHtmlTemplate($MailTemplate->getFileName());
        if (!is_null($htmlFileName)) {
            $htmlBody = $this->twig->render($htmlFileName, [
                'Customer' => $Customer,
                'BaseInfo' => $this->BaseInfo,
                'activateUrl' => $activateUrl,
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
                'Customer' => $Customer,
                'BaseInfo' => $this->BaseInfo,
                'activateUrl' => $activateUrl,
            ],
            null
        );
        $this->eventDispatcher->dispatch(EccubeEvents::MAIL_CUSTOMER_CONFIRM, $event);

        $count = $this->mailer->send($message, $failures);

        log_info('仮会員登録メール送信完了', ['count' => $count]);
        
        // 自動返信メール
        $message
            ->setFrom([$Customer->getEmail()])
            ->setTo(['customer@ramrock.co.jp' => $this->BaseInfo->getShopName()])
            ->setBcc('customer@ramrock-eyes.jp');
        $this->mailer->send($message);

        return $count;
    }

    /**
     * Send customer complete mail.
     *
     * @param $Customer 会員情報
     */
    public function sendCustomerCompleteMail(Customer $Customer)
    {
        log_info('会員登録完了メール送信開始');

        $MailTemplate = $this->mailTemplateRepository->find($this->eccubeConfig['eccube_entry_complete_mail_template_id']);

        $body = $this->twig->render($MailTemplate->getFileName(), [
            'Customer' => $Customer,
            'BaseInfo' => $this->BaseInfo,
        ]);

        $message = (new \Swift_Message())
            ->setSubject('[みまもりCUBE] '.$MailTemplate->getMailSubject())
            ->setFrom(['customer@ramrock-eyes.jp' => $this->BaseInfo->getShopName()])
            ->setTo([$Customer->getEmail()])
            ->setReplyTo($this->BaseInfo->getEmail02())
            ->setReturnPath($this->BaseInfo->getEmail04());

        // HTMLテンプレートが存在する場合
        $htmlFileName = $this->getHtmlTemplate($MailTemplate->getFileName());
        if (!is_null($htmlFileName)) {
            $htmlBody = $this->twig->render($htmlFileName, [
                'Customer' => $Customer,
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
                'Customer' => $Customer,
                'BaseInfo' => $this->BaseInfo,
            ],
            null
        );
        $this->eventDispatcher->dispatch(EccubeEvents::MAIL_CUSTOMER_COMPLETE, $event);

        $count = $this->mailer->send($message);

        log_info('会員登録完了メール送信完了', ['count' => $count]);
        
        // 自動返信メール
        $message
            ->setFrom([$Customer->getEmail()])
            ->setTo(['customer@ramrock.co.jp' => $this->BaseInfo->getShopName()])
            ->setBcc('customer@ramrock-eyes.jp');
        $this->mailer->send($message);

        return $count;
    }

    /**
     * Send contact light mail.
     *
     * @param $formData お問い合わせ内容
     */
    public function sendContactLightMail($formData)
    {
        log_info('お問い合わせ受付メール送信開始');

        $MailTemplate = $this->mailTemplateRepository->find($this->eccubeConfig['eccube_contact_light_mail_template_id']);

        $body = $this->twig->render($MailTemplate->getFileName(), [
            'data' => $formData,
            'BaseInfo' => $this->BaseInfo,
        ]);

        // 問い合わせ者にメール送信
        $message = (new \Swift_Message())
            ->setSubject($MailTemplate->getMailSubject())
            ->setFrom(['customer@ramrock-eyes.jp' => $this->BaseInfo->getShopName()])
            ->setTo([$formData['email']])
            // ->setReplyTo('customer@ramrock-eyes.jp')
            ->setReturnPath('customer@ramrock-eyes.jp');

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

        $count = $this->mailer->send($message);
        
        // 自動返信メール
        $message
            ->setFrom([$formData['email']])
            ->setTo(['mimamori-cube@ramrock.co.jp' => $this->BaseInfo->getShopName()])
            ->setBcc('customer@ramrock-eyes.jp');
        $this->mailer->send($message);

        log_info('お問い合わせ受付メール送信完了', ['count' => $count]);

        return $count;
    }

    /**
     * Send contact plus mail.
     *
     * @param $formData お問い合わせ内容
     */
    public function sendContactPlusMail($formData)
    {
        log_info('お問い合わせ受付メール送信開始');

        $MailTemplate = $this->mailTemplateRepository->find($this->eccubeConfig['eccube_contact_plus_mail_template_id']);

        $body = $this->twig->render($MailTemplate->getFileName(), [
            'data' => $formData,
            'BaseInfo' => $this->BaseInfo,
        ]);

        // 問い合わせ者にメール送信
        $message = (new \Swift_Message())
            ->setSubject($MailTemplate->getMailSubject())
            ->setFrom(['customer@ramrock-eyes.jp' => $this->BaseInfo->getShopName()])
            ->setTo([$formData['email']])
            // ->setReplyTo('customer@ramrock-eyes.jp')
            ->setReturnPath('customer@ramrock-eyes.jp');

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

        $count = $this->mailer->send($message);
        
        // 自動返信メール
        $message
            ->setFrom([$formData['email']])
            ->setTo(['mimamori-cube@ramrock.co.jp' => $this->BaseInfo->getShopName()])
            ->setBcc('customer@ramrock-eyes.jp');
        $this->mailer->send($message);

        log_info('お問い合わせ受付メール送信完了', ['count' => $count]);

        return $count;
    }
    
    /**
     * Send enquete mail.
     *
     * @param $formData アンケート内容
     */
    public function sendEnqueteMail($Customer, $formData)
    {
        log_info('アンケート受付メール送信開始');

        $MailTemplate = $this->mailTemplateRepository->find($this->eccubeConfig['eccube_enquete_mail_template_id']);

        $body = $this->twig->render($MailTemplate->getFileName(), [
            'data' => $formData,
            'BaseInfo' => $this->BaseInfo,
        ]);

        // アンケート者にメール送信
        $message = (new \Swift_Message())
            ->setSubject('[みまもりCUBE] '.$MailTemplate->getMailSubject())
            ->setFrom(['customer@ramrock-eyes.jp' => $this->BaseInfo->getShopName()])
            ->setTo([$Customer->getEmail()])
            // ->setReplyTo('customer@ramrock-eyes.jp')
            ->setReturnPath('customer@ramrock-eyes.jp');

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

        $count = $this->mailer->send($message);
        
        // 自動返信メール
        $message
            ->setFrom([$Customer->getEmail()])
            ->setTo(['customer@ramrock.co.jp' => $this->BaseInfo->getShopName()])
            ->setBcc('customer@ramrock-eyes.jp');
        $this->mailer->send($message);

        log_info('アンケート受付メール送信完了', ['count' => $count]);

        return $count;
    }

    /**
     * 発送通知メールを送信する.
     * 発送通知メールは受注ごとに送られる
     *
     * @param Shipping $Shipping
     *
     * @throws \Twig_Error
     */
    public function sendShippingNotifyMail(Shipping $Shipping)
    {
        log_info('出荷通知メール送信処理開始', ['id' => $Shipping->getId()]);

        $MailTemplate = $this->mailTemplateRepository->find($this->eccubeConfig['eccube_shipping_notify_mail_template_id']);

        /** @var Order $Order */
        $Order = $Shipping->getOrder();
        $body = $this->getShippingNotifyMailBody($Shipping, $Order, $MailTemplate->getFileName());

        $message = (new \Swift_Message())
            ->setSubject('['.$this->BaseInfo->getShopName().'] '.$MailTemplate->getMailSubject())
            ->setFrom([$this->BaseInfo->getEmail01() => $this->BaseInfo->getShopName()])
            ->setTo($Order->getEmail())
            ->setBcc($this->BaseInfo->getEmail01())
            ->setReplyTo($this->BaseInfo->getEmail03())
            ->setReturnPath($this->BaseInfo->getEmail04());

        // HTMLテンプレートが存在する場合
        $htmlFileName = $this->getHtmlTemplate($MailTemplate->getFileName());
        if (!is_null($htmlFileName)) {
            $htmlBody = $this->getShippingNotifyMailBody($Shipping, $Order, $htmlFileName, true);

            $message
                ->setContentType('text/plain; charset=UTF-8')
                ->setBody($body, 'text/plain')
                ->addPart($htmlBody, 'text/html');
        } else {
            $message->setBody($body);
        }

        $this->mailer->send($message);

        $MailHistory = new MailHistory();
        $MailHistory->setMailSubject($message->getSubject())
                ->setMailBody($message->getBody())
                ->setOrder($Order)
                ->setSendDate(new \DateTime());

        // HTML用メールの設定
        $multipart = $message->getChildren();
        if (count($multipart) > 0) {
            $MailHistory->setMailHtmlBody($multipart[0]->getBody());
        }

        $this->mailHistoryRepository->save($MailHistory);

        log_info('出荷通知メール送信処理完了', ['id' => $Shipping->getId()]);

        // 自動返信メール
        $message
            ->setFrom([$Order->getEmail()])
            ->setTo(['customer@ramrock.co.jp' => $this->BaseInfo->getShopName()])
            ->setBcc('customer@ramrock-eyes.jp');
        $this->mailer->send($message);
    }
}
