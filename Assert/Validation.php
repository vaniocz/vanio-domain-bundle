<?php
namespace Vanio\DomainBundle\Assert;

class Validation extends Assertion
{
    /** @var string */
    protected static $exceptionClass = ValidationException::class;
}
