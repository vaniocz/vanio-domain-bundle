<?php
namespace Vanio\DomainBundle\Pagination;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\HttpFoundation\Request;

class PageParamConverter implements ParamConverterInterface
{
    const DEFAULT_OPTIONS = [
        'parameter' => 'page',
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
        $pageNumber = max($request->query->getInt($options['parameter'], 1), 1);
        $request->attributes->set($configuration->getName(), new Page($pageNumber, $options['records_per_page']));

        return true;
    }

    public function supports(ParamConverter $configuration): bool
    {
        return $configuration->getClass() === Page::class;
    }
}
