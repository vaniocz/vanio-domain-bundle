<?php
namespace Vanio\DomainBundle\Request;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\HttpFoundation\Request;
use Vanio\DomainBundle\Specification\Locale;

class LocaleParamConverter implements ParamConverterInterface
{
    public function supports(ParamConverter $configuration)
    {
        return ($configuration->getClass() == Locale::class);
    }

    public function apply(Request $request, ParamConverter $configuration)
    {
        $name = $configuration->getName();
        $specification = new Locale($request->getLocale());

        $request->attributes->set($name, $specification);
    }
}
