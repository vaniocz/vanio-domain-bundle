<?php
namespace Vanio\DomainBundle\Pagination;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\HttpFoundation\Request;
use Vanio\DomainBundle\Request\LocaleParamConverter;
use Vanio\DomainBundle\Specification\Locale;

class MultilingualFilterParamConverter implements ParamConverterInterface
{
    public function apply(Request $request, ParamConverter $configuration): bool
    {
        (new FilterParamConverter($configuration->getOptions()))->apply($request, $configuration);
        $filter = $request->attributes->get($configuration->getName());

        (new LocaleParamConverter('filterLocale'))->apply($request, $configuration);
        /** @var Locale $locale */
        $locale = $request->attributes->get($configuration->getName());

        $multilingualFilter = new MultilingualFilter($filter, $locale->withIncludedUntranslated());
        $request->attributes->set($configuration->getName(), $multilingualFilter);

        return true;
    }

    public function supports(ParamConverter $configuration): bool
    {
        return $configuration->getClass() === MultilingualFilter::class;
    }
}
