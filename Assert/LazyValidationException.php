<?php
namespace Vanio\DomainBundle\Assert;

use Assert\LazyAssertionException;

/**
 * @method ValidationException[] getErrorExceptions
 */
class LazyValidationException extends LazyAssertionException
{
    /**
     * @param ValidationException[] $errors
     * @return self
     */
    public static function fromErrors(array $errors)
    {
        $message = sprintf("The following %d validations failed:\n", count($errors));
        $i = 1;

        foreach ($errors as $error) {
            $message .= sprintf("%d) %s: %s\n", $i++, $error->getPropertyPath(), $error->getMessage());
        }

        return new self($message, $errors);
    }
}
