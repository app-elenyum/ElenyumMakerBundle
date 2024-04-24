<?php

namespace Elenyum\Maker\Service\Module\Handler;

use Nette\PhpGenerator\PhpNamespace;
use Nette\PhpGenerator\Printer;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class ServiceCreateRepositoryHandler implements ServiceCreateInterface
{
    public function __construct(
        readonly private Filesystem $filesystem,
        readonly private array $options
    ) {
    }

    /**
     * @param array $data
     * @return array - return array with created files structure
     */
    public function create(array $data): array
    {
        $root = $this->options['root'] ?? null;
        if ($root === null) {
            throw new MissingOptionsException('Not defined "root" option');
        }
        $path = $root['path'] ?? null;
        if ($path === null) {
            throw new MissingOptionsException('Not defined "path" option');
        }
        $namespace = $root['namespace'] ?? null;
        if ($namespace === null) {
            throw new MissingOptionsException('Not defined "namespace" option');
        }

        $repositoryFileData = $this->printNamespace(
            $this->createRepository($namespace, $data)
        );

        $version = $data['version_namespace'];
        $entityName = $data['entity_name'];
        $repositoryName = $entityName.'Repository';
        $moduleName = $data['module_name'];
        $dirRepositoryFile = $path.'/'.$moduleName.'/'.$version.'/Repository/'.$repositoryName.'.php';

        $operation = 'created';
        if (file_exists($dirRepositoryFile)) {
            $operation = 'updated';
        }
        $this->filesystem->dumpFile($dirRepositoryFile, $repositoryFileData);
        return [[
            'module' => $moduleName,
            'version' => $data['version'],
            'operation' => $operation,
            'type' => 'repository',
            'file' => $dirRepositoryFile,
        ]];
    }

    /**
     * @param PhpNamespace $namespace
     * @return string
     */
    private function printNamespace(PhpNamespace $namespace): string
    {
        $printer = new Printer(); // or PsrPrinter
        $printer->setTypeResolving(false);
        $printer->linesBetweenMethods = 1;

        return "<?php \n".$printer->printNamespace($namespace);
    }

    private function createRepository(string $baseNamespace, array $data): PhpNamespace
    {
        $version = str_replace('.', '_', $data['version']);
        $entityName = ucfirst($data['entity_name']);
        $repositoryName = $entityName.'Repository';
        $moduleName = ucfirst($data['module_name']);
        $rootNamespace = ucfirst($baseNamespace).'\\'.$moduleName.'\\'.ucfirst($version);


        $fullNamespace = $rootNamespace.'\\Repository';
        $namespaceToEntity = $rootNamespace.'\\Entity\\' . $entityName;

        $namespace = new PhpNamespace($fullNamespace);
        $namespace->addUse('Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository');
        $namespace->addUse('Doctrine\Persistence\ManagerRegistry');
        $namespace->addUse($namespaceToEntity);

        $class = $namespace->addClass($repositoryName);
        $class->addComment(sprintf('
Class %1$sRepository
@package Module\%1$s\Repository

@method %1$s|null find($id, $lockMode = null, $lockVersion = null)
@method %1$s|null findOneBy(array $criteria, array $orderBy = null)
@method %1$s[]    findAll()
@method %1$s[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
        ', $entityName));
        $class->setExtends('ServiceEntityRepository');
        $constructor = $class->addMethod('__construct');
        $constructor->addParameter('registry')->setType('ManagerRegistry');
        $constructor->addBody(sprintf('parent::__construct($registry, %1$s::class);', $entityName));

        return $namespace;
    }
}