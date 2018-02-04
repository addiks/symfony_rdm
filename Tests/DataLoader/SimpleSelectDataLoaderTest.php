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

    public function setUp()
    {
        $this->mappingDriver = $this->createMock(MappingDriverInterface::class);
        $this->valueResolver = $this->createMock(ValueResolverInterface::class);

        $this->dataLoader = new SimpleSelectDataLoader(
            $this->mappingDriver,
            $this->valueResolver
        );
    }

    /**
     * @test
     */
    public function loadsDataFromDatabase()
    {
        /** @var array $expectedData */
        $expectedData = array(
            'foo' => 'Lorem ipsum',
            'bar' => 'dolor sit amet'
        );

        $classMetaData = new ClassMetadata(EntityExample::class);
        $classMetaData->table = ['name' => 'some_table'];
        $classMetaData->identifier = ["id"];
        $classMetaData->fieldMappings = [
            "id" => [
                'columnName' => 'id'
            ]
        ];

        $entityMapping = new EntityMapping(EntityExample::class, [
            new ChoiceMapping('foo', []),
            new ChoiceMapping('bar', [])
        ]);

        /** @var Connection $connection */
        $connection = $this->createMock(Connection::class);

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->createMock(QueryBuilder::class);

        /** @var Expr $expr */
        $expr = $this->createMock(Expr::class);

        /** @var Statement $statement */
        $statement = $this->createMock(Statement::class);

        /** @var EntityManagerInterface $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);

        $connection->method('createQueryBuilder')->willReturn($queryBuilder);

        $queryBuilder->method('expr')->willReturn($expr);
        $queryBuilder->method('execute')->willReturn($statement);

        $queryBuilder->expects($this->once())->method('from')->with($this->equalTo('some_table'));
        $queryBuilder->expects($this->once())->method('andWhere')->with($this->equalTo("*eq-return*"));
        $queryBuilder->expects($this->exactly(2))->method('addSelect');

        $statement->method('fetch')->willReturn($expectedData);

        $this->mappingDriver->method("loadRDMMetadataForClass")->willReturn($entityMapping);
        $entityManager->method("getClassMetadata")->willReturn($classMetaData);
        $entityManager->method("getConnection")->willReturn($connection);

        $entity = new EntityExample();
        $entity->id = "some_id";

        $expr->expects($this->once())->method("eq")->with(
            $this->equalTo('id'),
            $this->equalTo('some_id')
        )->willReturn("*eq-return*");

        /** @var array $actualData */
        $actualData = $this->dataLoader->loadDBALDataForEntity($entity, $entityManager);

        $this->assertEquals($expectedData, $actualData);
    }

    /**
     * @test
     */
    public function storesDataInDatabase()
    {
        /** @var mixed $classMetaData */
        $classMetaData = new ClassMetadata(EntityExample::class);
        $classMetaData->table = ['name' => 'some_table'];
        $classMetaData->identifier = ["id"];
        $classMetaData->fieldMappings = [
            "id" => [
                'columnName' => 'id'
            ]
        ];

        $fooMapping = new ChoiceMapping('foo_column', []);
        $barMapping = new ChoiceMapping('bar_column', []);

        $entityMapping = new EntityMapping(EntityExample::class, [
            'foo' => $fooMapping,
            'bar' => $barMapping
        ]);

        /** @var Connection $connection */
        $connection = $this->createMock(Connection::class);

        /** @var EntityManagerInterface $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);

        /** @var UnitOfWork $unitOfWork */
        $unitOfWork = $this->createMock(UnitOfWork::class);

        $this->mappingDriver->method("loadRDMMetadataForClass")->willReturn($entityMapping);
        $entityManager->method("getClassMetadata")->willReturn($classMetaData);
        $entityManager->method("getConnection")->willReturn($connection);
        $entityManager->method("getUnitOfWork")->willReturn($unitOfWork);

        $entity = new EntityExample();
        $entity->id = "some_id";
        $entity->foo = "some_ipsum_service";
        $entity->bar = "some_dolor_service";

        $this->valueResolver->method("revertValue")->will($this->returnValueMap([
            [$fooMapping, $entity, "some_ipsum_service", ["foo_column" => "ipsum"]],
            [$barMapping, $entity, "some_dolor_service", ["bar_column" => "dolor"]],
        ]));

        $connection->expects($this->once())->method("update")->with(
            $this->equalTo("some_table"),
            $this->equalTo([
                'foo_column' => 'ipsum',
                'bar_column' => 'dolor'
            ])
        );

        $this->dataLoader->storeDBALDataForEntity($entity, $entityManager);
    }

}
