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
        $locale = $request->getLocale();
        if ($name != 'locale') {
            $locale = $request->attributes->get($name) ?? $request->query->get($name) ?? $locale;
        }
        $specification = new Locale($locale);

        $request->attributes->set($name, $specification);
    }
}
