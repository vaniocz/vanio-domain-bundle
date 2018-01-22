<?php
namespace Vanio\DomainBundle\Request;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\HttpFoundation\AcceptHeader;
use Symfony\Component\HttpFoundation\Request;

class AcceptHeaderParamConverter implements ParamConverterInterface
{
    public function supports(ParamConverter $configuration): bool
    {
        return $configuration->getClass() === AcceptHeader::class;
    }

    public function apply(Request $request, ParamConverter $configuration): bool
    {
        $accept = AcceptHeader::fromString($request->headers->get('Accept'));
        $request->attributes->set($configuration->getName(), $accept);

        return true;
    }
}
