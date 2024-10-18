<?php

namespace Elenyum\Maker\Serializer;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class CollectionDenormalizer implements DenormalizerInterface
{
    public function supportsDenormalization(mixed $data, string $type, $format = null, array $context = []): bool
    {
        return $type === Collection::class;
    }

    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
    {
        return new ArrayCollection($data);
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            Collection::class => false, // Указываем, что этот нормализатор поддерживает тип Doctrine Collection
            '*' => false,
        ];
    }
}