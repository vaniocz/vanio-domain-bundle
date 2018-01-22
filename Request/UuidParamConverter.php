<?php
namespace Vanio\DomainBundle\Request;

use Ramsey\Uuid\Uuid;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class UuidParamConverter implements ParamConverterInterface
{
    public function supports(ParamConverter $configuration): bool
    {
        return $configuration->getClass() === Uuid::class;
    }

    public function apply(Request $request, ParamConverter $configuration): bool
    {
        $parameter = $request->get($configuration->getName());

        if ($parameter && is_string($parameter)) {
            if (!Uuid::isValid($parameter)) {
                throw new NotFoundHttpException(sprintf('Invalid UUID "%s".', $parameter));
            }

            $request->attributes->set($configuration->getName(), Uuid::fromString($parameter));
        }

        return true;
    }
}
