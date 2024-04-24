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
    ) {}

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

        $version = $data['version_namespace'];
        $moduleName = $data['module_name'];
        $rootNamespace = ucfirst($namespace);

        $fullNamespace = $rootNamespace.'\\'.$moduleName.'\\'.$version.'\\Controller';

        /** @var ServiceAddControllerInterface $controllerService */
        foreach ($this->controllerServices as $controllerService) {
            $n = $controllerService->createController($fullNamespace, $data, $prefix);

//            dd($this->printNamespace($n));
        }

        return ['ServiceCreateControllerHandler'];
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