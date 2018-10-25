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
        return (new LazyValidation)
            ->setAssertClass(static::class)
            ->setExceptionClass(static::$lazyAssertionExceptionClass);
    }

    public static function assertionClass(): string
    {
        return static::$assertionClass;
    }
}
