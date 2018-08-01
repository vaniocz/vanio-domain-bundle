<?php
namespace Vanio\DomainBundle\Assert;

use Assert\Assertion as BaseAssertion;
use Assert\AssertionFailedException;

/**
 * @method static bool allNotContain(mixed $string, string $needle, string|callable $message = null, string $propertyPath = null, string $encoding = 'UTF8')
 * @method static bool nullOrNotContain(mixed $string, string $needle, string|callable $message = null, string $propertyPath = null, string $encoding = 'UTF8')
 * @method static bool allSupportedImageFile(mixed $value, string|callable $message = null, string $propertyPath = null)
 * @method static bool nullOrSupportedImageFile(mixed $value, string|callable $message = null, string $propertyPath = null)
 */
class Assertion extends BaseAssertion
{
    const INVALID_STRING_NOT_CONTAIN = 1000;
    const INVALID_SUPPORTED_IMAGE_FILE = 1001;

    /**
     * @param mixed $value
     * @param array $values
     * @param string|callable|null $message
     * @param string|null $propertyPath
     * @return bool
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
     * @param mixed $string
     * @param string $needle
     * @param string|callable|null $message
     * @param string|null $propertyPath
     * @param string $encoding
     * @return bool
     */
    public static function notContain(
        $string,
        string $needle,
        $message = null,
        $propertyPath = null,
        string $encoding = 'UTF8'
    ): bool {
        static::string($string, $message, $propertyPath);

        if (mb_strpos($string, $needle, null, $encoding)) {
            $message = sprintf(
                static::generateMessage($message ?: 'Value "%s" contains "%s".'),
                static::stringify($string),
                static::stringify($needle)
            );

            throw static::createException($string, $message, static::INVALID_STRING_NOT_CONTAIN, $propertyPath, [
                'needle' => $needle,
                'encoding' => $encoding,
            ]);
        }

        return true;
    }

    /**
     * @param mixed $value
     * @param string|callable|null $message
     * @param string|null $propertyPath
     * @return bool
     */
    public static function supportedImageFile($value, $message = null, $propertyPath = null): bool
    {
        static::file((string) $value);

        if (empty(@getimagesize($value)[0])) {
            $message = \sprintf(
                static::generateMessage($message ?: 'Unknown image format.'),
                static::stringify($value)
            );

            throw static::createException($value, $message, static::INVALID_SUPPORTED_IMAGE_FILE, $propertyPath);
        }

        return true;
    }
}
