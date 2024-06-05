<?php

namespace Elenyum\Maker\Tests\Service;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Elenyum\Maker\Entity\AbstractEntity;
use Elenyum\Maker\Exception\EntityNotImplementAbstractEntityException;
use Elenyum\Maker\Exception\NotFoundByIdException;
use Elenyum\Maker\Service\ElenyumMakerBaseService;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class ElenyumMakerBaseServiceTest extends TestCase
{
    private $service;
    private $repositoryMock;
    private $entityManagerMock;
    private $containerMock;

    protected function setUp(): void
    {
        $this->repositoryMock = $this->createMock(EntityRepository::class);
        $this->entityManagerMock = $this->createMock(EntityManagerInterface::class);
        $this->containerMock = $this->createMock(ContainerInterface::class);

        $this->service = $this->getMockBuilder(ElenyumMakerBaseService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getRepository', 'getEntityManager', 'serializeDataToEntity', 'validate'])
            ->getMock();

        $this->service->method('getRepository')->willReturn($this->repositoryMock);
        $this->service->method('getEntityManager')->willReturn($this->entityManagerMock);
    }

    public function testGetOne()
    {
        $id = 1;
        $groups = ['group1'];
        $fields = ['field1'];
        $entityMock = $this->createMock(AbstractEntity::class);

        $entityMock->expects($this->once())
            ->method('toArray')
            ->with($groups, $fields)
            ->willReturn(['data']);

        $this->repositoryMock->expects($this->once())
            ->method('findOneBy')
            ->with(['id' => $id])
            ->willReturn($entityMock);

        $result = $this->service->getOne($id, $groups, $fields);

        $this->assertSame(['data'], $result);
    }

    public function testGetOneNotFound()
    {
        $this->repositoryMock->method('findOneBy')->willReturn(null);

        $this->expectException(NotFoundByIdException::class);

        $this->service->getOne(1, ['group1']);
    }

    public function testGetOneEntityNotImplementAbstractEntityException()
    {
        $entityMock = new \stdClass();

        $this->repositoryMock->method('findOneBy')->willReturn($entityMock);

        $this->expectException(EntityNotImplementAbstractEntityException::class);

        $this->service->getOne(1, ['group1']);
    }

    public function testGetList()
    {
        $offset = 0;
        $limit = 10;
        $orderBy = ['id' => 'ASC'];
        $groups = ['group1'];
        $filter = ['filter'];
        $fields = ['field1'];

        $entityMock = $this->createMock(AbstractEntity::class);
        $entityMock->method('toArray')->willReturn(['data']);

        $this->repositoryMock->expects($this->once())
            ->method('count')
            ->with($filter)
            ->willReturn(100);

        $this->repositoryMock->expects($this->once())
            ->method('findBy')
            ->with($filter, $orderBy, $limit, $offset)
            ->willReturn([$entityMock]);

        $result = $this->service->getList($offset, $limit, $orderBy, $groups, $filter, $fields);

        $this->assertSame([100, [['data']]], $result);
    }

    public function testPrepareJsonFormat()
    {
        $json = "{ 'key1' : 'value1' , 'key2':'value2' }";
        $expected = '{ "key1":"value1","key2":"value2" }';

        $result = $this->service->prepareJsonFormat($json);

        $this->assertSame($expected, $result);
    }

    public function testAdd()
    {
        $data = json_encode(['data']);
        $groups = ['group1'];
        $outputGroups = ['group2'];
        $entityMock = $this->createMock(AbstractEntity::class);

        $this->service->method('serializeDataToEntity')->willReturn($entityMock);
        $this->service->method('validate')->willReturn([]);

        $entityMock->method('toArray')->willReturn(['result']);

        $this->entityManagerMock->expects($this->once())->method('persist')->with($entityMock);
        $this->entityManagerMock->expects($this->once())->method('flush');

        $result = $this->service->add($data, $groups, $outputGroups);

        $this->assertSame(['result'], $result);
    }

    public function testUpdate()
    {
        $data = '{"key":"value"}';
        $id = 1;
        $groups = ['group1'];
        $outputGroups = ['group2'];
        $entityMock = $this->createMock(AbstractEntity::class);

        $this->repositoryMock->method('findOneBy')->with(['id' => $id])->willReturn($entityMock);

        $this->service->method('serializeDataToEntity')->willReturn($entityMock);
        $this->service->method('validate')->willReturn([]);

        $entityMock->method('toArray')->willReturn(['result']);

        $this->entityManagerMock->expects($this->once())->method('flush');

        $result = $this->service->update($data, $id, $groups, $outputGroups);

        $this->assertSame(['result'], $result);
    }

    public function testDelete()
    {
        $id = 1;
        $groups = ['group1'];
        $entityMock = $this->createMock(AbstractEntity::class);

        $this->repositoryMock->method('findOneBy')->with(['id' => $id])->willReturn($entityMock);

        $entityMock->method('toArray')->willReturn(['result']);

        $this->entityManagerMock->expects($this->once())->method('remove')->with($entityMock);

        $result = $this->service->delete($id, $groups);

        $this->assertSame(['result'], $result);
    }
}

