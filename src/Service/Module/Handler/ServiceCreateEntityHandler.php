<?php

namespace Elenyum\Maker\Service\Module\Handler;

use Countable;
use Elenyum\Maker\Service\Module\Entity\SetFullNamespaceInterface;
use Nette\PhpGenerator\PhpNamespace;
use Nette\PhpGenerator\Printer;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class ServiceCreateEntityHandler implements ServiceCreateInterface
{
    public function __construct(
        readonly private Filesystem $filesystem,
        /** @var \Elenyum\Maker\Service\Module\Entity\ServiceAddToClassInterface[] $entityServices */
        readonly private Countable $entityServices,
        readonly private array $options
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

        $version = $data['version_namespace'];
        $nameEntity = $data['entity_name'];
        $moduleName = $data['module_name'];
        $dirEntityFile = $path.'/'.$moduleName.'/'.$version.'/Entity/'.$nameEntity.'.php';

        $entityFileData = $this->printNamespace(
            $this->createEntity($namespace, $data)
        );

        $operation = 'created';
        if (file_exists($dirEntityFile)) {
            $operation = 'updated';
        }
        $this->filesystem->dumpFile($dirEntityFile, $entityFileData);
        return [[
            'module' => $moduleName,
            'version' => $data['version'],
            'operation' => $operation,
            'type' => 'entity',
            'file' => $dirEntityFile,
        ]];
    }

    private function createEntity(string $baseNamespace, array $data): PhpNamespace
    {
        $version = str_replace('.', '_', $data['version']);
        $nameEntity = ucfirst($data['entity_name']);
        $moduleName = ucfirst($data['module_name']);
        $rootNamespace = ucfirst($baseNamespace);

        $fullNamespace = $rootNamespace.'\\'.$moduleName.'\\'.ucfirst($version).'\\Entity';

        /** added full name */
        $namespace = new PhpNamespace($fullNamespace);
        $namespace->addUse('Doctrine\ORM\Mapping', 'ORM');
        $namespace->addUse('Symfony\Component\Serializer\Annotation\Groups');
        $namespace->addUse('Symfony\Component\Validator\Constraints', 'Assert');
        $namespace->addUse('Doctrine\Common\Collections\Collection');
        $namespace->addUse('Doctrine\Common\Collections\ArrayCollection');

        $class = $namespace->addClass($nameEntity);

        $class->addAttribute('ORM\Table', ['name' => $this->prepareTableName($moduleName, $nameEntity, $version)]);
        $class->addAttribute('ORM\Entity');
        foreach ($data['validator'] as $validator => $validatorParams) {
            $class->addAttribute('Assert\\' . $validator, $validatorParams ?? []);
        }

        /** @var \Elenyum\Maker\Service\Module\Entity\ServiceAddToClassInterface $service */
        foreach ($this->entityServices as $service) {
            if ($service instanceof SetFullNamespaceInterface) {
                $service->setFullNamespace($fullNamespace);
            }

            $service->create($class, $data);
        }

        return $namespace;
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

    private function prepareTableName(string $moduleName, string $input, string $version): string
    {
        $result = preg_replace_callback('/(?<!^)[A-Z]/', function ($matches) {
            return '_' . strtolower($matches[0]);
        }, $input);

        $result .= '__' . $version;
        $result .= '__' . $moduleName;

        return mb_strtolower($result);
    }
}