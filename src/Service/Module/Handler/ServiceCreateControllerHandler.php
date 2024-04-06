<?php

namespace Elenyum\Maker\Service\Module\Handler;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class ServiceCreateControllerHandler implements ServiceCreateInterface
{
    public function __construct(
        readonly private Filesystem $filesystem,
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

        return ['ServiceCreateControllerHandler'];
    }
}