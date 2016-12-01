<?php
namespace Vanio\DomainBundle\Doctrine;

use Assert\Assertion;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\Query;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Happyr\DoctrineSpecification\EntitySpecificationRepository;
use Happyr\DoctrineSpecification\Filter\Filter;
use Happyr\DoctrineSpecification\Logic\AndX;
use Happyr\DoctrineSpecification\Query\QueryModifier;
use Happyr\DoctrineSpecification\Result\ResultModifier;

/**
 * @method find($id, int $lockMode = null, int $lockVersion = null)
 */
class EntityRepository extends EntitySpecificationRepository
{
    /**
     * @param mixed $id
     * @param int|null $lockMode
     * @param int|null $lockVersion
     * @throws EntityNotFoundException
     */
    public function get($id, int $lockMode = null, int $lockVersion = null)
    {
        if (!$entity = $this->find($id, $lockMode, $lockVersion)) {
            throw new EntityNotFoundException;
        }

        return $entity;
    }

    public function random()
    {
        return $this->createQueryBuilder('e')
            ->addSelect('RANDOM() as HIDDEN _random')
            ->orderBy('_random')
            ->setMaxResults(1)
            ->getQuery()
            ->getSingleResult();
    }


    /**
     * @param object $entity
     */
    public function add($entity)
    {
        Assertion::isInstanceOf($entity, $this->_entityName, 'Cannot add an instance of "%s" to "%s" repository.');
        $this->assertNotChildEntity($entity, 'add');
        $this->_em->persist($entity);
    }

    /**
     * @param object $entity
     */
    public function remove($entity)
    {
        Assertion::isInstanceOf($entity, $this->_entityName, 'Cannot remove an instance of "%s" from "%s" repository.');
        $this->assertNotChildEntity($entity, 'remove');
        $this->_em->remove($entity);
    }

    public function getQuery($specifications, ResultModifier $modifier = null): Query
    {
        list($specification, $modifier) = $this->mergeSpecifications($specifications, $modifier);

        return parent::getQuery($specification, $modifier);
    }

    public function paginate($specifications, bool $fetchJoinCollection = true): Paginator
    {
        return new Paginator($this->getQuery($specifications), $fetchJoinCollection);
    }

    /**
     * @param mixed $specifications
     * @param ResultModifier|null $modifier
     * @return array
     * @throws \InvalidArgumentException
     */
    private function mergeSpecifications($specifications, ResultModifier $modifier = null): array
    {
        $specifications = is_array($specifications) ? $specifications : [$specifications];
        $and = new AndX;

        foreach ($specifications as $specification) {
            if ($specification instanceof Filter || $specification instanceof QueryModifier) {
                $and->andX($specification);
            }

            if ($specification instanceof ResultModifier) {
                if ($modifier) {
                    throw new \InvalidArgumentException('Only one result modifier can be passed at once.');
                }

                $modifier = $specification;
            }
        }

        return [$and, $modifier];
    }

    /**
     * @param object $entity
     * @param string $action
     */
    private function assertNotChildEntity($entity, string $action)
    {
        $message = sprintf(
            'Cannot %s child entity "%s", write/modify operations through repository are forbidden.',
            $action,
            $this->_entityName
        );
        Assertion::false($entity instanceof ChildEntity, $message);
    }
}
