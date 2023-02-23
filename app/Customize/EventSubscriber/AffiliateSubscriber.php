<?php


namespace Customize\EventSubscriber;


use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\Request;

class AffiliateSubscriber implements EventSubscriberInterface
{
    const COOKIE_NAME = 'a8_parameter';
    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::RESPONSE => 'onResponse',
            //KernelEvents::CONTROLLER_ARGUMENTS => 'onKernelControllerArguments'
        ];
    }

    /**
     * ページにアクセスしたらCookieに保存
     *
     * @param FilterResponseEvent $event
     * @throws \Exception
     */
    public function onResponse(FilterResponseEvent $event): void
    {
        if (false === $event->isMasterRequest()) {
            return;
        }

        $a8_param = $event->getRequest()->get('a8');
        //$cookie = $this->getCookie($event);

        if($a8_param){
            // Cookie作成・更新
            $cookie = $this->createCookie($a8_param);

            $response = $event->getResponse();
            $response->headers->setCookie($cookie);
            //$event->setResponse($response);
        }

    }


    /**
     * Cookie作成・更新
     *
     * @return Cookie
     * @throws \Exception
     */
    private function createCookie($a8_param): Cookie
    {
        return new Cookie(
            self::COOKIE_NAME,
            json_encode($a8_param),
            (new \DateTime())->modify('3 month'),
            $this->eccubeConfig['env(ECCUBE_COOKIE_PATH)']
        );
    }
}