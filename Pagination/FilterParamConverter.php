<?php
namespace Vanio\DomainBundle\Pagination;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;

class FilterParamConverter implements ParamConverterInterface
{
    /** @var TranslatorInterface */
    private $translator;

    /** @var array */
    private $options;

    public function __construct(TranslatorInterface $translator, array $options = [])
    {
        $this->translator = $translator;
        $this->options = $options + [
            'dql_alias' => null,
            'page_class' => Page::class,
        ];
    }

    public function apply(Request $request, ParamConverter $configuration): bool
    {
        $options = $configuration->getOptions() + $this->options;
        $configuration->setClass($options['page_class']);
        (new OrderByParamConverter($this->translator, $options))->apply($request, $configuration);
        $orderBy = $request->attributes->get($configuration->getName());
        (new PageParamConverter($this->translator, $options))->apply($request, $configuration);
        $page = $request->attributes->get($configuration->getName());
        $filter = new Filter($orderBy, $page, $options['dql_alias']);
        $request->attributes->set($configuration->getName(), $filter);

        return true;
    }

    public function supports(ParamConverter $configuration): bool
    {
        return $configuration->getClass() === Filter::class;
    }
}
