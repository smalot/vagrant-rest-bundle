<?php

namespace Smalot\Vagrant\RestBundle\EventListener;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class ResponseListener
 * @package Smalot\Vagrant\RestBundle\EventListener
 */
class ResponseListener implements EventSubscriberInterface
{
    /**
     * @param FilterResponseEvent $event
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        $response = $event->getResponse();

        if (!$response->headers->has('Content-Encoding')) {
            $content = $response->getContent();

            $request = $event->getRequest();
            $encodings = $request->getEncodings();

            if ((empty($encodings) || in_array('gzip', $encodings, true)) && extension_loaded('zlib')) {
                $response->headers->set('Content-Encoding', 'gzip');
                $content = gzencode($content, 9, FORCE_GZIP);
                $response->setContent($content);
            }

            $response->headers->set('Content-Length', strlen($content));
        }
    }

    public function onKernelTerminate(Event $event)
    {
        echo '';

        flush();            // Unless both are called !
        if (session_id()) {
            session_write_close();
        }
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return array(
          KernelEvents::RESPONSE => array('onKernelResponse', 20),
          KernelEvents::TERMINATE => array('onKernelTerminate', 20),
        );
    }
}
