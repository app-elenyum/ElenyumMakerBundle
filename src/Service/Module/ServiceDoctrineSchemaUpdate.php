<?php

namespace Elenyum\Maker\Service\Module;

use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Driver\XmlDriver;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\Console\EntityManagerProvider;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class ServiceDoctrineSchemaUpdate
{
    /**
     * @var array|null
     */
    private ?array $paths = null;

    /**
     * @var array|null
     */
    private ?array $names = null;

    public function __construct(
        private array $options,
        private readonly EntityManagerProvider $entityManagerProvider,
    ) {
        $included = $this->options['doctrine'] ?? null;
        if ($included !== null) {
            $this->paths = $included['paths'] ?? null;
        }
        if ($included !== null) {
            $this->names = $included['names'] ?? null;
        }
    }

    final protected function getEntityManager(): EntityManagerInterface
    {
        return $this->entityManagerProvider->getDefaultManager();
    }

    public function getConfigs(array $paths): Configuration
    {
        return ORMSetup::createAttributeMetadataConfiguration(
            paths: $paths,
            isDevMode: true,
        );
    }

    public function execute(array $paths): array
    {
        $em = $this->getEntityManager();

        $currentConfig = $em->getConfiguration();
        $currentDriver = $currentConfig->getMetadataDriverImpl();

        if (!empty($this->names)) {
            $paths = array_merge(
                $paths,
                $this->filterPathsByKeywords(
                    current($currentDriver->getDriver()->getDrivers())->getPaths(),
                    $this->names
                )
            );
        }

        if (!empty($this->paths)) {
            $paths = array_merge($paths, $this->paths);
        }

        if (!empty($paths)) {
            $config = $this->getConfigs($paths);
            $em = new EntityManager($em->getConnection(), $config);
        }

        $metadatas = $em->getMetadataFactory()->getAllMetadata();
        $schemaTool = new SchemaTool($em);

        $sqls = $schemaTool->getUpdateSchemaSql($metadatas);

        $schemaTool->updateSchema($metadatas);

        return $sqls;
    }

    private function filterPathsByKeywords(array $paths, array $keywords): array {
        $filteredPaths = [];

        foreach ($paths as $path) {
            foreach ($keywords as $keyword) {
                if (str_contains($path, $keyword)) {
                    $filteredPaths[] = $path;
                    break; // Прекращаем дальнейшие проверки для текущего пути
                }
            }
        }

        return $filteredPaths;
    }

}