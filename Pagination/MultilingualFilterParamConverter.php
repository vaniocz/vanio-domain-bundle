<?php
namespace Vanio\DomainBundle\Pagination;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\HttpFoundation\Request;
use Vanio\DomainBundle\Request\LocaleParamConverter;

class MultilingualFilterParamConverter implements ParamConverterInterface
{
    public function apply(Request $request, ParamConverter $configuration): bool
    {
        $options = $configuration->getOptions();

        (new FilterParamConverter($options))->apply($request, $configuration);
        $filter = $request->attributes->get($configuration->getName());

        (new LocaleParamConverter('filterLocale'))->apply($request, $configuration);
        $locale = $request->attributes->get($configuration->getName());

        $multilingualFilter = new MultilingualFilter($filter, $locale->withUntranslated());
        $request->attributes->set($configuration->getName(), $multilingualFilter);

        return true;
    }

    public function supports(ParamConverter $configuration): bool
    {
        return $configuration->getClass() === MultilingualFilter::class;
    }
}
