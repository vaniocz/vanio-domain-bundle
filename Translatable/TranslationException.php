<?php
namespace Vanio\DomainBundle\Translatable;

use Doctrine\Common\Util\ClassUtils;

class TranslationException extends \Exception
{
    public const INVALID_CLASS = 1;
    public const EMPTY_LOCALE = 2;
    public const DUPLICATE = 3;
    public const CANNOT_REASSIGN = 4;
    public const CANNOT_OVERWRITE_LOCALE = 5;

    public static function invalidClass(Translation $translation, Translatable $translatable): self
    {
        $message = sprintf(
            'Invalid translation class "%s". Translatable of class "%s" must contain only translations of class "%s".',
            ClassUtils::getClass($translation),
            ClassUtils::getClass($translatable),
            $translatable::translationClass()
        );

        return new self($message, self::INVALID_CLASS);
    }

    public static function emptyLocale(Translation $translation): self
    {
        $message = sprintf(
            'Cannot use translation of class "%s" with empty locale.',
            ClassUtils::getClass($translation)
        );

        return new self($message, self::EMPTY_LOCALE);
    }

    public static function duplicate(Translation $translation): self
    {
        $message = sprintf(
            'Translation of class "%s" with locale "%s" already exists.',
            ClassUtils::getClass($translation),
            $translation->locale()
        );

        return new self($message, self::DUPLICATE);
    }

    public static function cannotReassign(Translation $translation): self
    {
        $message = sprintf(
            'Trying to assign translation of class "%s" to a different translatable of class "%s". Reassigning translations is forbidden.',
            ClassUtils::getClass($translation),
            ClassUtils::getClass($translation->translatable())
        );

        return new self($message, self::CANNOT_REASSIGN);
    }

    public static function cannotOverwriteLocale(Translation $translation, string $locale): self
    {
        $message = sprintf(
            'Trying to overwrite locale "%s" with locale "%s". Overwriting locales of already assigned translations is forbidden.',
            $translation->locale(),
            $locale
        );

        return new self($message, self::CANNOT_OVERWRITE_LOCALE);
    }
}
