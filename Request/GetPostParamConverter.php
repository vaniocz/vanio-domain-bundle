<?php
namespace Vanio\DomainBundle\Request;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\HttpFoundation\Request;

class GetPostParamConverter implements ParamConverterInterface
{
    public function supports(ParamConverter $configuration)
    {
        return true;
    }

    public function apply(Request $request, ParamConverter $configuration)
    {
        $name = $configuration->getName();
        $class = $configuration->getClass();
        $options = $configuration->getOptions();
        $attributes = $request->attributes->all();
        $parameters = $request->query->all() + $request->request->all();

        if (array_key_exists($name, $attributes)) {
            return;
        } elseif ($class === null) {
            if (array_key_exists($name, $parameters)) {
                $attributes += [$name => $parameters[$name]];
            }
        } else {
            if (isset($options['mapping'])) {
                $attributes += array_diff_key(
                    array_intersect_key($parameters, $options['mapping']),
                    array_flip($options['exclude'] ?? [])
                );
            } elseif (isset($options['id'])) {
                $attributes += array_intersect_key($parameters, array_flip((array) $options['id']));
            } elseif (!array_key_exists('id', $attributes) && array_key_exists($name, $parameters)) {
                $attributes += [$name => $parameters[$name]];
            } elseif (array_key_exists('id', $parameters)) {
                $attributes += ['id' => $parameters['id']];
            }
        }

        $request->attributes->replace($attributes);
    }
}
