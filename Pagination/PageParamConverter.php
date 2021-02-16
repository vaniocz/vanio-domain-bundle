<?php
namespace Vanio\DomainBundle\Pagination;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;

class PageParamConverter implements ParamConverterInterface
{
    const DEFAULT_OPTIONS = [
        'page_parameter' => 'page',
        'records_per_page' => 10,
        'records_on_first_page' => null,
        'translation_domain' => null,
    ];

    /** @var TranslatorInterface */
    private $translator;

    /** @var array */
    private $options;

    public function __construct(TranslatorInterface $translator, array $options = [])
    {
        $this->translator = $translator;
        $this->options = $options + self::DEFAULT_OPTIONS;
    }

    public function apply(Request $request, ParamConverter $configuration): bool
    {
        $options = $configuration->getOptions() + $this->options;
        $class = $configuration->getClass();
        $pageParameter = $options['translation_domain']
            ? $this->translator->trans($options['page_parameter'], [], $options['translation_domain'])
            : $options['page_parameter'];
        $page = $class::{'create'}(
            $request->query->get($pageParameter, '1'),
            $options['records_per_page'],
            $options['records_on_first_page']
        );
        $request->attributes->set($configuration->getName(), $page);

        return true;
    }

    public function supports(ParamConverter $configuration): bool
    {
        return is_a($configuration->getClass(), PageSpecification::class, true);
    }
}
