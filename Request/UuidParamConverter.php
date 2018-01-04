<?php
namespace Vanio\DomainBundle\Request;

use Ramsey\Uuid\Uuid;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\HttpFoundation\Request;

class UuidParamConverter implements ParamConverterInterface
{
    public function supports(ParamConverter $configuration)
    {
        return $configuration->getClass() === Uuid::class;
    }

    public function apply(Request $request, ParamConverter $configuration): bool
    {
        $name = $configuration->getName();
        $parameter = $request->get($name);

        if ($parameter && is_string($parameter) && Uuid::isValid($parameter)) {
            $request->attributes->set($name, Uuid::fromString($parameter));
        }

        return true;
    }
}
