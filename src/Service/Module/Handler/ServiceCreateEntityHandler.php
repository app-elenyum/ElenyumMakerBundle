<?php

namespace Elenyum\Maker\Service\Module\Handler;

use Countable;
use Nette\PhpGenerator\PhpNamespace;
use Nette\PhpGenerator\Printer;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class ServiceCreateEntityHandler implements ServiceCreateInterface
{
    public function __construct(
        readonly private Filesystem $filesystem,
        /** @var \Elenyum\Maker\Service\Module\Entity\ServiceAddToClass[] $propertyServices */
        readonly private Countable $propertyServices,
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

        $entityFileData = $this->printNamespace(
            $this->createEntity($namespace, $data)
        );

        $version = strtoupper(str_replace('.', '_', $data['version']));
        $nameEntity = ucfirst($data['entity_name']);
        $moduleName = ucfirst($data['module_name']);
        $dirEntityFile = $path.'/'.$moduleName.'/'.$version.'/Entity/'.$nameEntity.'.php';

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

    private function createEntity(string $namespace, array $data): PhpNamespace
    {
        $version = strtoupper(str_replace('.', '_', $data['version']));
        $nameEntity = ucfirst($data['entity_name']);
        $moduleName = ucfirst($data['module_name']);
        $rootNamespace = ucfirst($namespace);

        $fullNamespace = $rootNamespace.'\\'.$moduleName.'\\'.$version.'\\Entity';

        $namespace = new PhpNamespace($fullNamespace);
        $namespace->addUse('Doctrine\ORM\Mapping', 'ORM');
        $namespace->addUse('Symfony\Component\Serializer\Annotation\Groups');
        $namespace->addUse('Symfony\Component\Validator\Constraints', 'Assert');
        $namespace->addUse('Doctrine\Common\Collections\Collection');

        $class = $namespace->addClass($nameEntity);
//        $class->setExtends('BaseEntity');

        $class->addAttribute('ORM\Table', ['name' => $this->prepareTableName($nameEntity)]);

        /** @var \Elenyum\Maker\Service\Module\Entity\ServiceAddToClass $service */
        foreach ($this->propertyServices as $service) {
            $service->create($class, $data['column']);
        }

//        dd($this->printNamespace($namespace));
//        die();
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

    private function prepareTableName(string $input): string
    {
        $pattern = '/(?<!^)[A-Z]/';
        $snakeCase = preg_replace_callback($pattern, function ($matches) {
            return '_' . strtolower($matches[0]);
        }, $input);

        return mb_strtolower($snakeCase);
    }
}