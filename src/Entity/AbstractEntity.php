<?php

namespace Elenyum\Maker\Entity;

use DateTimeImmutable;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\OneToOne;
use Exception;
use ReflectionClass;
use ReflectionProperty;
use Symfony\Component\Serializer\Annotation\Groups;
use Throwable;

abstract class AbstractEntity implements EntityToArrayInterface
{
    private array $hashEntity = [];

    /**
     * @throws Exception
     */
    public function toArray(array $inputGroups, ?array $fields = null, ?string $parent = null): array
    {
        $reflectionClass = new ReflectionClass($this);
        $result = [];

        foreach ($reflectionClass->getProperties() as $property) {
            $group = $property->getAttributes(Groups::class);
            if ((!empty($fields) && !in_array($property->getName(), $fields)) &&
                empty(preg_grep(sprintf('#^%s#', $property->getName()), $fields))) {
                continue;
            }

            $uintersectResult = array_uintersect(end($group)?->getArguments()[0], $inputGroups, 'strcasecmp');

            if (!empty($uintersectResult)) {
                $methodVal = 'get'.ucfirst($property->getName());
                if (method_exists($this, $methodVal)) {
//                    try {
                    $val = $this->{$methodVal}();
//                    } catch (Throwable $e) {
//                        continue;
//                    }

                    if ($val instanceof DateTimeImmutable) {
                        $val = $val->format(DATE_ATOM);
                    }

                    $pregForInput = $fields;
                    if (!empty($fields)) {
                        $pregForInput = preg_filter(sprintf('#^%s.#', $property->getName()), '', $fields);
                    }
                    if ($val instanceof Collection) {
                        if ($property->getName() === $parent) {
                            continue;
                        }
                        $collection = [];
                        $parentName = $this->getParent($property);
                        foreach ($val as $item) {
                            $collection[] = $item->toArray($inputGroups, $pregForInput, $parentName);
                        }
                        $result[$property->getName()] = $collection;
                    } elseif ((empty($fields) || !empty($pregForInput)) && class_exists($property->getType()->getName())) {
                        if ($property->getName() === $parent) {
                            continue;
                        }
                        $parentName = $this->getParent($property);
                        $result[$property->getName()] = $val->toArray($inputGroups, $pregForInput, $parentName);
                    } else {
                        $result[$property->getName()] = $val;
                    }
                } else {
                    throw new Exception('Undefined method: '.$methodVal.' for class: '.$this::class);
                }
            }

        }

        return array_filter($result);
    }

    private function getParent(ReflectionProperty $property): ?string
    {
        $result = null;
        foreach ($property->getAttributes() as $attribute) {
            if ($attribute->getName() === OneToMany::class ||
                $attribute->getName() === ManyToOne::class ||
                $attribute->getName() === ManyToMany::class ||
                $attribute->getName() === OneToOne::class
            ) {
                $arguments = $attribute->getArguments();
                $result = $arguments['inversedBy'] ?? $arguments['mappedBy'];
            }
        }

        return $result;
    }
}