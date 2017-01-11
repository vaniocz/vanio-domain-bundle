<?php
namespace Vanio\DomainBundle\Pagination;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\HttpFoundation\Request;

class PageParamConverter implements ParamConverterInterface
{
    const DEFAULT_OPTIONS = [
        'page_parameter' => 'page',
        'records_per_page' => 10,
    ];

    /** @var array */
    private $options;

    public function __construct(array $options = [])
    {
        $this->options = $options + self::DEFAULT_OPTIONS;
    }

    public function apply(Request $request, ParamConverter $configuration): bool
    {
        $options = $configuration->getOptions() + $this->options;
        $class = $configuration->getClass();
        $page = $class::{'create'}($request->query->get($options['page_parameter'], '1'), $options['records_per_page']);
        $request->attributes->set($configuration->getName(), $page);

        return true;
    }

    public function supports(ParamConverter $configuration): bool
    {
        return is_a($configuration->getClass(), PageSpecification::class, true);
    }
}
