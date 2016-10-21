<?php
namespace Vanio\DomainBundle\Tests\Assert;

use PHPUnit\Framework\TestCase;
use Vanio\DomainBundle\Assert\ValidationException;

class ValidationExceptionTest extends TestCase
{
    function test_getting_property_path()
    {
        $this->assertSame(
            'property_path',
            (new ValidationException('message', 0, 'property_path', null))->getPropertyPath()
        );
    }

    function test_getting_value()
    {
        $this->assertSame('value', (new ValidationException('message', 0, null, 'value'))->getValue());
    }

    function test_getting_constraints()
    {
        $constraints = ['foo' => 'foo', 'bar' => 'bar'];
        $this->assertSame(
            $constraints,
            (new ValidationException('message', 0, null, null, $constraints))->getConstraints()
        );
    }

    function test_getting_message_template()
    {
        $this->assertSame(
            'message',
            (new ValidationException('message', 0, null, null))->getMessageTemplate()
        );

        $this->assertSame(
            'message {{ foo }}',
            (new ValidationException('message {{ foo }}', 0, null, null))->getMessageTemplate()
        );
    }

    function test_getting_message_parameters()
    {
        $foo = new Foo;
        $validationException = new ValidationException('message', 0, null, 'value', [
            'foo' => $foo,
            'bar' => 'bar',
            'baz' => new \stdClass(),
        ]);
        $this->assertSame(
            [
                '{{ value }}' => 'value',
                '{{ foo }}' => $foo,
                '{{ bar }}' => 'bar',
            ],
            $validationException->getMessageParameters()
        );
    }

    function test_getting_plain_message()
    {
        $this->assertSame(
            'message',
            (new ValidationException('message', 0, null, null))->getMessageTemplate()
        );
    }

    function test_getting_interpolated_message()
    {
        $foo = new Foo;
        $validationException = new ValidationException('Message {{ foo }} {{ bar }} {{ baz }}.', 0, null, 'value', [
            'foo' => $foo,
            'bar' => 'bar',
            'baz' => new \stdClass(),
        ]);
        $this->assertSame('Message foo bar {{ baz }}.', $validationException->getMessage());
    }
}

class Foo
{
    public function __toString(): string
    {
        return 'foo';
    }
}
