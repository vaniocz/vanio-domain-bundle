<?php
namespace Vanio\DomainBundle\Tests\Assert;

use PHPUnit\Framework\TestCase;
use Vanio\DomainBundle\Assert\Validation;
use Vanio\DomainBundle\Assert\ValidationException;

class ValidationTest extends TestCase
{
    function test_throwing_validation_exception()
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Number "0" was expected to be at least "1" and at most "2".');
        $message = 'Number "{{ value }}" was expected to be at least "{{ min }}" and at most "{{ max }}".';
        Validation::range(0, 1, 2, $message);
    }
}
