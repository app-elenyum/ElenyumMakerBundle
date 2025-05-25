<?php

namespace Elenyum\Maker\Serializer;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\Mapping\MappingException;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Doctrine\Common\Collections\Collection;

class EntityDenormalizer implements DenormalizerInterface, SerializerAwareInterface
{
    private $entityManager;
    private $serializer;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function setSerializer(SerializerInterface $serializer): void
    {
        $this->serializer = $serializer;
    }

    public function supportsDenormalization($data, $type, $format = null, array $context = []): bool
    {
        $supports = class_exists($type) && $this->entityManager->getMetadataFactory()->hasMetadataFor($type);

        return $supports;
    }

    /**
     * @throws MappingException
     * @throws ORMException
     */
    public function denormalize($data, $type, $format = null, array $context = []): mixed
    {
        // Создаём новый объект или используем существующий
        $object = $context[AbstractNormalizer::OBJECT_TO_POPULATE] ?? new $type();

        // Получаем метаданные сущности
        $metadata = $this->entityManager->getClassMetadata($type);

        // Обрабатываем каждое поле
        foreach ($data as $fieldName => $value) {
            if ($metadata->hasField($fieldName)) {
                // Простое поле (например, text)
                $metadata->setFieldValue($object, $fieldName, $value);
            } elseif ($metadata->hasAssociation($fieldName) && is_scalar($value)) {
                // Поле-отношение (например, news с ID)
                $associationMapping = $metadata->getAssociationMapping($fieldName);
                $targetEntity = $associationMapping['targetEntity'];
                $relatedEntity = $this->entityManager->getReference($targetEntity, $value);
                $metadata->setFieldValue($object, $fieldName, $relatedEntity);
            } elseif ($metadata->hasAssociation($fieldName) && is_array($value)) {
                // Коллекция (например, OneToMany или ManyToMany)
                $associationMapping = $metadata->getAssociationMapping($fieldName);
                $targetEntity = $associationMapping['targetEntity'];
                $collection = new ArrayCollection();
                foreach ($value as $item) {
                    if (is_scalar($item)) {
                        $relatedEntity = $this->entityManager->getReference($targetEntity, $item);
                        $collection->add($relatedEntity);
                    } else {
                        // Если это сложный объект, делегируем сериализатору
                        $relatedEntity = $this->serializer->denormalize($item, $targetEntity, $format, $context);
                        $collection->add($relatedEntity);
                    }
                }
                $metadata->setFieldValue($object, $fieldName, $collection);
            }
        }

        return $object;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            Collection::class => false, // Указываем, что этот нормализатор поддерживает тип Doctrine Collection
            '*' => false,
        ];
    }
}