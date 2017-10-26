<?php
namespace Vanio\DomainBundle\Doctrine;

use Doctrine\Common\DataFixtures\ReferenceRepository;
use Doctrine\Common\DataFixtures\SharedFixtureInterface;
use Doctrine\Common\Util\ClassUtils;

abstract class SharedFixture implements SharedFixtureInterface
{
    /** @var ReferenceRepository */
    private $referenceRepository;

    public function setReferenceRepository(ReferenceRepository $referenceRepository)
    {
        $this->referenceRepository = $referenceRepository;
    }

    public function hasReference(string $class, string $name): bool
    {
        return $this->referenceRepository->hasReference($this->resolveReferenceName($class, $name));
    }

    /**
     * @param string $class
     * @param string $name
     * @return mixed
     */
    public function getReference(string $class, string $name)
    {
        return $this->referenceRepository->getReference($this->resolveReferenceName($class, $name));
    }

    /**
     * @param string $name
     * @param object $object
     * @return $this
     */
    public function setReference(string $name, $object)
    {
        foreach ($this->resolveReferenceNames($object, $name) as $referenceName) {
            $this->referenceRepository->setReference($referenceName, $object);
        }

        return $this;
    }

    /**
     * @param string $name
     * @param object $object
     * @return $this
     */
    public function addReference(string $name, $object)
    {
        foreach ($this->resolveReferenceNames($object, $name) as $referenceName) {
            $this->referenceRepository->addReference($referenceName, $object);
        }

        return $this;
    }

    private function resolveReferenceNames($object, string $name): array
    {
        $referenceNames = [];
        $reflectionClass = new \ReflectionClass(ClassUtils::getClass($object));

        do {
            $referenceNames[] = $this->resolveReferenceName($reflectionClass->name, $name);
        } while ($reflectionClass = $reflectionClass->getParentClass());

        return $referenceNames;
    }

    /**
     * @param object|string $class
     * @param string $name
     * @return string
     */
    private function resolveReferenceName($class, string $name): string
    {
        return sprintf('%s#%s', ClassUtils::getRealClass(is_object($class) ? get_class($class) : $class), $name);
    }
}
