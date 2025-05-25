<?php

namespace Elenyum\Maker\Serializer;

use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerInterface;
use voku\helper\AntiXSS;

class XssDenormalizer implements DenormalizerInterface, SerializerAwareInterface
{
    private SerializerInterface $serializer;
    private AntiXSS $antiXss;
    private array $skipProperties;
    private bool $isEnabled;

    public function __construct(array $skipProperties = [], bool $isEnabled = true)
    {
        $this->antiXss = new AntiXSS();
        $this->skipProperties = $skipProperties;
        $this->isEnabled = $isEnabled;
    }

    public function setSerializer(SerializerInterface $serializer): void
    {
        $this->serializer = $serializer;
    }

    public function denormalize($data, string $type, string $format = null, array $context = []): mixed
    {
        if (!$this->isEnabled) {
            return $this->serializer->denormalize($data, $type, $format, $context);
        }

        $data = $this->sanitizeData($data, $type);

        return $this->serializer->denormalize($data, $type, $format, $context);
    }

    public function supportsDenormalization($data, string $type, string $format = null, array $context = []): bool
    {
        return true;
    }

    public function getSupportedTypes(?string $format): array
    {
        return ['*' => true];
    }

    private function sanitizeData($data, string $type): mixed
    {
        if (is_string($data)) {
            return $this->sanitizeString($data);
        }

        if (is_array($data)) {
            return $this->sanitizeArray($data, $type);
        }

        return $data;
    }

    private function sanitizeString(string $data): string
    {
        return $this->antiXss->xss_clean($data);
    }

    private function sanitizeArray(array $data, string $type): array
    {
        $skipProperties = $this->skipProperties[$type] ?? [];

        foreach ($data as $key => $value) {
            if (in_array($key, $skipProperties, true)) {
                continue;
            }

            if (is_string($value)) {
                $data[$key] = $this->sanitizeString($value);
            } elseif (is_array($value)) {
                $data[$key] = $this->sanitizeArray($value, $type);
            }
        }

        return $data;
    }
}