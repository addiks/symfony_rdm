<?php
/**
 * Copyright (C) 2018 Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 * @license GPL-3.0
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

namespace Addiks\RDMBundle\Tests\DataLoader;

use Addiks\RDMBundle\DataLoader\SimpleSelectDataLoader;
use Addiks\RDMBundle\Mapping\Drivers\MappingDriverInterface;
use Addiks\RDMBundle\Mapping\EntityMapping;
use Addiks\RDMBundle\Tests\Hydration\EntityExample;
use Addiks\RDMBundle\Mapping\ChoiceMapping;
use Addiks\RDMBundle\ValueResolver\ValueResolverInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\ORM\Query\Expr;
use Doctrine\DBAL\Driver\Statement;
use PHPUnit\Framework\TestCase;
use Addiks\RDMBundle\Mapping\ServiceMapping;
use Doctrine\ORM\UnitOfWork;
use Addiks\RDMBundle\Tests\Hydration\ServiceExample;

final class DataSimpleSelectLoaderTest extends TestCase
{

    /**
     * @var SimpleSelectDataLoader
     */
    private $dataLoader;

    /**
     * @var MappingDriverInterface
     */
    private $mappingDriver;

    /**
     * @var ValueResolverInterface
     */
    private $valueResolver;

    /**
     * @var ClassMetadata
     */
    private $classMetaData;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var QueryBuilder
     */
    private $queryBuilder;

    /**
     * @var Expr
     */
    private $expr;

    /**
     * @var Statement
     */
    private $statement;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var UnitOfWork
     */
    private $unitOfWork;

    /**
     * @var EntityMapping
     */
    private $entityMapping;

    /**
     * @var ChoiceMapping
     */
    private $mappings = array();

    public function setUp()
    {
        $this->mappingDriver = $this->createMock(MappingDriverInterface::class);
        $this->valueResolver = $this->createMock(ValueResolverInterface::class);
        $this->connection = $this->createMock(Connection::class);
        $this->queryBuilder = $this->createMock(QueryBuilder::class);
        $this->expr = $this->createMock(Expr::class);
        $this->statement = $this->createMock(Statement::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->unitOfWork = $this->createMock(UnitOfWork::class);

        $this->dataLoader = new SimpleSelectDataLoader(
            $this->mappingDriver,
            $this->valueResolver,
            1
        );

        $this->classMetaData = new ClassMetadata(EntityExample::class);
        $this->classMetaData->table = ['name' => 'some_table'];
        $this->classMetaData->identifier = ["id"];
        $this->classMetaData->fieldMappings = [
            "id" => [
                'columnName' => 'id'
            ]
        ];

        $this->mappings['foo'] = new ChoiceMapping('foo_column', []);
        $this->mappings['bar'] = new ChoiceMapping('bar_column', []);

        $this->entityMapping = new EntityMapping(EntityExample::class, $this->mappings);

        $this->mappingDriver->method("loadRDMMetadataForClass")->willReturn($this->entityMapping);
        $this->entityManager->method("getClassMetadata")->willReturn($this->classMetaData);
        $this->entityManager->method("getConnection")->willReturn($this->connection);
        $this->entityManager->method("getUnitOfWork")->willReturn($this->unitOfWork);
        $this->connection->method('createQueryBuilder')->willReturn($this->queryBuilder);
        $this->queryBuilder->method('expr')->willReturn($this->expr);
        $this->queryBuilder->method('execute')->willReturn($this->statement);
    }

    /**
     * @test
     */
    public function loadsDataFromDatabase()
    {
        /** @var array $expectedData */
        $expectedData = array(
            'foo_column' => 'Lorem ipsum',
            'bar_column' => 'dolor sit amet'
        );

        $this->classMetaData->identifier = ["id", "secondId"];
        $this->classMetaData->fieldMappings = [
            "id"  => ['columnName' => 'id'],
            "secondId"  => ['columnName' => 'secondId'],
            "faz" => ['columnName' => 'faz']
        ];

        $this->queryBuilder->expects($this->once())->method('from')->with($this->equalTo('some_table'));
        $this->queryBuilder->expects($this->exactly(2))->method('andWhere')->with($this->equalTo("*eq-return*"));
        $this->queryBuilder->expects($this->exactly(2))->method('addSelect');
        $this->queryBuilder->expects($this->once())->method('setMaxResults')->with($this->equalTo(1));

        $this->statement->method('fetch')->willReturn($expectedData);

        /** @var ServiceExample $fazService */
        $fazService = $this->createMock(ServiceExample::class);

        $entity = new EntityExample(null, null, null, $fazService);
        $entity->id = "some_id";
        $entity->secondId = "second_id";

        $this->expr->method("eq")->willReturn("*eq-return*");

        /** @var array $actualData */
        $actualData = $this->dataLoader->loadDBALDataForEntity($entity, $this->entityManager);

        $this->assertEquals($expectedData, $actualData);
    }

    /**
     * @test
     */
    public function shouldNotLoadEntityWithoutId()
    {
        /** @var array $expectedData */
        $expectedData = array();

        $this->classMetaData->identifier = [];

        $this->queryBuilder->expects($this->never())->method('from');

        $this->statement->method('fetch')->willReturn($expectedData);

        $entity = new EntityExample();
        $entity->id = "some_id";

        $this->expr->method("eq")->willReturn("*eq-return*");

        /** @var array $actualData */
        $actualData = $this->dataLoader->loadDBALDataForEntity($entity, $this->entityManager);

        $this->assertEquals($expectedData, $actualData);
    }

    /**
     * @test
     */
    public function storesDataInDatabase()
    {
        $this->mappings['faz'] = new ChoiceMapping('faz_column', []);

        $this->setUp();

        $this->classMetaData->identifier = ["id", "faz"];
        $this->classMetaData->fieldMappings = [
            "id"  => ['columnName' => 'id'],
            "faz" => ['columnName' => 'faz']
        ];

        /** @var ServiceExample $fazService */
        $fazService = $this->createMock(ServiceExample::class);

        $entity = new EntityExample(null, null, null, $fazService);
        $entity->id = "some_id";
        $entity->foo = "some_ipsum_service";
        $entity->bar = "some_dolor_service";

        /** @var array $map */
        $map = array(
            [$this->mappings['foo'], ["foo_column" => "ipsum"]],
            [$this->mappings['bar'], ["bar_column" => "dolor"]],
            [$this->mappings['faz'], ["faz_column" => "sit"]],
        );

        $this->valueResolver->method("revertValue")->will($this->returnCallback(
            function ($fieldMapping, $context, $value) use ($map) {
                foreach ($map as [$mapping, $data]) {
                    if ($mapping === $fieldMapping) {
                        return $data;
                    }
                }
            }
        ));

        $this->connection->expects($this->once())->method("update")->with(
            $this->equalTo("some_table"),
            $this->equalTo([
                'foo_column' => 'ipsum',
                'bar_column' => 'dolor',
                'faz_column' => 'sit'
            ]),
            $this->equalTo([
                'id'  => "some_id",
                'faz' => $fazService
            ])
        );

        $this->dataLoader->storeDBALDataForEntity($entity, $this->entityManager);
    }

    /**
     * @test
     */
    public function shouldNotUpdateIfDataDidNotChange()
    {
        $entity = new EntityExample();
        $entity->id = "some_id";
        $entity->foo = "some_ipsum_service";
        $entity->bar = "some_dolor_service";

        /** @var array $map */
        $map = array(
            "some_ipsum_service" => ["foo_column" => "ipsum"],
            "some_dolor_service" => ["bar_column" => "dolor"],
        );

        $this->valueResolver->method("revertValue")->will($this->returnCallback(
            function ($fieldMapping, $context, $value) use ($map) {
                return $map[$value];
            }
        ));

#        $this->valueResolver->method("revertValue")->will($this->returnValueMap([
#            [$this->mappings['foo'], $entity, "some_ipsum_service", ["foo_column" => "ipsum"]],
#            [$this->mappings['bar'], $entity, "some_dolor_service", ["bar_column" => "dolor"]],
#        ]));

        $this->connection->expects($this->never())->method("update");

        /** @var array $expectedData */
        $expectedData = [
            'foo_column' => 'ipsum',
            'bar_column' => 'dolor'
        ];

        $this->statement->method('fetch')->willReturn($expectedData);

        $this->dataLoader->loadDBALDataForEntity($entity, $this->entityManager);
        $this->dataLoader->storeDBALDataForEntity($entity, $this->entityManager);
    }

    /**
     * @test
     */
    public function shouldRemoveDBALForEntity()
    {
        $this->dataLoader->removeDBALDataForEntity(new EntityExample(), $this->entityManager);
        $this->assertTrue(true);
    }

    /**
     * @test
     */
    public function shouldPrepareOnMetadataLoad()
    {
        $this->dataLoader->prepareOnMetadataLoad($this->entityManager, $this->classMetaData);
        $this->assertTrue(true);
    }

}
