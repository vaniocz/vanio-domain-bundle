<?php
namespace Vanio\DomainBundle\Pagination;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\HttpFoundation\Request;

class OrderByParamConverter implements ParamConverterInterface
{
    const DEFAULT_OPTIONS = [
        'order_parameter' => 'order',
        'default_order' => 'id',
        'dql_alias' => null,
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
        $orderBy = OrderBy::fromString(
            $request->query->get($options['order_parameter'], $options['default_order']),
            $options['dql_alias']
        );
        $request->attributes->set($configuration->getName(), $orderBy);

        return true;
    }

    public function supports(ParamConverter $configuration): bool
    {
        return $configuration->getClass() === OrderBy::class;
    }
}
