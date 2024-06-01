<?php

namespace Elenyum\Maker\Tests\Entity;

use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Elenyum\Maker\Entity\AbstractEntity;
use Elenyum\Maker\Entity\EntityToArrayInterface;
use Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Annotation\Groups;

class AbstractEntityTest extends TestCase
{
    public function testToArray()
    {
        $entity = new class() extends AbstractEntity {
            #[Groups(['default'])]
            private string $name = 'Test Name';

            #[Groups(['extra'])]
            private string $description = 'Test Description';

            public function getName(): string
            {
                return $this->name;
            }

            public function getDescription(): string
            {
                return $this->description;
            }
        };

        $toArrayDefaultGroup = $entity->toArray(['default']);
        $this->assertArrayHasKey('name', $toArrayDefaultGroup);
        $this->assertEquals('Test Name', $toArrayDefaultGroup['name']);
        $this->assertArrayNotHasKey('description', $toArrayDefaultGroup);

        $toArrayExtraGroup = $entity->toArray(['extra']);
        $this->assertArrayHasKey('description', $toArrayExtraGroup);
        $this->assertEquals('Test Description', $toArrayExtraGroup['description']);
        $this->assertArrayNotHasKey('name', $toArrayExtraGroup);
    }

    public function testToArrayWithCollections()
    {
        $childEntity = new class() extends AbstractEntity {
            #[Groups(['default'])]
            private string $detail = 'Child Detail';

            public function getDetail(): string
            {
                return $this->detail;
            }
        };

        $entity = new class($childEntity) extends AbstractEntity {
            private ArrayCollection $children;

            public function __construct($child)
            {
                $this->children = new ArrayCollection([$child]);
            }

            public function getChildren(): ArrayCollection
            {
                return $this->children;
            }
        };

        $result = $entity->toArray(['default']);
        $this->assertCount(1, $result['children']);
        $this->assertEquals('Child Detail', $result['children'][0]['detail']);
    }

    public function testToArrayWithDateTimeImmutable()
    {
        $entity = new class() extends AbstractEntity {
            #[Groups(['default'])]
            private DateTimeImmutable $createdAt;

            public function __construct()
            {
                $this->createdAt = new DateTimeImmutable();
            }

            public function getCreatedAt(): DateTimeImmutable
            {
                return $this->createdAt;
            }
        };

        $result = $entity->toArray(['default']);
        $this->assertMatchesRegularExpression(
            '/\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\+\d{2}:\d{2}/',
            $result['createdAt']
        );
    }

    public function testToArrayWithMissingGetter()
    {
        $this->expectException(Exception::class);

        $entity = new class() extends AbstractEntity {
            #[Groups(['default'])]
            private string $name = 'Test Name';
            // Notice: getter for $name is missing
        };

        $entity->toArray(['default']);
    }

    public function testToArrayWithNestedEntity()
    {
        $childEntity = new class() extends AbstractEntity {
            #[Groups(['default'])]
            private string $detail = 'Child Detail';

            public function getDetail(): string
            {
                return $this->detail;
            }
        };

        $entity = new class($childEntity) extends AbstractEntity {
            #[Groups(['default'])]
            private EntityToArrayInterface $child;

            public function __construct($child)
            {
                $this->child = $child;
            }

            public function getChild(): EntityToArrayInterface
            {
                return $this->child;
            }
        };

        $result = $entity->toArray(['default']);
        $this->assertArrayHasKey('child', $result); // Verify that 'child' key exists
        $this->assertArrayHasKey('detail', $result['child']); // Verify that 'detail' key exists within child
        $this->assertEquals('Child Detail', $result['child']['detail']); // Verify correct value of 'detail'
    }
}