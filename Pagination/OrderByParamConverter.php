<?php
namespace Vanio\DomainBundle\Pagination;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;

class OrderByParamConverter implements ParamConverterInterface
{
    const DEFAULT_OPTIONS = [
        'order_parameter' => 'order',
        'default_order' => 'id',
        'dql_alias' => null,
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
        $orderParameter = $options['translation_domain']
            ? $this->translator->trans($options['order_parameter'], [], $options['translation_domain'])
            : $options['order_parameter'];
        $orderBy = OrderBy::fromString(
            $request->query->get($orderParameter, $options['default_order']),
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
