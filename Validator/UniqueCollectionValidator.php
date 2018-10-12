<?php
namespace Vanio\DomainBundle\Validator;

use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class UniqueCollectionValidator extends ConstraintValidator
{
    /**
     * @param array|\Traversable $collection
     * @param Constraint $constraint
     */
    public function validate($collection, Constraint $constraint)
    {
        if (!$constraint instanceof UniqueCollection) {
            throw new UnexpectedTypeException($constraint, UniqueCollection::class);
        }

        $accessor = PropertyAccess::createPropertyAccessor();
        $uniqueValues = [];

        foreach ($collection as $index => $value) {
            if ($constraint->propertyPath !== null) {
                $value = $accessor->getValue($value, $constraint->propertyPath);
            }

            if ($value === null && $constraint->ignoreNull) {
                continue;
            } elseif (isset($uniqueValues[(string) $value])) {
                $this->context->buildViolation($constraint->message)
                    ->atPath(sprintf('[%s]%s', $index, $constraint->errorPath ?? $constraint->propertyPath))
                    ->setParameter('{{ value }}', $value)
                    ->setInvalidValue($value)
                    ->setCode(UniqueCollection::NOT_UNIQUE_COLLECTION_ERROR)
                    ->addViolation();
            }

            $uniqueValues[(string) $value] = true;
        }
    }
}
