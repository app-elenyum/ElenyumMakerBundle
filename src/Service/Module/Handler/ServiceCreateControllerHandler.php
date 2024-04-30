<?php

namespace Elenyum\Maker\Service\Module\Handler;

use Countable;
use Elenyum\Maker\Service\Module\Controller\ServiceAddControllerInterface;
use Nette\PhpGenerator\PhpNamespace;
use Nette\PhpGenerator\Printer;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class ServiceCreateControllerHandler implements ServiceCreateInterface
{
    public function __construct(
        readonly private Filesystem $filesystem,
        readonly private array $options,
        readonly private Countable $controllerServices,
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
        $prefix = $root['prefix'] ?? null;

        $entityName = $data['entity_name'];
        $version = $data['version_namespace'];
        $moduleName = $data['module_name'];
        $rootNamespace = ucfirst($namespace);

        $fullNamespace = $rootNamespace.'\\'.$moduleName.'\\'.$version.'\\Controller';
        $serviceName = $entityName.'Service';
        $namespaceToService = $rootNamespace.'\\'.$moduleName.'\\'.$version.'\\Service\\'.$serviceName;
        $namespaceToEntity = $rootNamespace.'\\'.$moduleName.'\\'.$version.'\\Entity\\'.$entityName;

        $createdControllers = [];

        /** @var ServiceAddControllerInterface $controllerService */
        foreach ($this->controllerServices as $controllerService) {
            $phpNamespace = $controllerService->createController($fullNamespace, $serviceName, $entityName, $data, $prefix);
            $phpNamespace->addUse($namespaceToService);
            $phpNamespace->addUse($namespaceToEntity);
            $dirControllerFile = sprintf(
                '%s/%s/%s/Controller/%s.php',
                $path,
                $moduleName,
                $version,
                $controllerService->getName($entityName)
            );
            $operation = 'created';
            if (file_exists($dirControllerFile)) {
                $operation = 'updated';
            }
            $controllerFileData = $this->printNamespace($phpNamespace);
            $this->filesystem->dumpFile($dirControllerFile, $controllerFileData);

            $createdControllers[] = [
                'module' => $moduleName,
                'version' => $data['version'],
                'operation' => $operation,
                'type' => 'controller',
                'file' => $dirControllerFile,
            ];
        }

        return $createdControllers;
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
}