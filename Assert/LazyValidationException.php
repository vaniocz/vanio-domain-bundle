<?php
namespace Vanio\DomainBundle\Assert;

use Assert\LazyAssertionException;

/**
 * @method ValidationException[] getErrorExceptions
 */
class LazyValidationException extends LazyAssertionException
{
    /**
     * @param LazyValidationException|LazyValidationException[]|ValidationException|ValidationException[] $childrenErrors
     * @return self
     */
    public static function fromErrors($errors): self
    {
        $errors = self::normalizeErrors($errors);
        $message = sprintf("The following %d validations failed:\n", count($errors));
        $i = 1;

        foreach ($errors as $error) {
            $message .= sprintf("%d) %s: %s\n", $i++, $error->getPropertyPath(), $error->getMessage());
        }

        return new self($message, $errors);
    }

    /**
     * @param LazyValidationException|LazyValidationException[]|ValidationException|ValidationException[] $childrenErrors
     * @return self
     */
    public static function fromChildrenErrors(array $childrenErrors): self
    {
        $errors = [];

        foreach ($childrenErrors as $propertyPath => $childErrors) {
            foreach (self::normalizeErrors($childErrors) as $error) {
                $errors[] = new ValidationException(
                    $error->getMessageTemplate(),
                    $error->getCode(),
                    "{$propertyPath}.{$error->getPropertyPath()}",
                    $error->getValue(),
                    $error->getConstraints()
                );
            }
        }

        return self::fromErrors($errors);
    }

    /**
     * @param LazyValidationException|LazyValidationException[]|ValidationException|ValidationException[] $errors
     * @return ValidationException[]
     */
    private static function normalizeErrors($errors): array
    {
        $normalizedErrors = [];

        foreach (is_array($errors) ? $errors : [$errors] as $error) {
            if ($error instanceof LazyValidationException) {
                $normalizedErrors = array_merge($normalizedErrors, $error->getErrorExceptions());
            } else {
                $normalizedErrors[] = $error;
            }
        }

        return $normalizedErrors;
    }
}
