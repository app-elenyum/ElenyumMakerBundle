<?php

namespace Elenyum\Maker\Service\Module;

use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\Console\EntityManagerProvider;
use Doctrine\ORM\Tools\SchemaTool;

class ServiceDoctrineSchemaUpdate
{
    public function __construct(
        private readonly EntityManagerProvider $entityManagerProvider,
    ) {
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

        $config = $this->getConfigs($paths);

        $em = new EntityManager($em->getConnection(), $config);

        $metadatas = $em->getMetadataFactory()->getAllMetadata();
        $schemaTool = new SchemaTool($em);

        $sqls = $schemaTool->getUpdateSchemaSql($metadatas);

        $schemaTool->updateSchema($metadatas);

        return $sqls;
    }
}