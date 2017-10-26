<?php
namespace Vanio\DomainBundle\Validator;

use Doctrine\Common\Persistence\ManagerRegistry;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class UniqueValidator extends ConstraintValidator
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
        if (!$constraint instanceof Unique) {
            throw new UnexpectedTypeException($constraint, Unique::class);
        }

        $class = $constraint->class;
        $id = $constraint->id;
        $fields = (array) $constraint->fields;
        $accessor = PropertyAccess::createPropertyAccessor();
        $criteria = [];

        foreach ($fields as $objectField => $entityField) {
            $field = is_numeric($objectField) ? $entityField : $objectField;
            $criteria[$entityField] = $accessor->getValue($object, $field);
        }

        if (!$entityManager = $this->registry->getManagerForClass($class)) {
            throw new ConstraintDefinitionException(sprintf(
                'Unable to find the object manager associated with an entity of class "%s".',
                $class
            ));
        }

        if (!$entities = $entityManager->getRepository($class)->findBy($criteria)) {
            return;
        }

        if (
            $id !== null
            && count($entities) === 1
            && $entityManager->getReference($class, $accessor->getValue($object, $id)) === current($entities)
        ) {
            return;
        }

        $this->context->buildViolation($constraint->message)
            ->setCode(Unique::NOT_UNIQUE_ERROR)
            ->addViolation();
    }
}
