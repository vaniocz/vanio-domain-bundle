<?php
namespace Vanio\DomainBundle\Pagination;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\HttpFoundation\Request;
use Vanio\DomainBundle\Request\LocaleParamConverter;
use Vanio\DomainBundle\Specification\Locale;

class MultilingualFilterParamConverter implements ParamConverterInterface
{
    /** @var callable|null */
    private $currentLocaleCallable;

    public function __construct(?callable $currentLocaleCallable)
    {
        $this->currentLocaleCallable = $currentLocaleCallable;
    }

    public function apply(Request $request, ParamConverter $configuration): bool
    {
        (new FilterParamConverter($configuration->getOptions()))->apply($request, $configuration);
        $filter = $request->attributes->get($configuration->getName());

        (new LocaleParamConverter($this->currentLocaleCallable, 'filterLocale'))->apply($request, $configuration);
        $locale = $request->attributes->get($configuration->getName());
        assert($locale instanceof Locale);

        $multilingualFilter = new MultilingualFilter($filter, $locale->withIncludedUntranslated());
        $request->attributes->set($configuration->getName(), $multilingualFilter);

        return true;
    }

    public function supports(ParamConverter $configuration): bool
    {
        return $configuration->getClass() === MultilingualFilter::class;
    }
}
