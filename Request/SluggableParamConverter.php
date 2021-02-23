<?php
namespace Vanio\DomainBundle\Request;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Vanio\DomainBundle\Translatable\Translatable;
use Vanio\DomainBundle\Translatable\Translation;

class SluggableParamConverter implements ParamConverterInterface
{
    const DEFAULT_OPTIONS = ['property' => 'slug'];

    /** @var ManagerRegistry|null */
    private $doctrine;

    public function __construct(ManagerRegistry $registry = null)
    {
        $this->doctrine = $registry;
    }

    public function supports(ParamConverter $configuration): bool
    {
        $class = $configuration->getClass();

        if (!$this->doctrine || $class === null) {
            return false;
        } elseif ($entityManager = $this->getEntityManager($class)) {
            return !$entityManager->getMetadataFactory()->isTransient($class);
        }

        return false;
    }

    public function apply(Request $request, ParamConverter $configuration): bool
    {
        $name = $configuration->getName();
        $options = $configuration->getOptions() + self::DEFAULT_OPTIONS;
        $parameter = $this->resolveParameter($request, $configuration);
        $slug = $request->attributes->get($parameter);
        $locale = $request->attributes->get('_locale');

        if ($entity = $this->find($configuration->getClass(), $options['property'], $slug, $locale)) {
            $request->attributes->set($name, $entity);

            if ($entity instanceof Translation) {
                $entity = $entity->translatable();
            }

            if ($entity instanceof Translatable) {
                $translatableSlugs = $request->attributes->get('_translatable_slugs', []);
                $translatableSlugs[$parameter] = [
                    'entity' => $entity,
                    'property' => $options['property'],
                ];
                $request->attributes->set('_translatable_slugs', $translatableSlugs);
            }
        } elseif (!$configuration->isOptional()) {
            throw new NotFoundHttpException(sprintf(
                'Unable to find object of class "%s" by slug "%s".',
                $configuration->getClass(),
                $slug
            ));
        }

        return true;
    }

    /**
     * @return string|null
     */
    private function resolveParameter(Request $request, ParamConverter $configuration)
    {
        $attributes = $request->attributes->all();

        if (isset($attributes['parameter'])) {
            return $attributes['parameter'];
        }

        $name = $configuration->getName();

        foreach ([$name, sprintf('%sSlug', $name), sprintf('%s_slug', $name), 'slug'] as $parameter) {
            if (array_key_exists($parameter, $attributes)) {
                return $parameter;
            }
        }

        return null;
    }

    /**
     * @return object|null
     */
    private function find(string $class, string $property, string $slug, string $locale = null)
    {
        $entityManager = $this->getEntityManager($class);
        $classMetadata = $entityManager->getClassMetadata($class);
        $alias = 'e';
        $queryBuilder = $this->getRepository($class)->createQueryBuilder($alias);
        $isTranslatable = is_a($class, Translatable::class, true);
        $isTranslation = is_a($class, Translation::class, true);

        if ($isTranslatable && !$classMetadata->hasField($property)) {
            $translationAlias = "{$alias}_translations";
            $queryBuilder->join("$alias.translations", $translationAlias);
            $alias = $translationAlias;
        }

        if (($isTranslatable || $isTranslation) && $locale !== null) {
            $queryBuilder
                ->where("$alias.locale = :locale")
                ->setParameter('locale', $locale);
        }

        return $queryBuilder
            ->andWhere("$alias.$property = :slug")
            ->setParameter('slug', $slug)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param string $class
     * @return EntityManager|null
     */
    private function getEntityManager(string $class)
    {
        return $this->doctrine->getManagerForClass($class);
    }

    private function getRepository(string $class): EntityRepository
    {
        return $this->getEntityManager($class)->getRepository($class);
    }
}
