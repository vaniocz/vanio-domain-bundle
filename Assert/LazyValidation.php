<?php
namespace Vanio\DomainBundle\Assert;

use Assert\LazyAssertion;
use Vanio\Stdlib\Objects;

class LazyValidation extends LazyAssertion
{
    /**
     * @param mixed $value
     * @param string|null $propertyPath
     * @param string|null $defaultMessage
     * @return $this
     */
    public function that($value, $propertyPath, $defaultMessage = null)
    {
        parent::that($value, $propertyPath, $defaultMessage);
        $assertionChain = Validate::that($value, $defaultMessage, $propertyPath);
        Objects::setPropertyValue($this, 'currentChain', $assertionChain, parent::class);

        return $this;
    }
}
