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
            throw new UnexpectedTypeException($constraint, __NAMESPACE__ . '\Unique');
        }

        $em = $this->registry->getManagerForClass($constraint->relatedEntity);
        if (!$em) {
            throw new ConstraintDefinitionException(sprintf('Unable to find the object manager associated with an entity of class "%s".', $constraint->relatedEntity));
        }
        $repository = $em->getRepository($constraint->relatedEntity);

        $accessor = PropertyAccess::createPropertyAccessor();
        $relatedField = $constraint->relatedField ? $constraint->relatedField : $constraint->field;
        $relatedValue = $accessor->getValue($object, $constraint->field);

        if (is_callable([ $repository, 'existsBy' ])) {
            $result = $repository->existsBy([
                $relatedField => $relatedValue,
            ]);
        }
        else {
            $result = $repository->findBy([
                $relatedField => $relatedValue,
            ]);
        }

        if ($result) {
            $this->context->buildViolation($constraint->message)
                ->setCode(NotReferenced::IS_REFERENCED_ERROR)
                ->addViolation();
        }
    }
}
