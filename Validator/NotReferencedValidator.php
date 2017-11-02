<?php
namespace Vanio\DomainBundle\Validator;

use Doctrine\Common\Persistence\ManagerRegistry;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class NotReferencedValidator extends ConstraintValidator
{
    /** @var ManagerRegistry */
    private $registry;

    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @param mixed $object
     * @param Constraint $constraint
     */
    public function validate($object, Constraint $constraint)
    {
        if (!$constraint instanceof NotReferenced) {
            throw new UnexpectedTypeException($constraint, NotReferenced::class);
        } elseif (!$entityManager = $this->registry->getManagerForClass($constraint->relatedEntity)) {
            throw new ConstraintDefinitionException(sprintf(
                'Unable to find the object manager associated with an entity of class "%s".',
                $constraint->relatedEntity
            ));
        }

        $repository = $entityManager->getRepository($constraint->relatedEntity);
        $relatedField = $constraint->relatedField ? $constraint->relatedField : $constraint->field;
        $criteria = [$relatedField => PropertyAccess::createPropertyAccessor()->getValue($object, $constraint->field)];
        $result = is_callable([$repository, 'existsBy'])
            ? $repository->existsBy($criteria)
            : $repository->findBy($criteria);

        if ($result) {
            $this->context->buildViolation($constraint->message)
                ->setCode(NotReferenced::IS_REFERENCED_ERROR)
                ->addViolation();
        }
    }
}
