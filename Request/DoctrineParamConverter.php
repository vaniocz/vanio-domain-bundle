<?php
namespace Vanio\DomainBundle\Request;

use Doctrine\DBAL\Types\ConversionException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\DoctrineParamConverter as BaseDoctrineParamConverter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Vanio\Stdlib\Strings;

class DoctrineParamConverter extends BaseDoctrineParamConverter
{
    public function apply(Request $request, ParamConverter $configuration): bool
    {
        try {
            return parent::apply($request, $configuration);
        } catch (NotFoundHttpException $e) {
            throw new NotFoundHttpException(sprintf('%s not found.', Strings::baseName($configuration->getClass())));
        } catch (ConversionException $e) {
            preg_match('~Could not convert database value "(.+)" to Doctrine Type (.+)~', $e->getMessage(), $matches);

            throw $matches
                ? new BadRequestHttpException(sprintf('Invalid %s value "%s".', $matches[2], $matches[1]), $e)
                : $e;
        }
    }
}
