<?php
namespace Vanio\DomainBundle\Assert;

use Assert\InvalidArgumentException;

class ValidationException extends InvalidArgumentException
{
    /** @var string */
    private $messageTemplate;

    /** @var array */
    private $messageParameters = [];

    /**
     * @param string $messageTemplate
     * @param int $code
     * @param string|null $propertyPath
     * @param mixed $value
     * @param array $constraints
     */
    public function __construct(
        string $messageTemplate,
        int $code,
        ?string $propertyPath,
        $value,
        array $constraints = []
    ) {
        $this->messageTemplate = $messageTemplate;

        foreach (['value' => $value] + $constraints as $parameter => $value) {
            if ($value === null || is_scalar($value) || method_exists($value, '__toString')) {
                $this->messageParameters["{{ $parameter }}"] = $value;
            }
        }

        $message = strtr($this->messageTemplate, $this->messageParameters);
        parent::__construct($message, $code, $propertyPath, $value, $constraints);
    }

    public function getMessageTemplate(): string
    {
        return $this->messageTemplate;
    }

    public function getMessageParameters(): array
    {
        return $this->messageParameters;
    }
}
