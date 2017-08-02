<?php
namespace Vanio\DomainBundle\Validator;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class UniqueValidator extends ConstraintValidator
{
    /** @var EntityManager */
    private $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function validate($entity, Constraint $constraint)
    {
        if (!$constraint instanceof Unique) {
            throw new UnexpectedTypeException($constraint, __NAMESPACE__.'\Unique');
        }

        $fields = $constraint->fields;
        if (!is_array($fields)) {
            $fields = [ $fields ];
        }

        $criteria = [];
        foreach ($fields as $field) {
            $criteria[$field] = $entity->$field();
        }

        $repository = $this->entityManager->getRepository($constraint->class);
        $result = $repository->findBy($criteria);

        if (count($result) == 0) {
            return;
        }

        $id = $constraint->id;
        if ($id && count($result) == 1 && $repository->get($entity->$id()) === current($result)) {
            return;
        }

        $this->context->buildViolation($constraint->message)
            ->setCode(Unique::NOT_UNIQUE_ERROR)
            ->addViolation();
    }
}
