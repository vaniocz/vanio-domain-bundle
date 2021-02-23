<?php
namespace Vanio\DomainBundle\Request;

use BeSimple\I18nRoutingBundle\Routing\Loader\AnnotatedRouteControllerLoader;
use Doctrine\Persistence\ManagerRegistry;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\ConfigurableRequirementsInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Router;

class GetPostParamConverter implements ParamConverterInterface
{
    /** @var UrlGeneratorInterface */
    private $urlGenerator;

    /** @var ConfigurableRequirementsInterface|null */
    private $configurableUrlGenerator;

    /** @var AnnotatedRouteControllerLoader|null */
    private $annotatedRouteControllerLoader;

    /** @var ManagerRegistry|null */
    private $registry;

    public function __construct(
        UrlGeneratorInterface $urlGenerator,
        UrlGeneratorInterface $defaultUrlGenerator = null,
        AnnotatedRouteControllerLoader $annotatedRouteControllerLoader = null,
        ManagerRegistry $registry = null
    ) {
        $this->urlGenerator = $urlGenerator;
        $this->configurableUrlGenerator = $this->resolveConfigurableRequirementsUrlGenerator($urlGenerator)
            ?: $this->resolveConfigurableRequirementsUrlGenerator($defaultUrlGenerator);
        $this->annotatedRouteControllerLoader = $annotatedRouteControllerLoader;
        $this->registry = $registry;
    }

    public function supports(ParamConverter $configuration): bool
    {
        return true;
    }

    public function apply(Request $request, ParamConverter $configuration): void
    {
        $name = $configuration->getName();

        if ($name[0] === '_') {
            return;
        }

        $class = $configuration->getClass();
        $options = $configuration->getOptions();
        $attributes = $request->attributes->all();
        $routeParameters = $this->resolveRouteParameters($attributes);
        $parameters = array_intersect_key($attributes, $routeParameters)
            + $request->query->all()
            + $request->request->all();

        if (!$class || !$this->isEntityClass($class, $options)) {
            if (array_key_exists($name, $parameters)) {
                $attributes[$name] = $parameters[$name];
            }
        } else {
            if (isset($options['mapping'])) {
                $attributes = array_diff_key(
                    array_intersect_key($parameters, $options['mapping']),
                    array_flip($options['exclude'] ?? [])
                ) + $attributes;
            } elseif (isset($options['id'])) {
                $attributes = array_intersect_key($parameters, array_flip((array) $options['id'])) + $attributes;
            } elseif (!isset($routeParameters['id']) && array_key_exists($name, $parameters)) {
                $attributes[$name] = $parameters[$name];
            } elseif (array_key_exists('id', $parameters)) {
                $attributes['id'] = $parameters['id'];
            }
        }

        $request->attributes->replace($attributes);
    }

    /**
     * @param UrlGeneratorInterface $urlGenerator
     * @return UrlGeneratorInterface|null
     */
    private function resolveConfigurableRequirementsUrlGenerator(UrlGeneratorInterface $urlGenerator)
    {
        if ($urlGenerator instanceof ConfigurableRequirementsInterface) {
            return $urlGenerator;
        } elseif ($urlGenerator instanceof Router) {
            return $this->resolveConfigurableRequirementsUrlGenerator($urlGenerator->getGenerator());
        }

        return null;
    }

    /**
     * Returns an array of route parameters which placeholders arepresent in route definition.
     * @param mixed[] $attributes
     * @return string[]
     */
    private function resolveRouteParameters(array $attributes): array
    {
        if (!isset($attributes['_route'])) {
            return [];
        }

        $routeParameters = array_keys($attributes['_route_params']);
        $routeParameters = array_combine($routeParameters, $routeParameters);

        if (!$this->annotatedRouteControllerLoader) {
            return $routeParameters;
        }

        // AnnotatedRouteControllerLoader from BeSimpleI18nRoutingBundle loads routes differently than the default one from Symfony.
        // It populates _route_params with all default parameter values from controller action even with ones which placeholders are not present in route definition.
        // The following workaround relies on the fact that URL generator generates URL with parameters which placeholders are not present in route definition as query string.
        // @see https://github.com/BeSimple/BeSimpleI18nRoutingBundle/blob/83d2cf7c9ba6e6e3caed5d063b185ae54645eeee/src/Routing/Loader/AnnotatedRouteControllerLoader.php#L43
        // @see https://github.com/symfony/symfony/blob/8ab7077225eadee444ba76c83c667688763c56fb/src/Symfony/Component/Routing/Loader/AnnotationClassLoader.php#L202

        if ($this->configurableUrlGenerator) {
            $isStrictRequirements = $this->configurableUrlGenerator->isStrictRequirements();
            $this->configurableUrlGenerator->setStrictRequirements(null);
        }

        $url = $this->urlGenerator->generate($attributes['_route'], $routeParameters);

        if (isset($isStrictRequirements)) {
            $this->configurableUrlGenerator->setStrictRequirements($isStrictRequirements);
        }

        parse_str(parse_url($url, PHP_URL_QUERY), $additionalAttributes);

        return array_diff_key($routeParameters, $additionalAttributes);
    }

    private function isEntityClass(string $class, array $options): bool
    {
        if (!$this->registry || !$this->registry->getManagers()) {
            return false;
        }

        $entityManager = isset($options['entity_manager'])
            ? $this->registry->getManager($options['entity_manager'])
            : $this->registry->getManagerForClass($class);

        return $entityManager ? !$entityManager->getMetadataFactory()->isTransient($class) : false;
    }
}
