<?php
namespace Vanio\DomainBundle\Request;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\HttpFoundation\Request;
use Vanio\DomainBundle\Specification\Locale;

class LocaleParamConverter implements ParamConverterInterface
{
    /** @var callable|null */
    private $currentLocaleCallable;

    /** @var string|null */
    private $localeParameter;

    public function __construct(?callable $currentLocaleCallable, ?string $localeParameter = null)
    {
        $this->currentLocaleCallable = $currentLocaleCallable;
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
        } elseif ($localeParameter !== 'locale') {
            $locale = $request->query->get($localeParameter);
        }

        if (empty($locale)) {
            $currentLocaleCallable = $this->currentLocaleCallable;
            $locale = $currentLocaleCallable ? $currentLocaleCallable() : $request->getLocale();
        }

        $request->attributes->set($configuration->getName(), new Locale($locale));

        return true;
    }
}
