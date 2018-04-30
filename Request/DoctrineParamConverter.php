<?php
namespace Vanio\DomainBundle\Request;

use Doctrine\DBAL\Types\ConversionException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\DoctrineParamConverter as BaseDoctrineParamConverter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class DoctrineParamConverter extends BaseDoctrineParamConverter
{
    public function apply(Request $request, ParamConverter $configuration): bool
    {
        try {
            return parent::apply($request, $configuration);
        } catch (ConversionException $e) {
            throw new BadRequestHttpException($e->getMessage(), $e);
        }
    }
}
