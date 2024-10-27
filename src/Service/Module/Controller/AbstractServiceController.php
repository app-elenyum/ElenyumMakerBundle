<?php

namespace Elenyum\Maker\Service\Module\Controller;

use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Literal;
use Nette\PhpGenerator\PhpNamespace;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

abstract class AbstractServiceController implements ServiceAddControllerInterface
{
    /**
     * @var mixed|null
     */
    private string $securityName;

    public function __construct(
        #[Autowire('%elenyum_maker.config%')]
        private array $options
    )
    {
        $this->securityName = $this->options['securityName'] ?? null;
    }

    public function getSecurityName(): ?string
    {
        return $this->securityName;
    }

    public function addAutAttribute(PhpNamespace $namespace, Literal $entityClass, ClassType $class)
    {
        if (class_exists(\Elenyum\Authorization\Attribute\Auth::class)) {
            $namespace->addUse('Elenyum\Authorization\Attribute\Auth');
            if ($this->getSecurityName() !== null) {
                $params['name'] = $this->getSecurityName();
            }
            $params['model'] = $entityClass;
            $class->addAttribute('Auth', $params);
        }
    }
}