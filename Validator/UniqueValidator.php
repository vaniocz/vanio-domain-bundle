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
            throw new UnexpectedTypeException($constraint, __NAMESPACE__ . '\Unique');
        }

        $fields = $constraint->fields;
        if (!is_array($fields)) {
            $fields = [ $fields ];
        }

        $accessor = PropertyAccess::createPropertyAccessor();

        $criteria = [];
        foreach ($fields as $objectField => $entityField) {
            $criteria[$entityField] = $accessor->getValue(
                $object,
                is_numeric($objectField) ? $entityField : $objectField
            );
        }

        $em = $this->registry->getManagerForClass($constraint->class);
        if (!$em) {
            throw new ConstraintDefinitionException(sprintf('Unable to find the object manager associated with an entity of class "%s".', $constraint->class));
        }

        $repository = $em->getRepository($constraint->class);
        $result = $repository->findBy($criteria);

        if (count($result) === 0) {
            return;
        }


        $id = $constraint->id;
        if (!is_null($id) && count($result) === 1 &&
            $em->getReference($constraint->class, $accessor->getValue($object, $id)) === current($result)) {
            return;
        }

        $this->context->buildViolation($constraint->message)
            ->setCode(Unique::NOT_UNIQUE_ERROR)
            ->addViolation();
    }
}
