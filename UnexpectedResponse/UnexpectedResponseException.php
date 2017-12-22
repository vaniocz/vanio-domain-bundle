<?php
namespace Vanio\DomainBundle\UnexpectedResponse;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class UnexpectedResponseException extends \RuntimeException implements HttpExceptionInterface
{
    /** @var Response */
    private $response;

    public function __construct(Response $response)
    {
        $this->response = $response;
        parent::__construct('This exception should be handled by UnexpectedResponseExceptionListener.');
    }

    public function getResponse(): Response
    {
        return $this->response;
    }

    public function getStatusCode(): int
    {
        return $this->response->getStatusCode();
    }

    public function getHeaders(): array
    {
        return $this->response->headers->all();
    }
}
