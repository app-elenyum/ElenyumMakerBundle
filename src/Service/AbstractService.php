<?php

namespace Elenyum\Maker\Service;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use LogicException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionClass;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Service\Attribute\Required;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

abstract class AbstractService implements ServiceSubscriberInterface
{
    const CONNECTION = null;

    protected ContainerInterface $container;
    protected EntityRepository $repository;

    #[Required]
    public function setContainer(ContainerInterface $container): ?ContainerInterface
    {
        $previous = $this->container ?? null;
        $this->container = $container;

        return $previous;
    }

    public function getRepository(): EntityRepository
    {
        return $this->repository;
    }

    /**
     * Get a user from the Security Token Storage.
     *
     * @return UserInterface|null
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     *
     * @see TokenInterface::getUser()
     */
    protected function getUser(): ?UserInterface
    {
        if (!$this->container->has('security.token_storage')) {
            throw new LogicException(
                'The SecurityBundle is not registered in your application. Try running "composer require symfony/security-bundle".'
            );
        }

        if (null === $token = $this->container->get('security.token_storage')->getToken()) {
            return null;
        }

        return $token->getUser();
    }

    /**
     * Checks if the attribute is granted against the current authentication token and optionally supplied subject.
     *
     * @param mixed $attribute
     * @param mixed|null $subject
     *
     * @return bool
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function isGranted(mixed $attribute, mixed $subject = null): bool
    {
        if (!$this->container->has('security.authorization_checker')) {
            throw new \LogicException(
                'The SecurityBundle is not registered in your application. Try running "composer require symfony/security-bundle".'
            );
        }

        return $this->container->get('security.authorization_checker')->isGranted($attribute, $subject);
    }

    /**
     * Gets a container parameter by its name.
     */
    protected function getParameter(string $name): array|bool|string|int|float|\UnitEnum|null
    {
        if (!$this->container->has('parameter_bag')) {
            throw new ServiceNotFoundException(
                'parameter_bag.',
                null,
                null,
                [],
                sprintf(
                    'The "%s::getParameter()" method is missing a parameter bag to work properly. Did you forget to register your controller as a service subscriber? This can be fixed either by using autoconfiguration or by manually wiring a "parameter_bag" in the service locator passed to the controller.',
                    static::class
                )
            );
        }

        return $this->container->get('parameter_bag')->get($name);
    }

    protected function getSerializer(): Serializer
    {
        if (!$this->container->has('serializer')) {
            throw new \LogicException(
                'The Serializer is not registered in your application. Try running "composer require symfony/serializer".'
            );
        }

        /** @var Serializer $serializer */
        return $this->container->get('serializer');
    }

    /**
     * @param string $data
     * @param object|null $entity
     * @param array $groups
     * @return object
     */
    public function serializeDataToEntity(string $data, ?object $entity = null, array $groups = []): object
    {
        $serializer = $this->getSerializer();
        $context = [
            'groups' => array_unique(array_merge($groups, ['Default'])),
            'allow_extra_attributes' => false,
        ];

        if ($entity !== null) {
            $context[AbstractNormalizer::OBJECT_TO_POPULATE] = $entity;
        }

        return $serializer->deserialize($data, $this->repository->getClassName(), 'json', $context);
    }

    public function validate(object $entity): array
    {
        if (!$this->container->has('validator')) {
            throw new \LogicException(
                'The Validator is not registered in your application. Try running "composer require symfony/validator".'
            );
        }
        $validator = $this->container->get('validator');

        $messages = [];
        $errors = $validator->validate($entity);
        if ($errors->count() > 0) {
            for ($x = 0; $x < $errors->count(); $x++) {
                $error = $errors->get($x);
                $messages[$error->getPropertyPath()][] = $error->getMessage();
            }
        }

        return $messages;
    }

    /**
     * @return EntityManagerInterface
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function getEntityManager(): EntityManagerInterface
    {
        if (!$this->container->has(ManagerRegistry::class)) {
            throw new \LogicException(
                'The orm-pack is not registered in your application. Try running "composer require symfony/orm-pack".'
            );
        }

        return $this->container->get(ManagerRegistry::class)->getManager(static::CONNECTION);
    }

    /**
     * @throws \ReflectionException
     */
    public function getEntityGroups(?string $type = null): array
    {
        $reflectionClass = new ReflectionClass($this->getRepository()->getClassName());

        $attributeGroups = $reflectionClass->getAttributes(Groups::class);
        if (isset($attributeGroups[0]) && isset($attributeGroups[0]?->getArguments()[0])) {
            $groups = $attributeGroups[0]->getArguments()[0];
            if (!empty($groups) && $type !== null) {
                $type = mb_strtoupper($type);
                $groups = preg_replace('/(\w+)/', $type.'_$1', $groups);
            }

            return array_merge($groups, ['Default']);
        }

        return [];
    }

    /**
     * @return string[]
     */
    public static function getSubscribedServices(): array
    {
        return [
            'security.authorization_checker' => '?'.AuthorizationCheckerInterface::class,
            'security.token_storage' => '?'.TokenStorageInterface::class,
            'parameter_bag' => '?'.ContainerBagInterface::class,
            'serializer' => '?'.SerializerInterface::class,
            'validator' => '?'.ValidatorInterface::class,
            'doctrine.orm.entity_manager' => '?'.EntityManagerInterface::class,
            ManagerRegistry::class => '?'.ManagerRegistry::class,
        ];
    }
}