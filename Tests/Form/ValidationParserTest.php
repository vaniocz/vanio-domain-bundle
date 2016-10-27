<?php
namespace Vanio\DomainBundle\Tests\Form;

use PHPUnit\Framework\TestCase;
use Vanio\DomainBundle\Assert\Validation;
use Vanio\DomainBundle\Form\ValidationTokenParser;
use Vanio\DomainBundle\Tests\Fixtures\Foo;

class ValidationParserTest extends TestCase
{
    function test_parsing_validation_rules()
    {
        $this->assertSame(
            [
                [
                    'class' => Validation::class,
                    'method' => 'notBlank',
                    'property_path' => 'foo',
                    'message' => 'not_blank_message',
                ], [
                    'class' => Validation::class,
                    'method' => 'notBlank',
                    'property_path' => null,
                    'message' => 'not_blank_message',
                ], [
                    'class' => Validation::class,
                    'method' => 'same',
                    'property_path' => 'property_path',
                    'value2' => 'value',
                    'message' => 'same_message',
                ], [
                    'class' => Validation::class,
                    'method' => 'greaterThan',
                    'property_path' => 'bar',
                    'limit' => 10,
                    'message' => 'greater_than_message',
                ], [
                    'class' => Validation::class,
                    'method' => 'lessThan',
                    'property_path' => 'bar',
                    'limit' => 100.0,
                    'message' => 'less_than_message',
                ],
            ],
            (new ValidationTokenParser)->parseValidationRules(Foo::class)
        );
    }
}
