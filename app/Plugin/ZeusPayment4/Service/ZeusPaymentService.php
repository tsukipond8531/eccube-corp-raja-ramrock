<?php

namespace Plugin\ZeusPayment4\Service;

use Doctrine\ORM\NoResultException;
use GuzzleHttp\Client;
use Plugin\ZeusPayment4\Entity\Config;
use Plugin\ZeusPayment4\Entity\ZeusOrder;
use Symfony\Component\HttpFoundation\Response;
use Eccube\Common\EccubeConfig;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Eccube\Repository\Master\OrderStatusRepository;
use Eccube\Entity\Master\OrderStatus;
use GuzzleHttp\Exception\BadResponseException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Routing\RouterInterface;
use Eccube\Entity\Layout;
use Eccube\Entity\Page;
use Eccube\Entity\PageLayout;
use Eccube\Entity\Order;
use Eccube\Service\PurchaseFlow\PurchaseFlow;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Eccube\Service\MailService;
use Eccube\Service\OrderStateMachine;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/*
 * 決済ロジック処理
 */
class ZeusPaymentService
{
    private $entityManager;
    private $eccubeConfig;
    private $session;
    private $orderStatusRepository;
    private $tokenStorage;
    private $mailService;
    private $authorizationChecker;
    private $orderStateMachine;

    public function __construct(
        EntityManagerInterface $entityManager,
        EccubeConfig $eccubeConfig,
        SessionInterface $session,
        OrderStatusRepository $orderStatusRepository,
        TokenStorageInterface $tokenStorage,
        AuthorizationCheckerInterface $authorizationChecker,
        MailService $mailService,
        RouterInterface $route,
        PurchaseFlow $shoppingPurchaseFlow,
        OrderStateMachine $orderStateMachine
    ) {
        $this->entityManager = $entityManager;
        $this->eccubeConfig = $eccubeConfig;
        $this->session = $session;
        $this->orderStatusRepository = $orderStatusRepository;
        $this->tokenStorage = $tokenStorage;
        $this->authorizationChecker = $authorizationChecker;
        $this->mailService = $mailService;
        $this->route = $route;
        $this->purchaseFlow = $shoppingPurchaseFlow;
        $this->orderStateMachine = $orderStateMachine;
    }

    /*
     * 設定情報をＤＢに保存します。
     */
    public function saveConfig($type, $config)
    {
        //ECCUBE3の旧実装
        // 削除済のpaymentも拾う
        /*$softDeleteFilter = $this->entityManager->getFilters()->getFilter('soft_delete');
        $softDeleteFilter->setExcludes(array(
            'Eccube\Entity\Payment'
        ));*/
        
        // dtb_paymentにデータを登録
        $payment = $config->getPayment($type);
        $methodClass = "Plugin\\ZeusPayment4\\Service\Method\\" . ucfirst(strtolower($type)) . "Payment";

        $paymentRepository = $this->entityManager->getRepository('\Eccube\Entity\Payment');
        if (is_null($payment)) {
            $payment = $paymentRepository->findOneBy(['method_class'=>$methodClass]);
            if ($payment) {
                $config->setPayment($type, $payment);
            }
        }
        
        // 登録されていない場合のみ更新する。
        if (is_null($payment)) {
            $lastPayment = $paymentRepository->findOneBy([], ['sort_no' => 'DESC']);
            $sortNo = $lastPayment ? $lastPayment->getSortNo() + 1 : 1;

            $payment = new \Eccube\Entity\Payment();
            $payment->setMethod($this->eccubeConfig['zeus_payment_method_' . strtolower($type)]);
            $payment->setMethodClass($methodClass);
            $payment->setRuleMin(0);
            $payment->setCharge(0);
            $payment->setSortNo($sortNo);
            $payment->setVisible(true);

            $this->entityManager->persist($payment);
            $this->entityManager->flush();

            $deliveries = $this->entityManager->getRepository('\Eccube\Entity\Delivery')->findAll();
            foreach ($deliveries as $delivery) {
                $paymentOption = new \Eccube\Entity\PaymentOption();
                $paymentOption->setPaymentId($payment->getId())
                    ->setPayment($payment)
                    ->setDeliveryId($delivery->getId())
                    ->setDelivery($delivery);
                $this->entityManager->persist($paymentOption);
                $payment->addPaymentOption($paymentOption);
            }
            $this->entityManager->persist($payment);
            $this->entityManager->flush();
            $config->setPayment($type, $payment);
        } else {
            if (!$payment->isVisible()) {
                $payment->setVisible(true);
                $this->entityManager->persist($payment);
                $this->entityManager->flush();
            }
        }
        
        // zeus_payment_configにデータを登録
        $key = $config->getKey($type);
        if (empty($key)) {
            $config->setKey($type, $this->getUniqKey());
        }


        $this->entityManager->persist($config);
        $this->entityManager->flush();

        if($type=='cvs'){
            $this->savePageLayout('ZEUSコンビニ決済','zeus_cvs_payment','@ZeusPayment4/cvs');
        }
        if($type=="ebank"){
            $this->savePageLayout('ZEUS銀行振込決済','zeus_ebank_payment','@ZeusPayment4/ebank');
        }
        if($type=="eaccount"){
            $this->savePageLayout('ZEUS口座振替決済','zeus_eaccount_payment','@ZeusPayment4/eaccount');
        }

    }

    public function savePageLayout($name,$url,$filename)
    {
        $pageRepository = $this->entityManager->getRepository('\Eccube\Entity\Page');
        $page = $pageRepository->findOneBy(['url' => $url]);
        if($page){
            return;
        }

        $page = new Page();
        $page->setEditType(Page::EDIT_TYPE_DEFAULT);
        $page->setName($name);
        $page->setUrl($url);
        $page->setFileName($filename);

        // DB登録
        $this->entityManager->persist($page);
        $this->entityManager->flush($page);
        $layout = $this->entityManager->find(Layout::class, Layout::DEFAULT_LAYOUT_UNDERLAYER_PAGE);
        $PageLayout = new PageLayout();
        $PageLayout->setPage($page)
            ->setPageId($page->getId())
            ->setLayout($layout)
            ->setLayoutId($layout->getId())
            ->setSortNo(0);
        $this->entityManager->persist($PageLayout);
        $this->entityManager->flush($PageLayout);
    }

    /*
     * カード番号にマスクをかける
     */
    public function getMaskedCard($cardNo)
    {
        $len = strlen($cardNo);
        $mask = '';
        for ($i = 0; $i < ($len - 6); $i ++) {
            $mask .= '*';
        }
        return (strlen($cardNo) > 4) ? substr($cardNo, 0, 2) . $mask . substr($cardNo, - 4) : $cardNo;
    }

    /*
     * 処理中かの確認
     */
    public function isProcessing($order)
    {
        return ($order == $this->session->get('ZeusOrdering'));
    }

    /*
         * クレカＡＰＩ処理
         */
    public function sendCreditData($order, $config)
    {
        // 3D認証は以下の処理内でリダイレクト実施。
        $this->session->set('ZeusOrdering', $order);

        try {
            if (!$this->paymentDataSend($order, $config)) {
                return false;
            }
        } catch (\Throwable $e) {
            $this->session->remove('ZeusOrdering');
            throw $e;
        }
        $this->session->remove('ZeusOrdering');
        return true;
    }

    /*
     * クレカデータ送信
     */
    public function paymentDataSend($order, $config)
    {
        $cvvPostData = '';

        if ($order->getZeusCreditPaymentQuick() && $this->isQuickChargeOK($order, $config->getCreditPayment())) {
            if ((strlen($order->getZeusCreditPaymentCvv()) > 0) && ($config->getCvvflg() > 0) && ($config->getCvvflg() != Config::$cvv_first_use)) {
                $cvvPostData = '<cvv>' . $order->getZeusCreditPaymentCvv() . '</cvv>';
            } else {
                $cvvPostData = '';
            }
        } else {
            if (($config->getCvvflg() > 0) && (strlen($order->getZeusCreditPaymentCvv()) > 0)) {
                $cvvPostData = '<cvv>' . $order->getZeusCreditPaymentCvv() . '</cvv>';
            } else {
                $cvvPostData = '';
            }
        }
        $enrolXid = '';
        $enrolAcsUrl = '';
        $enrolPaReq = '';

        if (!$order->getZeusCreditPaymentToken()) {
            log_error('ZeusTokenが送信されていません');
            return false;
        }

        /// 3d secure
        if ($config->getSecure3dflg()) {
            // Enrol Action Start
            $toApiPostData = $this->getZeusPostData3dToEnrolReq($order, $config, $cvvPostData);
            $response = '';
            try {
                $response = $this->secureSendAction($this->eccubeConfig['zeus_secure_3d_link_url'], $toApiPostData);
                //$toApiPostData = $this->setMaskCardNumber($toApiPostData); // カード番号の一部をマスク
                //log_notice('ゼウス送信内容(3D_EnrolReq)：' . $toApiPostData);
                log_notice('ゼウス応答結果(3D_EnrolReq)：' . $response);
            } catch (BadResponseException $e) {
                log_error('ゼウス通信失敗：' . $e->getMessage());
                return false;
            }
            if (strstr($response, '<status>invalid</status>') !== false) {
                return false;
            }
            if (strstr($response, '<status>success</status>') !== false) {
            } elseif (strstr($response, '<status>outside</status>') !== false) {
                $enrolXid = $this->getEnrolXid($response);
                $toApiPostData = $this->getZeusPostData3dToPayReq($enrolXid);
                try {
                    $response = $this->secureSendAction($this->eccubeConfig['zeus_secure_3d_link_url'], $toApiPostData);
                    //$toApiPostData = $this->setMaskCardNumber($toApiPostData); // カード番号の一部をマスク
                    //log_notice('ゼウス送信内容：' . $toApiPostData);
                    log_notice('ゼウス応答結果：' . $response);
                } catch (BadResponseException $e) {
                    log_error('ゼウス通信失敗：' . $e->getMessage());
                    return false;
                }

                if (strstr($response, '<status>success</status>') !== false) {
                    return $this->createZeusOrderCredit($order, $toApiPostData, $response, $config);
                } else {
                    return false;
                }
            } else {
                return false;
            }
            $enrolXid = $this->getEnrolXid($response);
            $enrolAcsUrl = $this->getEnrolAcsUrl($response);
            $enrolPaReq = $this->getEnrolPaReq($response);
            $iframeUrl = $this->getEnrolIframeUrl($response);
            $threeDSMehtod= $this->getEnrolThreeDSMethod($response);

            // Enrol Action End
            // PaReq Action Start(PaReqは本人認証URLをユーザページよりリダイレクトさせ本人認証ページを表示させます。)
            $termUrl = $this->route->generate("zeus_payment_return_index", array(
                'mode' => 'pares',
                'ver3ds2' => ($threeDSMehtod == "2")?"1":""
            ), UrlGeneratorInterface::ABSOLUTE_URL);
            $paReqRequest = array(
                'MD' => $enrolXid,
                'PaReq' => $enrolPaReq,
                'TermUrl' => $termUrl,
                'threeDSMehtod' => $threeDSMehtod,
                'iframeUrl' => $iframeUrl,
            );
            log_notice('3Dセキュア認証ページへ転送 3ds:' . $threeDSMehtod);
            if ($threeDSMehtod == "2")
                $this->acsPostRedirect3ds2($enrolAcsUrl, $paReqRequest);
            else
                $this->acsPostRedirect($enrolAcsUrl, $paReqRequest);
            return false;
        } else { // not 3d
            log_notice('NOT 3Dセキュア');
            $toApiPostData = $this->getZeusPostData($order, $config, $cvvPostData);
        }

        $response = '';
        try {
            $response = $this->secureSendAction($this->eccubeConfig['zeus_secure_link_url'], $toApiPostData);
            log_notice('ゼウス応答結果：' . $response);
        } catch (BadResponseException $e) {
            log_error('ゼウス通信失敗：' . $e->getMessage());
            return false;
        }

        if (strstr($response, '<status>success</status>') !== false) {
            return $this->createZeusOrderCredit($order, $toApiPostData, $response, $config);
        } else {
            return false;
        }
    }

    /*
     * ゼウス注文番号を取得
     */
    public function getZeusOrderId($zeusResponse)
    {
        $pattern = "/<order_number>(.*)<\/order_number>/";
        preg_match($pattern, $zeusResponse, $matches);
        return (count($matches) > 1) ? $matches[1] : "";
    }

    public function getZeusPostData($order, $config, $cvvPostData)
    {
        $customer = $order->getCustomer();
        $sendid = "";
        if (is_null($customer)) {
            $sendid = "ORD" . $order->getId();
        } else {
            $sendid = $customer->getId();
        }
        $tokenKey = "";
        if ($order->getZeusCreditPaymentToken()) {
            $tokenKey = '<token_key>' . $order->getZeusCreditPaymentToken() . '</token_key>';
        }
        if ($order->getZeusCreditPaymentQuick() && $this->isQuickChargeOK($order, $config->getCreditPayment())) {
            return "<?xml version=\"1.0\" encoding=\"utf-8\"?>" . "  <request service=\"secure_link\" action=\"payment\">" . "    <authentication>" . "      <clientip>" . $config->getClientip() . "</clientip>" . "      <key>" . $config->getClientauthkey() . "</key>" . "    </authentication>" . $tokenKey . "    <card>" . "      <history action=\"send_email\">" . "        <key>telno</key>" . "        <key>sendid</key>" . "      </history>" . $cvvPostData . "    </card>" . "    <payment>" . "      <amount>" . $order->getPaymentTotal() . "</amount>" . "      <count>" . $order->getZeusCreditPaymentMethod() . "</count>" . "    </payment>" . "    <user>" . "      <email>" . $order->getEmail() . "</email>" . "      <telno validation=\"permissive\">" . $order->getPhoneNumber() . "</telno>" . "    </user>" . "    <uniq_key>" . "      <sendid>" . $sendid . "</sendid>" . "      <sendpoint>" . $order->getId() . "</sendpoint>" . "    </uniq_key>" . "</request>";
        } else {
            return "<?xml version=\"1.0\" encoding=\"utf-8\"?>" . "  <request service=\"secure_link\" action=\"payment\">" . "    <authentication>" . "      <clientip>" . $config->getClientip() . "</clientip>" . "      <key>" . $config->getClientauthkey() . "</key>" . "    </authentication>" . $tokenKey . "    <payment>" . "      <amount>" . $order->getPaymentTotal() . "</amount>" . "      <count>" . $order->getZeusCreditPaymentMethod() . "</count>" . "    </payment>" . "    <user>" . "      <email>" . $order->getEmail() . "</email>" . "      <telno validation=\"permissive\">" . $order->getPhoneNumber() . "</telno>" . "    </user>" . "    <uniq_key>" . "      <sendid>" . $sendid . "</sendid>" . "      <sendpoint>" . $order->getId() . "</sendpoint>" . "    </uniq_key>" . "</request>";
        }
    }

    /*
     * ＵＲＬ送信
     */
    public function secureSendAction($url, $postData)
    {
        $client = new Client();

        $error = '';
        try {
            $httpResponse = $client->post($url, [
                'headers'=>['Content-Type' => 'application/xml'],
                'body'=>$postData]);
            if ($httpResponse->getStatusCode() != 200) {
                $error = $httpResponse->getReasonPhrase();
            } else {
                $response = $httpResponse->getBody(true);
                return $response;
            }
        } catch (BadResponseException $e) {
            $error = $e->getMessage();
        }

        throw new BadResponseException($error, null, $httpResponse);
    }

    public function secureSendRequest($url, $params, $method = "POST")
    {
        $client = new Client();
        
        $error = '';
        try {
            
            $httpResponse = $client->request($method, $url, [
                'form_params' => $params
            ]);
            if ($httpResponse->getStatusCode() != 200) {
                $error = $httpResponse->getReasonPhrase();
            } else {
                return $httpResponse->getBody(true);
            }
        } catch (BadResponseException $e) {
            $error = $e->getMessage();
        }
        
        throw new BadResponseException($error, null, $httpResponse);
    }
    
    /*
    * クレカ決済のゼウス注文情報を生成
    */
    public function createZeusOrderCredit($order, $toApiPostData, $response, $config)
    {
        $order->setZeusOrderId($this->getZeusOrderId($response));
        $order->setZeusRequestData($toApiPostData);
        $order->setZeusResponseData($response);
        //3d secure戻り処理する際は purchaseflow経由してなくて注文日がセットされない
        if (null === $order->getOrderDate()) {
            $order->setOrderDate(new \DateTime());
        }

        $prefix = $this->getZeusResultData($response, "/<prefix>(.*)<\/prefix>/");
        $suffix = $this->getZeusResultData($response, "/<suffix>(.*)<\/suffix>/");
        if (strlen($order->getNote()) > 0) {
            $str = $order->getNote() . "\r\n";
        } else {
            $str = "";
        }
        $order->setNote($str . '[' . date("Y-m-d H:i:s") . '] 決済処理（' . 
            $config->getSaleTypeString(). '）を行いました。ZEUS_ORDER_ID:[' . $this->getZeusOrderId($response) . ']'); //prefix:[' . $prefix . '] suffix:[' . $suffix . ']'
        $order->setZeusSaleType($config->getSaleType());
        
        return $order;
    }


    /*
     * quick charge 機能利用できるかの確認
     */
    public function isQuickChargeOK($CurOrder, $CreditPayment)
    {
        if (! $CurOrder) {
            return false;
        }
        // 未ログインの場合
        if (! $this->authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY')) {
            return false;
        }
        $customer = $this->tokenStorage->getToken()->getUser();

        $orderRepo = $this->entityManager->getRepository('\Eccube\Entity\Order');

        try {
            $preOrder = $orderRepo->createQueryBuilder('o')
                ->setMaxResults(1)
                ->where('o.Payment = :Payment')
                ->andWhere("o.OrderStatus not in (:OrderStatus)")
                ->andWhere("o.id != :order_id")
                ->andWhere('o.Customer = :Customer')
                ->setParameter('order_id', $CurOrder->getId())
                ->setParameter('Customer', $customer)
                ->setParameter('OrderStatus', [OrderStatus::PENDING,OrderStatus::PROCESSING])
                ->setParameter('Payment', $CreditPayment)
                ->addOrderBy('o.id', 'DESC')
                ->getQuery()
                ->getSingleResult();
        } catch (NoResultException $e) {
            // 結果がない場合はFalseを返す.
            return false;
        }
        
        if ($preOrder) {
            if ($preOrder->getZeusCreditPaymentNotreg() == 1) { //if the last order is not reg.
                return false;
            }
            $telno = $preOrder->getPhoneNumber();
            $curTelno = $customer->getPhoneNumber();
            if ($telno == $curTelno) {
                return true;
            }
        }
        return false;
    }
    
    
    /*
     * クレカ用clientipの確認
     */
    public function verifyConfig($clientip, $clientauthkey, $config)
    {
        $toApiPostData = $this->getZeusPostDataInformation($clientip, $clientauthkey);
        $response = '';
        try {
            $response = $this->secureSendAction($this->eccubeConfig['zeus_secure_3d_information_link_url'], $toApiPostData);
            //log_notice('応答：' . $response);
        } catch (BadResponseException $e) {
            log_error('ゼウス通信失敗：' . $e->getMessage());
            return 'NETERR';
        }
        
        if (strstr($response, '<status>success</status>') === false) {
            $errorCode = $this->getZeusResultData($response, "/<code>(.*)<\/code>/");
            return $errorCode;
        } else {
            $config->setDetailname($this->getZeusResultData($response, "/<name>(.*)<\/name>/"));
            $config->setSecure3dflg($this->getZeusResultData($response, "/<threed>(.*)<\/threed>/") === 'on' ? 1 : 0);
            $config->setSaletype($this->getZeusResultData($response, "/<auth>(.*)<\/auth>/") === 'on' ? 1 : 0);
            
            $getCvvPatternFirst = $this->getZeusResultData($response, "/<first>(.*)<\/first>/");
            $getCvvPatternQuick = $this->getZeusResultData($response, "/<quick>(.*)<\/quick>/");
            if ($getCvvPatternFirst === 'on' && $getCvvPatternQuick === 'on') {
                $config->setCvvflg(Config::$cvv_on);
            } elseif ($getCvvPatternFirst === 'on' && $getCvvPatternQuick === 'off') {
                $config->setCvvflg(Config::$cvv_first_use);
            } elseif ($getCvvPatternFirst === 'on' && $getCvvPatternQuick === 'optional') {
                $config->setCvvflg(Config::$cvv_first_on_quick_opt);
            } elseif ($getCvvPatternFirst === 'optional' && $getCvvPatternQuick === 'optional') {
                $config->setCvvflg(Config::$cvv_first_opt_quick_opt);
            } else {
                $config->setCvvflg(0);
            }
            $config->setQuickchargeflg(1);
            
            return '';
        }
    }
    

    /*
     * 継続会員：カード番号下4桁取得
     */
    public function fetchMaskedCard($order, $config)
    {
        $customer = $order->getCustomer();
        $sendid = "";
        if (is_null($customer)) {
            $sendid = "ORD" . $order->getId();
        } else {
            $sendid = $customer->getId();
        }
        
        $toApiPostData = "<?xml version=\"1.0\" encoding=\"utf-8\"?>" . "  <request service=\"keizoku\" action=\"inquiry\">" .
        "    <authentication>" . "      <clientip>" . $config->getClientip() . "</clientip>" . "      <key>" . $config->getClientauthkey() . "</key>" .
        "    </authentication>" . "<search_key><sendid>" . $sendid . "</sendid>" .
        "    <telno>" . $order->getPhoneNumber() . "</telno>" .
        "    </search_key></request>";

        try {
            $response = $this->secureSendAction($this->eccubeConfig['zeus_fetch_customer_info_url'], $toApiPostData);
            log_notice('応答：' . $response);
            log_notice('ゼウス応答結果：' . $this->getStatus($response));
        } catch (BadResponseException $e) {
            log_error('ゼウス通信失敗：' . $e->getMessage());
            return false;
        }
        
        if (strstr($response, '<status>success</status>') !== false) {
            $masked = $this->getMaskCardNumber($response);
            if (!empty($masked)) {
                return preg_replace("/^[0-9]{4}\*/", "*****", $masked);
            }
        }
        return "";
    }

    /*
     * クレカデータ送信（３Ｄ認証）
     */
    public function paymentDataSendAuthorize($request, $order, $config)
    {
        $paResMD = $request->get('MD');
        $paResPARES = $request->get('PaRes');
        
        if (empty($paResMD)) {
            $json = file_get_contents('php://input');
            // Converts json data into a PHP object
            $data = json_decode($json, true);
            
            $paResMD = $data['MD'];
            $paResPARES = $data['PaRes'];
        }

        // AuthReq
        $toApiPostData = $this->getZeusPostData3dToAuthReq($paResMD, $paResPARES);
        $response = '';
        try {
            $response = $this->secureSendAction($this->eccubeConfig['zeus_secure_3d_link_url'], $toApiPostData);
            //log_notice('ゼウス送信内容(3D_AuthReq)：' . $toApiPostData);
            log_notice('ゼウス応答結果(3D_AuthReq)：' . $response);
        } catch (BadResponseException $e) {
            log_error('ゼウス通信失敗：' . $e->getMessage());
            return false;
        }
        if (strstr($response, '<status>invalid</status>') !== false) {
            return false;
        }
        if (strstr($response, '<status>success</status>') === false) {
            return false;
        }
        
        // PayReq
        $toApiPostData = $this->getZeusPostData3dToPayReq($paResMD);
        $response = '';
        try {
            $response = $this->secureSendAction($this->eccubeConfig['zeus_secure_3d_link_url'], $toApiPostData);
            //log_notice('ゼウス送信内容：' . $toApiPostData);
            log_notice('ゼウス応答結果：' . $response);
        } catch (BadResponseException $e) {
            log_error('ゼウス通信失敗：' . $e->getMessage());
            return false;
        }
        
        if (strstr($response, '<status>success</status>') !== false) {
            if (!$this->createZeusOrderCredit($order, $toApiPostData, $response, $config)) {
                return false;
            } else {
                
                $OrderStatus = $this->orderStatusRepository->find($config->getOrderStatusForSaleType());
                $order->setOrderStatus($OrderStatus);
                $order->setPaymentDate(new \DateTime());
            }
        } else {
            return false;
        }
        return true;
    }

    /*
     * 取消
     */
    public function paymentCancel($order, $config)
    {
        $toApiPostData = array(
            'clientip' => $config->getClientip(),
            'return' => 'yes',
            'ordd' => $order->getZeusOrderId(),
        );
        
        try {
            $response = $this->secureSendRequest($this->eccubeConfig['zeus_secure_link_batch_url'], $toApiPostData, "POST");
            log_notice('ゼウス応答結果(cancel)：' . $response);
            if (strstr($response, 'SuccessOK')) {
                return true;
            } else {
                log_error('ゼウス取消に失敗(' . $order->getZeusOrderId() .')：' . $response);
                return false;
            }
            
        } catch (BadResponseException $e) {
            log_error('ゼウス通信失敗：' . $e->getMessage());
            return false;
        }
    }
    
    /*
     * 取消
     */
    public function paymentSetSale($order, $config, $king = 0, $date = null, $authtype = 'sale')
    {
        if (($king != 0) && (($king < $order->getPaymentTotal() - 5000) || ($king > $order->getPaymentTotal() + 5000))) {
            return "実売上の金額は仮売上時の金額より±5000円以内のみ変更可能。";
        }
        
        if ($king == 0) {
            $king = intval($order->getPaymentTotal());
        }
        
        if ($date == null) {
            $date = date('Ymd');
        }
            
        $toApiPostData = array(
            'clientip' => $config->getClientip(),
            'king' => $king,
            'date' => $date,
            'ordd' => $order->getZeusOrderId(),
            'autype' => $authtype
        );
        
        try {
            $response = $this->secureSendRequest($this->eccubeConfig['zeus_secure_link_batch_url'], $toApiPostData, "POST");
            log_notice('ゼウス応答結果(' . $authtype . ')：' . $response);
            if (strstr($response, 'Success_order')) {
                return true;
            } else {
                log_error('ゼウス実売上に失敗(' . $order->getZeusOrderId() .')：' . $response);
                return false;
            }
            
        } catch (BadResponseException $e) {
            log_error('ゼウス通信失敗：' . $e->getMessage());
            return false;
        }
    }
    
    
    public function getEnrolXid($zeusResponse)
    {
        $pattern = "/<xid>(.*)<\/xid>/";
        preg_match($pattern, $zeusResponse, $matches);
        return (count($matches) > 1) ? $matches[1] : "";
    }

    public function getEnrolPaReq($zeusResponse)
    {
        $pattern = "/<PaReq>(.*)<\/PaReq>/";
        preg_match($pattern, $zeusResponse, $matches);
        return (count($matches) > 1) ? $matches[1] : "";
    }

    public function getEnrolAcsUrl($zeusResponse)
    {
        $pattern = "/<acs_url>(.*)<\/acs_url>/";
        preg_match($pattern, $zeusResponse, $matches);
        return (count($matches) > 1) ? $matches[1] : "";
    }
    
    public function getEnrolThreeDSMethod($zeusResponse)
    {
        $pattern = "/<threeDS2flag>(.*)<\/threeDS2flag>/";
        preg_match($pattern, $zeusResponse, $matches);
        $threeDS2flag = (count($matches) > 1) ? $matches[1] : "";
        if ($threeDS2flag == "1") {
            return "2";
        } else {
            return "1";
        }
    }
    
    public function getEnrolIframeUrl($zeusResponse)
    {
        $pattern = "/<iframeUrl>(.*)<\/iframeUrl>/";
        preg_match($pattern, $zeusResponse, $matches);
        return (count($matches) > 1) ? $matches[1] : "";
    }
    
    public function getMaskCardNumber($zeusResponse)
    {
        $pattern = "/<number>(.*)<\/number>/";
        preg_match($pattern, $zeusResponse, $matches);
        return (count($matches) > 1) ? $matches[1] : "";
    }
    
    public function getStatus($zeusResponse)
    {
        $pattern = "/<status>(.*)<\/status>/";
        preg_match($pattern, $zeusResponse, $matches);
        return (count($matches) > 1) ? $matches[1] : "";
    }
    
    /*
     * カード番号にマスクをかける
     */
    public function setMaskCardNumber($zeusRequest)
    {
        $pattern = "/<number>(.*)<\/number>/";
        preg_match($pattern, $zeusRequest, $matches);
        if (count($matches) > 1) {
            $zeusRequest = str_replace($matches[1], $this->getMaskedCard($matches[1]), $zeusRequest);
        }
        return $zeusRequest;
    }

    public function getZeusPostData3dToEnrolReq($order, $config, $cvvPostData)
    {
        $customer = $order->getCustomer();
        $sendid = "";
        if (is_null($customer)) {
            $sendid = "ORD" . $order->getId();
        } else {
            $sendid = $customer->getId();
        }
        $tokenKey = '<token_key>' . $order->getZeusCreditPaymentToken() . '</token_key>';
        
        $riskinfo = "";
        if (strcasecmp($this->eccubeConfig['zeus_payment_3ds2_norisk'], "true") !== 0) {
            //add risk parameters
            $shipping = null;
            foreach ($order->getShippings() as $shipping) {
                break;
            }
            $addr_match = "N";
            if ($shipping && ($shipping->getAddr01() == $order->getAddr01()) && ($shipping->getAddr02() == $order->getAddr02())) {
                $addr_match = "Y";
            }
            
            
            $riskinfo .= "<card_hold_info>";
            $riskinfo .= "<addr_match>" . $addr_match . "</addr_match>";
            $riskinfo .= "<bill_addr_line1>" . $order->getAddr01() . "</bill_addr_line1>";
            $riskinfo .= "<bill_addr_line2>" . $order->getAddr02() . "</bill_addr_line2>";
            $riskinfo .= "<bill_addr_line3></bill_addr_line3>";
            $riskinfo .= "<bill_addr_post_code>" . $order->getPostalCode() ."</bill_addr_post_code>";
            $riskinfo .= "<bill_addr_state>" . $order->getPref() . "</bill_addr_state>";
            $riskinfo .= "<cardholder_name>" . $order->getName01() . " " . $order->getName02() . "</cardholder_name>";
            if (is_null($customer)) {
                
            } else {
                $riskinfo .= "<Email>" . $customer->getEmail() . "</Email>";
            }
            $riskinfo .= "<home_phone.subscriber>" . $order->getPhoneNumber() . "</home_phone.subscriber>";
            $riskinfo .= "<mobile_phone.subscriber>" . $order->getPhoneNumber() . "</mobile_phone.subscriber>";
            if ($shipping != null) {
                $riskinfo .= "<ship_addr_line1>" . $shipping->getAddr01() . "</ship_addr_line1>";
                $riskinfo .= "<ship_addr_line2>" . $shipping->getAddr02() . "</ship_addr_line2>";
                $riskinfo .= "<ship_addr_line3></ship_addr_line3>";
                $riskinfo .= "<ship_addr_post_code>" . $shipping->getPostalCode() ."</ship_addr_post_code>";
                $riskinfo .= "<ship_addr_state>" . $shipping->getPref() . "</ship_addr_state>";
            }
            $riskinfo .= "</card_hold_info>";
            if (is_null($customer)) {
                $age = "01";
                $change = "";
            } else {
                $date = $customer->getCreateDate();
                $changeDate = $customer->getUpdateDate();
                if ($date < strtotime('-60 days')) {
                    $age = "05";
                } else if ($date < strtotime('-30 days')) {
                    $age = "04";
                } else {
                    $age = "03";
                }
                if ($changeDate < strtotime('-60 days')) {
                    $changeAge = "05";
                } else if ($changeDate < strtotime('-30 days')) {
                    $changeAge = "04";
                } else {
                    $changeAge = "03";
                }
                
                $change = date("Ymd", $changeDate);
                $create = date("Ymd", $date);
                    
            }
            $riskinfo .= "<acct_info>";
            $riskinfo .= "<ch_acc_age_ind>" . $age . "</ch_acc_age_ind>";
            $riskinfo .= "<ch_acc_change>" . $change . "</ch_acc_change>";
            $riskinfo .= "<ch_acc_change_ind>" . $changeAge . "</ch_acc_change_ind>";
            $riskinfo .= "<ch_acc_date>" . $create . "</ch_acc_date>";
            $riskinfo .= "</acct_info>";
        }
        
        
        if ($order->getZeusCreditPaymentQuick() && $this->isQuickChargeOK($order, $config->getCreditPayment())) {
            return "<?xml version=\"1.0\" encoding=\"utf-8\"?>" . "  <request  service=\"secure_link_3d\" action=\"enroll\">" . "    <authentication>" . "      <clientip>" . $config->getClientip() . "</clientip>" . "      <key>" . $config->getClientauthkey() . "</key>" . "    </authentication>" . $tokenKey . "    <card>" . "      <history action=\"send_email\">" . "        <key>telno</key>" . "        <key>sendid</key>" . "      </history>" . $cvvPostData . "    </card>" . "    <payment>" . "      <amount>" . $order->getPaymentTotal() . "</amount>" . "      <count>" . $order->getZeusCreditPaymentMethod() . "</count>" . "    </payment>" . "    <user>" . "      <email>" . $order->getEmail() . "</email>" . "      <telno validation=\"permissive\">" . $order->getPhoneNumber() . "</telno>" . "    </user>" . "    <uniq_key>" . "      <sendid>" . $sendid . "</sendid>" . "      <sendpoint>" . $order->getId() . "</sendpoint>" . "    </uniq_key>" . "<use_3ds2_flag>1</use_3ds2_flag>" . $riskinfo . "</request>";
        } else {
            return "<?xml version=\"1.0\" encoding=\"utf-8\"?>" . "  <request  service=\"secure_link_3d\" action=\"enroll\">" . "    <authentication>" . "      <clientip>" . $config->getClientip() . "</clientip>" . "      <key>" . $config->getClientauthkey() . "</key>" . "    </authentication>" . $tokenKey . "    <payment>" . "      <amount>" . $order->getPaymentTotal() . "</amount>" . "      <count>" . $order->getZeusCreditPaymentMethod() . "</count>" . "    </payment>" . "    <user>" . "      <email>" . $order->getEmail() . "</email>" . "      <telno validation=\"permissive\">" . $order->getPhoneNumber() . "</telno>" . "    </user>" . "    <uniq_key>" . "      <sendid>" . $sendid . "</sendid>" . "      <sendpoint>" . $order->getId() . "</sendpoint>" . "    </uniq_key>" . "<use_3ds2_flag>1</use_3ds2_flag>" . $riskinfo . "</request>";
        }
    }

    public function getZeusPostData3dToAuthReq($md, $pares)
    {
        return "<?xml version=\"1.0\" encoding=\"utf-8\"?>" . "  <request service=\"secure_link_3d\" action=\"authentication\">" . "    <xid>" . $md . "</xid>" . "    <PaRes>" . $pares . "</PaRes>" . "</request>";
    }

    public function getZeusPostData3dToPayReq($md)
    {
        return "<?xml version=\"1.0\" encoding=\"utf-8\"?>" . "  <request service=\"secure_link_3d\" action=\"payment\">" . "  <xid>" . $md . "</xid>" . "</request>";
    }

    public function acsPostRedirect3ds2($url, $postdata = '')
    {
        $this->response = new Response(
            "<html>\n" . "<head>\n" . "<title>決済処理中</title>\n" . 
            "<script src=\"https://linkpt.cardservice.co.jp/api/3ds2/3ds-web-wrapper.js\"></script>" .
            "<script>window.addEventListener('load', function(){ " .
            "setPareqParams(\"" . $postdata['MD'] . "\", " .
            "\"" . $postdata['PaReq'] . "\", " .
            "\"" . $postdata['TermUrl'] . "\", " .
            "\"" . $postdata['threeDSMehtod'] . "\", " .
            "\"" . $postdata['iframeUrl'] . "\"" .
            "); });\n" .
            "function _onPaResSuccess(data){ " .
            "  location.href = \"" . $this->route->generate("shopping_complete") . "\";\n" . 
            "}\n" .            
            "function _onError(error) { " .
            "console.log('_onError :', error);\n" . 
            "if (error == \"shopping_error\") { " .
            "  location.href = \"" . $this->route->generate("shopping_error") . "\";" .
            "} else if (error == \"shopping_login\") { " .
            "  location.href = \"" . $this->route->generate("shopping_login") . "\";" .
            "}\n" .
            "}\n" .
            "function startChallenge(url) { " .
            "    waitmsg = document.querySelector(\"div[id='waitmsg']\"); " .
            "    if (waitmsg) waitmsg.style.display = 'none'; " .
            "    var challengeUrl = decodeURIComponent(url); " .
            "    var appendNode = document.createElement('iframe'); " .
            "    appendNode.setAttribute('id', '3ds_challenge'); " .
            "    appendNode.setAttribute('width', '100%'); " .
            "    appendNode.setAttribute('height', '100%'); " .
            "    appendNode.setAttribute('style', 'border:0'); " .
            "    appendNode.setAttribute('src', challengeUrl); " .
            "    setThreedsContainer(); " .
            "    threedsContainer.appendChild(appendNode); " .
            " } ".            
            "</script></head><body>" .
            "<div id=\"waitmsg\" style='display:flex;justify-content:center;align-items:center;height:100vh;'><img src=\"data:image/gif;base64,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\"/> ただいま決済処理中のため、そのままお待ちください。</div>" .
            "<div id=\"3dscontainer\"></div>" .
            "</body>" . "</html>",
            Response::HTTP_OK,
            array('content-type' => 'text/html; charset=utf-8',
                'Cache-Control' => 'no-cache, must-revalidate')
            );
    }
    
    public function acsPostRedirect($url, $postdata = '')
    {
        $this->response = new Response(
            "<html>\n" . "<head>\n" . "<title>3D Secure</title>\n" . "<SCRIPT LANGUAGE=\"Javascript\">\n" . "<!--  \n" . "function OnLoadEvent() {  \n" . " document.downloadForm.submit(); \n" . "} \n" . "//-->  \n" . "</SCRIPT>  \n" . "</head>  " . "<body onload=\"OnLoadEvent();\">  " . "<form name=\"downloadForm\" action=\"" . $url . "\" method=\"POST\">  " . "<noscript>  " . "<h3>Please click Submit to continue the processing of your 3-D Secure transaction.</h3><BR>  " . "<input type=\"submit\" value=\"処理を続ける\">  " . "</div>  " . "</noscript>  " . "<input type=\"hidden\" name=\"PaReq\"   value=\"" . htmlspecialchars($postdata['PaReq']) . "\">  " . "<input type=\"hidden\" name=\"MD\"      value=\"" . htmlspecialchars($postdata['MD']) . "\">  " . "<input type=\"hidden\" name=\"TermUrl\" value=\"" . htmlspecialchars($postdata['TermUrl']) . "\">  " . "</form>  " . "</body>  " . "</html>",
            Response::HTTP_OK,
            array('content-type' => 'text/html; charset=utf-8',
                'Cache-Control' => 'no-cache, must-revalidate')
            );
    }

    public function getZeusPostDataInformation($ipcode, $key)
    {
        return "<?xml version=\"1.0\" encoding=\"utf-8\"?>" . "<request service=\"information\" action=\"ec_plugin\">" . "  <authentication>" . "    <clientip>" . $ipcode . "</clientip>" . "    <key>" . $key . "</key>" . "  </authentication>" . "</request>";
    }
    
    public function getZeusResultData($zeusResponse, $pattern)
    {
        preg_match($pattern, $zeusResponse, $matches);
        return (count($matches) > 1) ? $matches[1] : "";
    }

    public function getUniqKey()
    {
        return sha1(uniqid(mt_rand(), true));
    }

    function getSendPoint($privateKey, $ipcode, $orderId)
    {
        return sha1($privateKey . $this->numberToStrReplace($ipcode . $orderId));
    }

    function numberToStrReplace($val)
    {
        $search = array(
            0,
            1,
            2,
            3,
            4,
            5,
            6,
            7,
            8,
            9
        );
        $replace = array(
            'D',
            'Y',
            'Z',
            'J',
            'W',
            'M',
            'T',
            'S',
            'B',
            'P'
        );
        return str_replace($search, $replace, $val);
    }

    /*
     * コンビニ決済、銀行振り込みの戻り処理
     */
    public function receive($type, $zeusResponse, $mdlName)
    {
        $ip = $this->getIp();
        if (!$this->isValidIp($ip)) {
            return $this->errorExit('[ゼウス' . $mdlName . 'ステータス変更]IP(' . $ip . ')が許可されていません。' . trim($this->eccubeConfig['zeus_resp_server_ips']));
        }
        // EC-CUBEデータの取得
        $configRepository = $this->entityManager->getRepository(Config::class);
        $config = $configRepository->get();
        if (empty($config)) {
            return $this->errorExit('[ゼウス' . $mdlName . 'ステータス変更]管理画面での設定が完了しておりません。');
        }

        // リクエスト情報チェック
        if (empty($zeusResponse['order_no'])) {
            return $this->errorExit('[ゼウス' . $mdlName . 'ステータス変更] ゼウスリクエストデータのオーダー番号が不正です。order_no:' . $zeusResponse['order_no']);
        }
        if ($config->getClientipByType($type) != $zeusResponse['clientip']) {
            return $this->errorExit('[ゼウス' . $mdlName . 'ステータス変更] ゼウスリクエストデータのIPコードが不正です。clientip:' . $zeusResponse['clientip']);
        }
        if (empty($zeusResponse['sendid'])) {
            return $this->errorExit('[ゼウス' . $mdlName . 'ステータス変更] ユニークキーが設定されていません。sendid:' . $zeusResponse['sendid']);
        }

        $orderRepository = $this->entityManager->getRepository(Order::class);
        $order = $orderRepository->find($zeusResponse['sendid']);
        if (empty($order)) {
            return $this->errorExit('[ゼウス' . $mdlName . 'ステータス変更] この注文情報は登録されていません。注文番号:' . $zeusResponse['sendid']);
        }
        if ($order->getPayment() != $config->getPayment($type)) {
            return $this->errorExit('[ゼウス' . $mdlName . 'ステータス変更] ' . $mdlName . 'データではありません。注文番号:' . $zeusResponse['sendid']);
        }
        if (empty($zeusResponse['money'])) {
            return $this->errorExit('[ゼウス' . $mdlName . 'ステータス変更] ユニークキーが設定されていません。money:' . $zeusResponse['money']);
        }
        if (intval($zeusResponse['money'])!=round($order->getPaymentTotal())) {
            return $this->errorExit('[ゼウス' . $mdlName . 'ステータス変更] フリーパラメータが不正です。money:' . $zeusResponse['money']);
        }
        if (empty($zeusResponse['sendpoint'])) {
            return $this->errorExit('[ゼウス' . $mdlName . 'ステータス変更] フリーパラメータが設定されていません。sendpoint:' . $zeusResponse['sendpoint']);
        }

        if ($this->sendPointCheck($zeusResponse['sendpoint'], $config->getKey($type), $zeusResponse['clientip'], $zeusResponse['sendid'])) { // EC-CUBE order_id
            return $this->errorExit('[ゼウス' . $mdlName . 'ステータス変更] フリーパラメータが不正です。sendpoint:' . $zeusResponse['sendpoint']);
        }

        switch ($type) {
            case 'cvs':
                if (! $this->orderStatusUpdateCvs($order, $zeusResponse)) {
                    return "Failed";
                }
                break;
            case 'ebank':
                if (! $this->orderStatusUpdateEbank($order, $zeusResponse)) {
                    return "Failed";
                }
                break;
            case 'eaccount':
                if (! $this->orderStatusUpdateEaccount($order, $zeusResponse)) {
                    return "Failed";
                }
                break;
            default:
                break;
        }
        log_notice('[ゼウス' . $mdlName . 'ステータス変更]処理終了');
        return "OK";
    }

    /*
     * コンビニ決済の注文データを更新
     */
    function orderStatusUpdateCvs($order, $zeusResponse)
    {
        if ($order) {
            $payNo = (empty($zeusResponse['pay_no1'])) ? $zeusResponse['pay_no2'] : $zeusResponse['pay_no1'];
            $payLimit = (strlen($zeusResponse['pay_limit']) == 8) ? substr($zeusResponse['pay_limit'], 0, 4) . '/' . substr($zeusResponse['pay_limit'], 4, 2) . '/' . substr($zeusResponse['pay_limit'], 6, 2) : $zeusResponse['pay_limit'];
            $memo = "ゼウスオーダー番号：[" . $zeusResponse['order_no'] . "]\n" . "払込番号：[" . $payNo . "]\n" . "支払期日：[" . $payLimit . "]\nステータス：[" . $zeusResponse['status'] . "]\n";

            $orderStatus = '';

            $oldOrderStatusObject = $order->getOrderStatus();

            switch ($zeusResponse['status']) {
                case $this->eccubeConfig['zeus_cvs_not_credited']: // 未入金
                    $orderStatus = OrderStatus::NEW; // ->入金待ち
                    break;

                case $this->eccubeConfig['zeus_cvs_preliminary_deposit']: // 速報済（入金速報時）
                    $order->setPaymentDate(new \DateTime());
                    $orderStatus = OrderStatus::PAID; // ->入金済み(注文受付)
                    break;

                case $this->eccubeConfig['zeus_cvs_cancel_payment']: // 速報取消（入金取消時）
                    $orderStatus = OrderStatus::CANCEL; // ->キャンセル
                    break;
                default:
                    return true;
            }
            $saveOrderErr = "注文情報作成失敗しました。";
            try {
                $note = $order->getNote();
                $saveOrderFailed = ($saveOrderErr === substr($note, - strlen($saveOrderErr)));
                if (strlen($note) > 0) {
                    $str = $note . "\r\n";
                    if ($saveOrderFailed) {
                        $memo = $memo . "\r\n" . $saveOrderErr;
                    }
                } else {
                    $str = "";
                }
                $order->setNote($str . $memo);
                $newOrderStatus = $this->orderStatusRepository->find($orderStatus);
                $order->setOrderStatus($newOrderStatus);

                if ($zeusResponse['status'] == $this->eccubeConfig['zeus_cvs_not_credited']) {
                    $order->setZeusOrderId($zeusResponse['order_no']);
                    $order->setZeusResponseData(print_r($zeusResponse,true));
                    if (null === $order->getOrderDate()) {
                        $order->setOrderDate(new \DateTime());
                    }

                    //$this->checkStock($order);

                    $this->purchaseFlow->prepare($order, new PurchaseContext());
                    $this->purchaseFlow->commit($order, new PurchaseContext());
                } elseif($zeusResponse['status'] == $this->eccubeConfig['zeus_cvs_cancel_payment'] && $oldOrderStatusObject->getId()!=$orderStatus){
                    $order->setOrderStatus($oldOrderStatusObject);
                    $this->orderStateMachine->apply($order, $newOrderStatus);
                }
                $order->setOrderStatus($this->orderStatusRepository->find($orderStatus));
                $this->entityManager->persist($order);
                $this->entityManager->flush();
                if ($zeusResponse['status'] == $this->eccubeConfig['zeus_cvs_not_credited']) {
                    $this->mailService->sendOrderMail($order);
                    $this->entityManager->flush();
                }


            } catch (\Throwable $e) {
                $this->errorExit('[ゼウスコンビニ決済ステータス変更] ステータス変更失敗。sendpoint:' . $zeusResponse['sendpoint'] . " " . $e->getMessage());
                log_error($e);

                $orderRepository = $this->entityManager->getRepository(Order::class);
                $order = $orderRepository->find($zeusResponse['sendid']);

                $curStatusId = $order->getOrderStatus()->getId();
                if ($curStatusId != $orderStatus) {
                    $order->setNote($str . $memo);
                    $order->setOrderStatus($this->orderStatusRepository->find->find($orderStatus));
                }

                if (strlen($order->getNote()) > 0) {
                    $str = $order->getNote() . "\r\n";
                } else {
                    $str = "";
                }
                $order->setNote($str . $saveOrderErr);
                $this->entityManager->persist($order);
                $this->entityManager->flush();
                return false;
            }
        }
        return true;
    }

    /*
     * 銀行振込決済の注文データを更新
     */
    function orderStatusUpdateEbank($order, $zeusResponse)
    {
        if ($order) {
            $memo = "ゼウスオーダー番号：[" . $zeusResponse['order_no'] . "]\n" . "受付番号：[" . $zeusResponse['tracking_no'] . "]\nステータス：[" . $zeusResponse['status'] . "]\n";

            $orderStatus = '';
            switch ($zeusResponse['status']) {
                case $this->eccubeConfig['zeus_ebank_wait']: // 受付中
                case $this->eccubeConfig['zeus_ebank_not_paid']: // 未入金
                    $orderStatus = OrderStatus::NEW; // ->入金待ち
                    break;
                case $this->eccubeConfig['zeus_ebank_paid']: // 入金済
                    $order->setPaymentDate(new \DateTime());
                    $orderStatus = OrderStatus::PAID; // ->入金済み(注文受付)
                    break;
                case $this->eccubeConfig['zeus_ebank_error']: // エラー
                    return true;
                case $this->eccubeConfig['zeus_ebank_failed']: // 未入金
                    $orderStatus = OrderStatus::CANCEL; // ->キャンセル
                    break;
                default:
                    return true;
            }
            try {
                if (strlen($order->getNote()) > 0) {
                    $str = $order->getNote() . "\r\n";
                } else {
                    $str = "";
                }
                $order->setNote($str . $memo);
                $order->setOrderStatus($this->orderStatusRepository->find($orderStatus));

                if ($zeusResponse['status'] == $this->eccubeConfig['zeus_ebank_paid']) {

                    $order->setZeusOrderId($zeusResponse['order_no']);
                    $order->setZeusResponseData(print_r($zeusResponse,true));
                    if (null === $order->getOrderDate()) {
                        $order->setOrderDate(new \DateTime());
                    }

                    //$this->checkStock($order);

                    $this->purchaseFlow->prepare($order, new PurchaseContext());
                    $this->purchaseFlow->commit($order, new PurchaseContext());
                }
                $order->setOrderStatus($this->orderStatusRepository->find($orderStatus));
                $this->entityManager->persist($order);
                $this->entityManager->flush();
                if ($zeusResponse['status'] == $this->eccubeConfig['zeus_ebank_paid']) {
                    $this->mailService->sendOrderMail($order);
                    $this->entityManager->flush();
                }

            } catch (\Throwable $e) {
                $this->errorExit('[ゼウス銀行振込決済ステータス変更] ステータス変更失敗。sendpoint:' . $zeusResponse['sendpoint'] . " " . $e->getMessage());
                log_error($e);

                $orderRepository = $this->entityManager->getRepository(Order::class);
                $order = $orderRepository->find($zeusResponse['sendid']);

                $curStatusId = $order->getOrderStatus()->getId();
                if ($curStatusId != $orderStatus) {
                    $order->setNote($str . $memo);
                    $order->setOrderStatus($this->app['eccube.repository.order_status']->find($orderStatus));
                }

                if (strlen($order->getNote()) > 0) {
                    $str = $order->getNote() . "\r\n";
                } else {
                    $str = "";
                }
                $order->setNote($str . "注文情報作成失敗しました。");
                $this->entityManager->persist($order);
                $this->entityManager->flush();
                return false;
            }
        }
        return true;
    }
    

    /*
     * 口座振替決済の注文データを更新
     */
    function orderStatusUpdateEaccount($order, $zeusResponse)
    {
        if ($order) {
            $memo = "ゼウスオーダー番号：[" . $zeusResponse['order_no'] . "]\n" . "受付番号：[" . $zeusResponse['tracking_no'] . "]\nステータス：[" . $zeusResponse['status'] . "]\n";

            $orderStatus = '';
            switch ($zeusResponse['status']) {
                case $this->eccubeConfig['zeus_eaccount_wait']: // 受付中
                case $this->eccubeConfig['zeus_eaccount_end']: // 未入金
                    $orderStatus = OrderStatus::NEW; // ->入金待ち
                    break;
                case $this->eccubeConfig['zeus_eaccount_paid']: // 入金済
                    $order->setPaymentDate(new \DateTime());
                    $orderStatus = OrderStatus::PAID; // ->入金済み(注文受付)
                    break;
                case $this->eccubeConfig['zeus_eaccount_failed']: // エラー
                    return true;
                case $this->eccubeConfig['zeus_eaccount_stopped']: // 未入金
                    $orderStatus = OrderStatus::CANCEL; // ->キャンセル
                    break;
                default:
                    return true;
            }
            try {
                if (strlen($order->getNote()) > 0) {
                    $str = $order->getNote() . "\r\n";
                } else {
                    $str = "";
                }
                $order->setNote($str . $memo);
                $order->setOrderStatus($this->orderStatusRepository->find($orderStatus));

                if ($zeusResponse['status'] == $this->eccubeConfig['zeus_eaccount_paid']) {

                    $order->setZeusOrderId($zeusResponse['order_no']);
                    $order->setZeusResponseData(print_r($zeusResponse,true));
                    if (null === $order->getOrderDate()) {
                        $order->setOrderDate(new \DateTime());
                    }

                    //$this->checkStock($order);

                    $this->purchaseFlow->prepare($order, new PurchaseContext());
                    $this->purchaseFlow->commit($order, new PurchaseContext());
                }
                $order->setOrderStatus($this->orderStatusRepository->find($orderStatus));
                $this->entityManager->persist($order);
                $this->entityManager->flush();
                if ($zeusResponse['status'] == $this->eccubeConfig['zeus_eaccount_paid']) {
                    $this->mailService->sendOrderMail($order);
                    $this->entityManager->flush();
                }

            } catch (\Throwable $e) {
                $this->errorExit('[ゼウス口座振替決済ステータス変更] ステータス変更失敗。sendpoint:' . $zeusResponse['sendpoint'] . " " . $e->getMessage());
                log_error($e);

                $orderRepository = $this->entityManager->getRepository(Order::class);
                $order = $orderRepository->find($zeusResponse['sendid']);

                $curStatusId = $order->getOrderStatus()->getId();
                if ($curStatusId != $orderStatus) {
                    $order->setNote($str . $memo);
                    $order->setOrderStatus($this->app['eccube.repository.order_status']->find($orderStatus));
                }

                if (strlen($order->getNote()) > 0) {
                    $str = $order->getNote() . "\r\n";
                } else {
                    $str = "";
                }
                $order->setNote($str . "注文情報作成失敗しました。");
                $this->entityManager->persist($order);
                $this->entityManager->flush();
                return false;
            }
        }
        return true;
    }

/* 支払成功の場合は在庫チェックしない
    function checkStock($order)
    {
        foreach($order->getItems() as $item){
            if (!$item->isProduct()) {
                continue;
            }
            if ($item->getProductClass()->isStockUnlimited()) {
                continue;
            }
            $stock = $item->getProductClass()->getStock();
            $quantity = $item->getQuantity();
            if ($stock == 0) {
                throw new \Exception("商品ステータスが変更されました。");
            }
            if ($stock < $quantity) {
                throw new \Exception("商品ステータスが変更されました。");
            }
        }
    }
*/
    function sendPointCheck($sendpoint, $privateKey, $ipcode, $orderId)
    {
        $checkUniqKey = sha1($privateKey . $this->numberToStrReplace($ipcode . $orderId));
        return ! ($sendpoint === $checkUniqKey);
    }

    function getIp() {
        $fields = array(
            'HTTP_CF_CONNECTING_IP',
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR',
        );

        foreach ( $fields as $ip_field ) {
            if ( ! empty( $_SERVER[ $ip_field ] ) ) {
                return $_SERVER[ $ip_field ];
            }
        }
        return null;
    }

    function isValidIp($ip) {
        $allowedIps = trim($this->eccubeConfig['zeus_resp_server_ips']);

        if ($allowedIps != '') {
            $ips = explode(",", $allowedIps);
            return in_array($ip, $ips);
        }

        return true; //allow all ips if not set
    }

    function errorExit($message)
    {
        log_error($message);
        log_error('処理終了_受信データが不正です');
        header('HTTP/1.1 400 Bad Request');
        return "Failed";
    }
    
    function formatPrice($number) {
        $locale = $this->eccubeConfig['locale'];
        $currency = $this->eccubeConfig['currency'];
        $formatter = new \NumberFormatter($locale, \NumberFormatter::CURRENCY);
        
        return $formatter->formatCurrency($number, $currency);
        
    }
}
