<?php

namespace Elenyum\Maker;

use Elenyum\Maker\DependencyInjection\ElenyumMakerExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class ElenyumMakerBundle extends Bundle
{
    /**
     * @return string
     */
    public function getPath(): string
    {
        return dirname(__DIR__);
    }

    /**
     * @param ContainerBuilder $container
     * @return void
     */
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);
    }

    public function getContainerExtension(): ?ExtensionInterface
    {
        return new ElenyumMakerExtension();
    }
}