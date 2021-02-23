<?php
namespace Vanio\DomainBundle\Cli;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Vanio\Stdlib\Objects;
use Vich\UploaderBundle\Mapping\PropertyMapping;
use Vich\UploaderBundle\Mapping\PropertyMappingFactory;
use Vich\UploaderBundle\Metadata\MetadataReader;

class DeleteUnusedUploadedFilesCommand extends Command
{
    /** @var ManagerRegistry */
    private $registry;

    /** @var MetadataReader */
    private $metadataReader;

    /** @var PropertyMappingFactory */
    private $propertyMappingFactory;

    public function __construct(
        ManagerRegistry $registry,
        MetadataReader $metadataReader,
        PropertyMappingFactory $propertyMappingFactory
    ) {
        parent::__construct();
        $this->registry = $registry;
        $this->metadataReader = $metadataReader;
        $this->propertyMappingFactory = $propertyMappingFactory;
    }

    protected function configure(): void
    {
        $this
            ->setName('vanio:delete-unused-uploaded-files')
            ->addOption('entity-manager','em', InputOption::VALUE_OPTIONAL, 'Entity manager name')
            ->addOption('force',
                'f',
                InputOption::VALUE_NONE,
                'Whether to delete files mapped without "delete_on_remove" option.'
            );
    }

    public function execute(InputInterface $input, OutputInterface $output): void
    {
        $deletedFilesCount = 0;
        $output->writeln('Deleting unused uploaded files.');

        foreach ($this->collectMappings($input->getOption('entity-manager')) as $path => $mappings) {
            $criteriaMapping = [];

            foreach ($mappings as $mapping) {
                assert($mapping['mapping'] instanceof PropertyMapping);
                $config = Objects::getPropertyValue($mapping['mapping'], 'mapping', PropertyMapping::class);

                if (empty($config['delete_on_remove']) && !$input->getOption('force')) {
                    continue 2;
                }

                $filenameProperty = $mapping['mapping']->getFileNamePropertyName();
                $criteriaMapping[$mapping['class']][$filenameProperty] = $filenameProperty;
            }

            foreach (new \DirectoryIterator($path) as $file) {
                if ($file->isDot()) {
                    continue;
                } elseif ($this->shouldDeleteFile($file->getFilename(), $criteriaMapping)) {
                    unlink($file->getPathname());
                    $output->writeln($file->getPathname(), OutputInterface::VERBOSITY_VERBOSE);
                    $deletedFilesCount++;
                }
            }
        }

        $output->writeln("<info>{$deletedFilesCount} unused uploaded files have been deleted.</info>");
    }

    /**
     * @return mixed[]
     */
    private function collectMappings(?string $entityManagerName = null): array
    {
        $mappings = [];

        foreach ($this->getAllMetadata($entityManagerName) as $classMetadata) {
            if (!$this->metadataReader->isUploadable($classMetadata->name)) {
                continue;
            }

            foreach ($this->metadataReader->getUploadableFields($classMetadata->name) as $field) {
                $property = $field['propertyName'];
                $mapping = $this->propertyMappingFactory->fromField(null, $property, $classMetadata->name);
                $path = realpath($mapping->getUploadDestination());

                if ($path === false) {
                    continue;
                }

                $mappings[$path][] = [
                    'class' => $classMetadata->name,
                    'property' => $property,
                    'mapping' => $mapping,
                ];
            }
        }

        return $mappings;
    }

    private function shouldDeleteFile(string $filename, array $criteriaMapping): bool
    {
        foreach ($criteriaMapping as $class => $properties) {
            $queryBuilder = $this->getRepository($class)->createQueryBuilder('e')
                ->select('1')
                ->setParameter('filename', $filename)
                ->setMaxResults(1);

            foreach ($properties as $property) {
                $queryBuilder->orWhere("e.{$property} = :filename");
            }

            if ($queryBuilder->getQuery()->getOneOrNullResult()) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return mixed[]
     */
    private function getAllMetadata(?string $entityManagerName = null): array
    {
        return $this->getEntityManager($entityManagerName)->getMetadataFactory()->getAllMetadata();
    }

    private function getEntityManager(?string $entityManagerName = null): EntityManager
    {
        return $this->registry->getManager($entityManagerName);
    }

    private function getRepository(string $class): EntityRepository
    {
        return $this->registry->getManagerForClass($class)->getRepository($class);
    }
}
