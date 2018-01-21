<?php
namespace Vanio\DomainBundle\Request;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\HttpFoundation\Request;
use Vanio\DomainBundle\Specification\Locale;

class LocaleParamConverter implements ParamConverterInterface
{
    /** @var string|null */
    private $localeParameter;

    public function __construct(string $localeParameter = null)
    {
        $this->localeParameter = $localeParameter;
    }

    public function supports(ParamConverter $configuration): bool
    {
        return $configuration->getClass() === Locale::class;
    }

    public function apply(Request $request, ParamConverter $configuration): bool
    {
        $localeParameter = $this->localeParameter ?? $configuration->getName();

        if ($request->attributes->has($localeParameter)) {
            $locale = $request->attributes->get($localeParameter);
        } else if ($localeParameter !== 'locale') {
            $locale = $request->query->get($localeParameter);
        }

        $request->attributes->set($configuration->getName(), new Locale($locale ?? $request->getLocale()));

        return true;
    }
}
