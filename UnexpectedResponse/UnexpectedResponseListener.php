<?php
namespace Vanio\DomainBundle\UnexpectedResponse;

use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;

class UnexpectedResponseListener
{
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $exception = $event->getException();

        if ($exception instanceof UnexpectedResponseException) {
            $event->setResponse($exception->getResponse());
        }
    }
}
