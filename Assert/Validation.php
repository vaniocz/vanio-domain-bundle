<?php
namespace Vanio\DomainBundle\Assert;

use Assert\Assertion;

class Validation extends Assertion
{
    /** @var string */
    protected static $exceptionClass = ValidationException::class;
}
