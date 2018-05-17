<?php
namespace Vanio\DomainBundle\Request;

use Doctrine\Common\Persistence\ManagerRegistry;
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

    /** @var ManagerRegistry|null */
    private $registry;

    public function __construct(
        UrlGeneratorInterface $urlGenerator,
        UrlGeneratorInterface $defaultUrlGenerator = null,
        ManagerRegistry $registry = null
    ) {
        $this->urlGenerator = $urlGenerator;
        $this->configurableUrlGenerator = $this->resolveConfigurableRequirementsUrlGenerator($urlGenerator)
            ?: $this->resolveConfigurableRequirementsUrlGenerator($defaultUrlGenerator);
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
        $parameters = $request->query->all() + $request->request->all();
        $additionalAttributes = $this->resolveAdditionalAttributes($attributes);

        if (array_key_exists($name, $attributes) && !isset($additionalAttributes[$name])) {
            return;
        } elseif (!$this->isEntityClass($class, $options)) {
            if (array_key_exists($name, $parameters)) {
                $attributes = [$name => $parameters[$name]] + $attributes;
            }
        } else {
            if (isset($options['mapping'])) {
                $attributes += array_diff_key(
                    array_intersect_key($parameters, $options['mapping']),
                    array_flip($options['exclude'] ?? [])
                );
            } elseif (isset($options['id'])) {
                $attributes = array_intersect_key($parameters, array_flip((array) $options['id'])) + $attributes;
            } elseif (!array_key_exists('id', $attributes) && array_key_exists($name, $parameters)) {
                $attributes = [$name => $parameters[$name]] + $attributes;
            } elseif (array_key_exists('id', $parameters)) {
                $attributes = ['id' => $parameters['id']] + $attributes;
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

    private function resolveAdditionalAttributes(array $attributes): array
    {
        if (!isset($attributes['_route'])) {
            return [];
        }

        $additionalAttributes = [];
        $routeParameters = array_fill_keys(array_keys($attributes['_route_params']), '__UNIQUE_DEFAULT_VALUE__');

        if ($this->configurableUrlGenerator) {
            $isStrictRequirements = $this->configurableUrlGenerator->isStrictRequirements();
            $this->configurableUrlGenerator->setStrictRequirements(null);
        }

        $url = $this->urlGenerator->generate($attributes['_route'], $routeParameters);

        if (isset($isStrictRequirements)) {
            $this->configurableUrlGenerator->setStrictRequirements($isStrictRequirements);
        }

        parse_str(parse_url($url, PHP_URL_QUERY), $additionalAttributes);

        return $additionalAttributes;
    }

    private function isEntityClass(string $class, array $options): bool
    {
        if ($class === null || $this->registry === null || !count($this->registry->getManagers())) {
            return false;
        }

        $entityManager = isset($options['entity_manager'])
            ? $this->registry->getManager($options['entity_manager'])
            : $this->registry->getManagerForClass($class);

        return $entityManager ? !$entityManager->getMetadataFactory()->isTransient($class) : false;
    }
}
