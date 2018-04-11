<?php
namespace Vanio\DomainBundle\Validator;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
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
        $accessor = PropertyAccess::createPropertyAccessor();
        $criteria = [];
        $errorPath = $constraint->errorPath;

        foreach ((array) $constraint->properties as $objectProperty => $entityProperty) {
            $property = is_numeric($objectProperty) ? $entityProperty : $objectProperty;
            $criteria[$entityProperty] = $accessor->getValue($object, $property);

            if ($errorPath === null) {
                $errorPath = $property;
            }
        }

        /** @var EntityManager $entityManager */
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

        $invalidValue = $criteria[$errorPath] ?? current($criteria);
        $this->context->buildViolation($constraint->message)
            ->atPath($errorPath)
            ->setParameter(
                '{{ value }}',
                $this->formatWithIdentifiers($entityManager, $entityManager->getClassMetadata($class), $invalidValue)
            )
            ->setInvalidValue($invalidValue)
            ->setCode(Unique::NOT_UNIQUE_ERROR)
            ->addViolation();
    }

    /**
     * @param EntityManager $entityManager
     * @param ClassMetadata $class
     * @param mixed $value
     * @return string
     */
    private function formatWithIdentifiers(EntityManager $entityManager, ClassMetadata $class, $value): string
    {
        if (!is_object($value) || $value instanceof \DateTimeInterface) {
            return $this->formatValue($value, self::PRETTY_DATE);
        }

        $idClass = get_class($value);

        if ($class->name !== $idClass) {
            $ids = $entityManager->getMetadataFactory()->hasMetadataFor($idClass)
                ? $entityManager->getClassMetadata($idClass)->getIdentifierValues($value)
                : [];
        } else {
            $ids = $class->getIdentifierValues($value);
        }

        if (!$ids) {
            return sprintf('object("%s")', $idClass);
        }

        foreach ($ids as $property => &$id) {
            $id = sprintf(
                '%s => %s',
                $property,
                is_object($id) && !$id instanceof \DateTimeInterface
                    ? sprintf('object("%s")', get_class($id))
                    : $this->formatValue($id, self::PRETTY_DATE)
            );
        }

        return sprintf('object("%s") identified by (%s)', $idClass, implode(', ', $ids));
    }
}
