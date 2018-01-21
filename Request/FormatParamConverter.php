<?php
namespace Vanio\DomainBundle\Request;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\HttpFoundation\Request;

class FormatParamConverter implements ParamConverterInterface
{
    /** @var array */
    private $defaultAcceptedFormats;

    public function __construct(array $defaultAcceptedFormats)
    {
        $this->defaultAcceptedFormats = $defaultAcceptedFormats;
    }

    public function supports(ParamConverter $configuration): bool
    {
        return $configuration->getName() === '_format' || $configuration->getConverter();
    }

    public function apply(Request $request, ParamConverter $configuration): bool
    {
        $formats = array_flip($configuration->getOptions()['accepted_formats'] ?? $this->defaultAcceptedFormats);

        foreach ($request->getAcceptableContentTypes() as $contentType) {
            $format = $request->getFormat($contentType);

            if (isset($formats[$format])) {
                $request->setRequestFormat($format);
                $request->attributes->set($configuration->getName(), $format);

                return true;
            }
        }

        return true;
    }
}
