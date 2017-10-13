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

        if ($request->attributes->has($name)) {
            return;
        }

        if ($class === null) {
            if (array_key_exists($name, $parameters)) {
                $attributes += [$name => $parameters[$name]];
            }
        } else {
            if (isset($options['mapping'])) {   // set parameters from mapping
                foreach (array_keys($options['mapping']) as $param) {
                    if (!isset($options['exclude']) || !in_array($param, $options['exclude'])) {
                        if (array_key_exists($param, $parameters)) {
                            $attributes += [$param => $parameters[$param]];
                        }
                    }
                }
            } elseif (isset($options['id'])) {
                $id = $options['id'];
                if (is_array($id)) {    // set ID parameters (array)
                    foreach ($id as $field) {
                        if (array_key_exists($field, $parameters)) {
                            $attributes += [$field => $parameters[$field]];
                        }
                    }
                } else {  // set ID parameter
                    if (array_key_exists($id, $parameters)) {
                        $attributes += [$id => $parameters[$id]];
                    }
                }
            } elseif (array_key_exists($name, $parameters)) {   // set parameter with same name
                $attributes += [$name => $parameters[$name]];
            } elseif (array_key_exists('id', $parameters)) { // fallback to parameter 'id'
                $attributes += ['id' => $parameters['id']];
            }
        }
        $request->attributes->replace($attributes);
    }
}
