<?php
namespace Vanio\DomainBundle\Request;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class GetPostParamConverter implements ParamConverterInterface
{
    /** @var UrlGeneratorInterface */
    private $urlGenerator;

    public function __construct(UrlGeneratorInterface $urlGenerator)
    {
        $this->urlGenerator = $urlGenerator;
    }

    public function supports(ParamConverter $configuration)
    {
        return true;
    }

    public function apply(Request $request, ParamConverter $configuration)
    {
        $name = $configuration->getName();

        if ($name[0] === '_') {
            return;
        }

        $class = $configuration->getClass();
        $options = $configuration->getOptions();
        $attributes = $request->attributes->all();
        $parameters = $request->query->all() + $request->request->all();
        $additionalAttributes = $this->resolveAdditionalAttributes($attributes);

        if (array_key_exists($name, $attributes) && !isset($additionalAttributes[$name])) {
            return;
        } elseif ($class === null) {
            if (array_key_exists($name, $parameters)) {
                $attributes = [$name => $parameters[$name]] + $attributes;
            }
        } else {
            if (isset($options['mapping'])) {
                $attributes += array_diff_key(
                    array_intersect_key($parameters, $options['mapping']),
                    array_flip($options['exclude'] ?? [])
                );
            } elseif (isset($options['id'])) {
                $attributes = array_intersect_key($parameters, array_flip((array) $options['id'])) + $attributes;
            } elseif (!array_key_exists('id', $attributes) && array_key_exists($name, $parameters)) {
                $attributes = [$name => $parameters[$name]] + $attributes;
            } elseif (array_key_exists('id', $parameters)) {
                $attributes = ['id' => $parameters['id']] + $attributes;
            }
        }

        $request->attributes->replace($attributes);
    }

    private function resolveAdditionalAttributes(array $attributes): array
    {
        $additionalAttributes = [];

        if (isset($attributes['_route'])) {
            $routeParameters = array_fill_keys(array_keys($attributes['_route_params']), '__UNIQUE_DEFAULT_VALUE__');
            $url = $this->urlGenerator->generate($attributes['_route'], $routeParameters);
            parse_str(parse_url($url, PHP_URL_QUERY), $additionalAttributes);
        }

        return $additionalAttributes;
    }
}
