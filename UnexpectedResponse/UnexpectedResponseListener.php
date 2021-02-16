<?php
namespace Vanio\DomainBundle\UnexpectedResponse;

use Symfony\Component\HttpKernel\Event\ExceptionEvent;

class UnexpectedResponseListener
{
    public function onKernelException(ExceptionEvent $event)
    {
        $exception = $event->getThrowable();

        if ($exception instanceof UnexpectedResponseException) {
            $event->setResponse($exception->getResponse());
        }
    }
}
