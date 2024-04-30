<?php

namespace Elenyum\Maker\Service\Module\Handler;

use Elenyum\Maker\Service\ElenyumMakerBaseService;
use Nette\PhpGenerator\PhpNamespace;
use Nette\PhpGenerator\Printer;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class ServiceCreateServiceHandler implements ServiceCreateInterface
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

        $version = $data['version_namespace'];
        $entityName = $data['entity_name'];
        $serviceName = $entityName.'Service';
        $moduleName = $data['module_name'];
        $dirServiceFile = $path.'/'.$moduleName.'/'.$version.'/Service/'.$serviceName.'.php';
        $phpNamespace = $this->createService($namespace, $data);
        $repositoryFileData = $this->printNamespace($phpNamespace);

        $operation = 'created';
        if (file_exists($dirServiceFile)) {
            $operation = 'updated';
        }
        $this->filesystem->dumpFile($dirServiceFile, $repositoryFileData);

        return [[
            'module' => $moduleName,
            'version' => $data['version'],
            'operation' => $operation,
            'type' => 'service',
            'file' => $dirServiceFile,
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

    private function createService(string $baseNamespace, array $data): PhpNamespace
    {
        $version = str_replace('.', '_', $data['version']);
        $entityName = ucfirst($data['entity_name']);
        $repositoryName = $entityName.'Repository';
        $serviceName = $entityName.'Service';
        $moduleName = ucfirst($data['module_name']);
        $rootNamespace = ucfirst($baseNamespace).'\\'.$moduleName.'\\'.ucfirst($version);

        $fullNamespace = $rootNamespace.'\\Service';
        $namespaceToRepository = $rootNamespace.'\\Repository\\' . $repositoryName;

        $namespace = new PhpNamespace($fullNamespace);
        $namespace->addUse(ElenyumMakerBaseService::class);
        $namespace->addUse($namespaceToRepository);
        $class = $namespace->addClass($serviceName);

        $class->setExtends('ElenyumMakerBaseService');
        $constructor = $class->addMethod('__construct');

        $constructor->addParameter('repository')->setType($repositoryName);
        $constructor->addBody('$this->repository = $repository;');

        return $namespace;
    }
}