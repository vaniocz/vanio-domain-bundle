<?php
namespace Vanio\DomainBundle\Tests\Fixtures;

use Vanio\DomainBundle\Assert\Validation;

class Foo
{
    public function __construct($foo, $bar)
    {
        Validation::notBlank($foo, 'not_blank_message');
        Validation::notBlank('foo', 'not_blank_message');
        Validation::same($bar, 'value', 'same_message', 'property_path');
        Validation::greaterThan($bar, 10, 'greater_than_message');
        Validation::lessThan($bar, 100.0, 'less_than_message');
    }
}

Validation::notBlank('foo', 'not_blank_message', 'property_path');
