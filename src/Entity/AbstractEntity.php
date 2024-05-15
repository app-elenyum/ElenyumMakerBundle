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

abstract class AbstractEntity implements EntityToArrayInterface
{
    /**
     * @throws Exception
     */
    public function toArray(array $inputGroups, ?array $fields = null, array $parents = []): array
    {
        $reflectionClass = new ReflectionClass($this);
        $result = [];

        foreach ($reflectionClass->getProperties() as $property) {
            if ($property->getName() === 'lazyObjectState') {
                continue;
            }
            $group = $property->getAttributes(Groups::class);
            if (
                (!empty($fields) && !in_array($property->getName(), $fields)) &&
                empty(preg_grep(sprintf('#^%s#', $property->getName()), $fields))
            ) {
                continue;
            }

            $uintersectResult = [];
            if (!empty($group)) {
                $uintersectResult = array_uintersect(
                    end($group)?->getArguments()[0],
                    $inputGroups, 'strcasecmp'
                );
            }
            if (empty($uintersectResult) && !empty($group)) {
                continue;
            }
            if (in_array($property->getName(), $parents)) {
                continue;
            }

            $methodVal = 'get'.ucfirst($property->getName());
            if (method_exists($this, $methodVal)) {
                $val = $this->{$methodVal}();

                if ($val instanceof DateTimeImmutable) {
                    $val = $val->format(DATE_ATOM);
                }

                $pregForInput = $fields;
                if (!empty($fields)) {
                    $pregForInput = preg_filter(sprintf('#^%s.#', $property->getName()), '', $fields);
                }
                if ($val instanceof Collection) {
                    $collection = [];
                    $parentName = $this->getParent($property);
                    $parents[] = $parentName;
                    foreach ($val as $item) {
                        $collection[] = $item->toArray($inputGroups, $pregForInput, $parents);
                    }
                    $result[$property->getName()] = $collection;
                } elseif ((empty($fields) || !empty($pregForInput)) && class_exists($property->getType()->getName()) && $val !== null) {
                    $parentName = $this->getParent($property);
                    $parents[] = $parentName;
                    $result[$property->getName()] = $val->toArray($inputGroups, $pregForInput, $parents);
                } else {
                    $result[$property->getName()] = $val;
                }
            } else {
                throw new Exception('Undefined method: '.$methodVal.' for class: '.$this::class);
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