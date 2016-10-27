<?php
namespace Vanio\DomainBundle\Tests\Form;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraints\GreaterThan;
use Symfony\Component\Validator\Constraints\IdenticalTo;
use Symfony\Component\Validator\Constraints\LessThan;
use Symfony\Component\Validator\Constraints\NotBlank;
use Vanio\DomainBundle\Form\ValidationConstraintsGuesser;
use Vanio\DomainBundle\Form\ValidationTokenParser;
use Vanio\DomainBundle\Tests\Fixtures\Foo;

class ValidationConstraintsGuesserTest extends TestCase
{
    function test_guessing_validation_constraints()
    {
        $validationTypeGuesser = new ValidationConstraintsGuesser(new ValidationTokenParser, [], []);
        $this->assertEquals(
            [
                'foo' => [new NotBlank(['message' => 'not_blank_message'])],
                null => [new NotBlank(['message' => 'not_blank_message'])],
                'bar' => [
                    new GreaterThan(['message' => 'greater_than_message', 'value' => 10]),
                    new LessThan(['message' => 'less_than_message', 'value' => 100.0]),
                ],
                'property_path' => [new IdenticalTo(['message' => 'same_message', 'value' => 'value'])],
            ],
            $validationTypeGuesser->guessValidationConstraints(Foo::class)
        );
    }
}
