<?php
namespace Vanio\DomainBundle\Assert;

use Assert\Assert;

class Validate extends Assert
{
    /** @var string */
    protected static $assertionClass = Validation::class;

    /** @var string */
    protected static $lazyAssertionExceptionClass = LazyValidationException::class;

    public static function lazy(): LazyValidation
    {
        return (new LazyValidation)->setExceptionClass(static::$lazyAssertionExceptionClass);
    }
}
