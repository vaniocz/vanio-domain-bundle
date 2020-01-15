<?php
namespace Vanio\DomainBundle\Assert;

class Validation extends Assertion
{
    /** @var string */
    protected static $exceptionClass = ValidationException::class;

    /**
     * {@inheritDoc}
     */
    public static function createException($value, $message, $code, $propertyPath = null, array $constraints = [])
    {
        return parent::createException($value, $message, $code, $propertyPath, $constraints);
    }
}
