<?php
namespace Vanio\DomainBundle\Assert;

use Assert\Assertion as BaseAssertion;
use Assert\AssertionFailedException;

class Assertion extends BaseAssertion
{
    /**
     * @param mixed $value
     * @param array $values
     * @param string|callable|null $message
     * @param string|null $propertyPath
     * @return bool
     * @throws AssertionFailedException
     */
    public static function inArray($value, array $values, $message = null, $propertyPath = null): bool
    {
        if (!in_array($value, $values)) {
            $message = sprintf(
                static::generateMessage($message ?: 'Value "%s" is not an element of the valid values: %s'),
                static::stringify($value),
                implode(', ', array_map([get_called_class(), 'stringify'], $values))
            );

            throw static::createException($value, $message, static::INVALID_VALUE_IN_ARRAY, $propertyPath, [
                'values' => $values,
            ]);
        }

        return true;
    }

    /**
     * @param mixed $value
     * @param string|callable|null $message
     * @param string|null $propertyPath
     * @return bool
     * @throws AssertionFailedException
     */
    public static function supportedImageFile($value, $message = null, $propertyPath = null): bool
    {
        static::file((string) $value);
        static::notEmpty(@getimagesize($value)[0], $message ?: 'Unknown image format.', $propertyPath);

        return true;
    }
}
