<?php
namespace Vanio\DomainBundle\Assert;

use Assert\LazyAssertion;
use Vanio\Stdlib\Objects;

class LazyValidation extends LazyAssertion
{
    /**
     * @param string $exceptionClass
     * @return $this
     */
    public function setExceptionClass($exceptionClass): self
    {
        if (!is_string($exceptionClass)) {
            throw new \LogicException('Exception class must be a string.');
        } elseif (!is_a($exceptionClass, LazyValidationException::class, true)) {
            throw new \LogicException(sprintf(
                '"%s" must be a subclass of "%s".',
                $exceptionClass,
                LazyValidationException::class
            ));
        }

        Objects::setPropertyValue($this, 'exceptionClass', $exceptionClass, parent::class);

        return $this;
    }
}
