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

        if ($request->attributes->has($name)) {
            $locale = $request->attributes->get($name);
        } else if ($name !== 'locale') {
            $locale = $request->query->get($name);
        }

        $request->attributes->set($name, new Locale($locale ?? $request->getLocale()));
    }
}
