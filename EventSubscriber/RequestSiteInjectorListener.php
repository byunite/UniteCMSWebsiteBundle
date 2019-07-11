<?php


namespace Unite\CMSWebsiteBundle\EventSubscriber;

use Unite\CMSWebsiteBundle\Services\SiteManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\RouterInterface;

class RequestSiteInjectorListener implements EventSubscriberInterface
{
    const ATTRIBUTE_KEY = 'site';

    /**
     * @var SiteManager $siteManager
     */
    protected $siteManager;

    /**
     * @var RouterInterface $router
     */
    protected $router;

    /**
     * @var bool
     */
    protected $multiLanguage;

    public function __construct(SiteManager $siteManager, RouterInterface $router, bool $multiLanguage = false)
    {
        $this->siteManager = $siteManager;
        $this->router = $router;
        $this->multiLanguage = $multiLanguage;
    }

    /**
     * See, if we have a mapped domain for the request domain. if so, add a
     * Site object to the request. If not, show a special response.
     *
     * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function onKernelRequest(RequestEvent $event)
    {
        // Only check for master request and not internal redirects.
        if (!$event->isMasterRequest()) {
            return;
        }

        // If site is already set, just skip.
        if($event->getRequest()->attributes->has(self::ATTRIBUTE_KEY)) {
            return;
        }

        // Store the full site object to the current request.
        if($site = $this->siteManager->findSiteByHost(
            $event->getRequest()->getHost(),
            $this->multiLanguage ? $event->getRequest()->getLocale() : null
        )) {
            $event->getRequest()->attributes->set(self::ATTRIBUTE_KEY, $site);
            return;
        }

        // If we could not find a site object for the hostname, redirect to public.
        throw new NotFoundHttpException(sprintf('Site with hostname "%s" not found.', $event->getRequest()->getHost()));
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            'kernel.request' => 'onKernelRequest',
        ];
    }
}
