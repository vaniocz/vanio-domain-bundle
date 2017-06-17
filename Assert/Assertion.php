<?php
namespace Vanio\DomainBundle\Assert;

use Assert\Assertion as BaseAssertion;
use Assert\InvalidArgumentException;

class Assertion extends BaseAssertion
{
    /**
     * Asserts that value is an existing image file.
     *
     * @param mixed $value
     * @param string|null $message
     * @param string|null $propertyPath
     * @throws InvalidArgumentException
     */
    public static function supportedImageFile($value, string $message = null, string $propertyPath = null)
    {
        static::file((string) $value);
        static::notEmpty(@getimagesize($value)[0], $message ?: 'Unknown image format.', $propertyPath);
    }
}
