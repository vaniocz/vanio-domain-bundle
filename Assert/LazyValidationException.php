<?php
namespace Vanio\DomainBundle\Assert;

class LazyValidationException extends ValidationException
{
    /** @var ValidationException[] */
    private $errors = [];

    /**
     * @param string $message
     * @param ValidationException[] $errors
     */
    public function __construct(string $message, array $errors)
    {
        parent::__construct($message, 0, null, null);
        $this->errors = $errors;
    }

    /**
     * @param ValidationException|ValidationException[] $errors
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
     * @param ValidationException|ValidationException[] $childrenErrors
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
     * @return ValidationException[]
     */
    public function getErrorExceptions(): array
    {
        return $this->errors;
    }

    /**
     * @param ValidationException|ValidationException[] $childrenErrors
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
