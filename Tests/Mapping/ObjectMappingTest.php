<?php
/**
 * Copyright (C) 2017  Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 * @license GPL-3.0
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

namespace Addiks\RDMBundle\Tests\Mapping;

use PHPUnit\Framework\TestCase;
use Addiks\RDMBundle\Mapping\ObjectMapping;
use Addiks\RDMBundle\Mapping\CallDefinitionInterface;
use Addiks\RDMBundle\Tests\ValueObjectExample;
use Addiks\RDMBundle\Mapping\MappingInterface;
use Doctrine\DBAL\Schema\Column;
use Addiks\RDMBundle\Exception\FailedRDMAssertionExceptionInterface;
use Addiks\RDMBundle\Hydration\HydrationContextInterface;
use Addiks\RDMBundle\Tests\Hydration\EntityExample;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use ReflectionClass;
use Symfony\Component\DependencyInjection\ContainerInterface;

final class ObjectMappingTest extends TestCase
{

    /**
     * @var ObjectMapping
     */
    private $objectMapping;

    /**
     * @var CallDefinitionInterface
     */
    private $factory;

    /**
     * @var CallDefinitionInterface
     */
    private $serializer;

    /**
     * @var MappingInterface
     */
    private $fieldMappingA;

    /**
     * @var MappingInterface
     */
    private $fieldMappingB;

    /**
     * @var Column
     */
    private $column;

    public function setUp(
        string $className = ValueObjectExample::class,
        bool $hasFieldMappingB = true,
        bool $useFactory = true,
        bool $useSerializer = true,
        string $id = "some_id",
        string $referenceId = "some_reference",
        string $firstMappingName = "lorem"
    ): void {
        $this->factory = $this->createMock(CallDefinitionInterface::class);
        $this->serializer = $this->createMock(CallDefinitionInterface::class);
        $this->fieldMappingA = $this->createMock(MappingInterface::class);
        $this->fieldMappingB = $this->createMock(MappingInterface::class);
        $this->column = $this->createMock(Column::class);

        /** @var array<string,MappingInterface> $fieldMappings */
        $fieldMappings = [
            $firstMappingName => $this->fieldMappingA,
        ];

        if ($hasFieldMappingB) {
            $fieldMappings['dolor'] = $this->fieldMappingB;
        }

        /** @var CallDefinitionInterface|null $factory */
        $factory = null;

        /** @var CallDefinitionInterface|null $serializer */
        $serializer = null;

        if ($useFactory) {
            $factory = $this->factory;
        }

        if ($useSerializer) {
            $serializer = $this->serializer;
        }

        $this->objectMapping = new ObjectMapping(
            $className,
            $fieldMappings,
            $this->column,
            "some cool origin",
            $factory,
            $serializer,
            $id,
            $referenceId
        );
    }

    /**
     * @test
     */
    public function shouldStoreClassName()
    {
        $this->assertEquals(ValueObjectExample::class, $this->objectMapping->getClassName());
    }

    /**
     * @test
     */
    public function shouldStoreFieldMappings()
    {
        $this->assertEquals([
            'lorem' => $this->createMock(MappingInterface::class),
            'dolor' => $this->createMock(MappingInterface::class),
        ], $this->objectMapping->getFieldMappings());
    }

    /**
     * @test
     */
    public function shouldStoreOrigin()
    {
        $this->assertEquals("some cool origin", $this->objectMapping->describeOrigin());
    }

    /**
     * @test
     */
    public function shouldStoreDBALColumn()
    {
        $this->assertSame($this->column, $this->objectMapping->getDBALColumn());
    }

    /**
     * @test
     */
    public function shouldStoreId()
    {
        $this->assertSame("some_id", $this->objectMapping->getId());
    }

    /**
     * @test
     */
    public function shouldStoreReferenceId()
    {
        $this->assertSame("some_reference", $this->objectMapping->getReferencedId());
    }

    /**
     * @test
     */
    public function shouldCollectDBALColumns()
    {
        /** @var Column $columnA */
        $columnA = $this->createMock(Column::class);

        /** @var Column $columnB */
        $columnB = $this->createMock(Column::class);

        /** @var array<Column> $expectedColumns */
        $expectedColumns = [$columnA, $columnB, $this->column];

        $this->fieldMappingA->method('collectDBALColumns')->willReturn([$columnA]);
        $this->fieldMappingB->method('collectDBALColumns')->willReturn([$columnB]);

        $this->assertEquals($expectedColumns, $this->objectMapping->collectDBALColumns());
    }

    /**
     * @test
     */
    public function shouldStoreFactory()
    {
        $this->assertSame($this->factory, $this->objectMapping->getFactory());
    }

    /**
     * @test
     */
    public function shouldStoreSerializer()
    {
        $this->assertSame($this->serializer, $this->objectMapping->getSerializer());
    }

    /**
     * @test
     */
    public function shouldResolveObjectValue()
    {
        $this->setUp(ValueObjectExample::class, false, true, false, "some_id", "", "amet");

        /** @var ValueObjectExample $object */
        $object = $this->createMock(ValueObjectExample::class);

        $this->column->expects($this->once())->method("getName")->willReturn("some_column");

        /** @var HydrationContextInterface $context */
        $context = $this->createMock(HydrationContextInterface::class);
        $context->method('getEntityClass')->willReturn(EntityExample::class);
        $context->expects($this->once())->method('registerValue')->with(
            "some_id",
            $object
        );

        /** @var mixed $dataFromAdditionalColumns */
        $dataFromAdditionalColumns = array(
            'lorem' => 'ipsum',
            'dolor' => 'sit amet',
            'amet'  => 'EXPECTED',
        );

        $this->factory->method('execute')->willReturn($object);

        $this->fieldMappingA->expects($this->once())->method('resolveValue')->with(
            $this->equalTo($context),
            $this->equalTo([
                'amet'  => 'EXPECTED',
                'lorem' => 'ipsum',
                'dolor' => 'sit amet',
            ])
        )->willReturn("FOO BAR BAZ");

        /** @var mixed $actualObject */
        $actualObject = $this->objectMapping->resolveValue($context, $dataFromAdditionalColumns);

        $this->assertSame($actualObject, $object);

        $reflectionClass = new ReflectionClass(ValueObjectExample::class);

        /** @var ReflectionProperty $reflectionProperty */
        $reflectionProperty = $reflectionClass->getProperty("amet");
        $reflectionProperty->setAccessible(true);

        $this->assertEquals("FOO BAR BAZ", $reflectionProperty->getValue($object));
    }

    /**
     * @test
     */
    public function shouldNotLoadFactoryArgumentsWhenDataGiven()
    {
        $this->setUp(ValueObjectExample::class, false, true, false, "some_id", "");

        /** @var ValueObjectExample $object */
        $object = $this->createMock(ValueObjectExample::class);

        $this->column->expects($this->never())->method("getName");

        /** @var HydrationContextInterface $context */
        $context = $this->createMock(HydrationContextInterface::class);
        $context->method('getEntityClass')->willReturn(EntityExample::class);
        $context->expects($this->once())->method('registerValue')->with(
            "some_id",
            $object
        );

        $this->factory->method('execute')->willReturn($object);

        /** @var mixed $dataFromAdditionalColumns */
        $dataFromAdditionalColumns = array(
            'lorem' => 'ipsum',
            'dolor' => 'sit amet',
            '' => 'foo'
        );

        $this->fieldMappingA->method('resolveValue')->willReturn("FOO BAR BAZ");

        $this->objectMapping->resolveValue($context, $dataFromAdditionalColumns);
    }

    /**
     * @test
     */
    public function shouldRevertObjectValue()
    {
        $this->setUp(ValueObjectExample::class, false, false, false, "some_id", "", "amet");

        /** @var HydrationContextInterface $context */
        $context = $this->createMock(HydrationContextInterface::class);
        $context->method('getEntityClass')->willReturn(EntityExample::class);
        $context->method('getEntityManager')->willReturn($this->createEntityManagerMock());

        $actualObject = new ValueObjectExample('foo', 'sit amet');
        $actualObject->setAmet('EXPECTED');

        $this->fieldMappingA->method('revertValue')->will($this->returnValueMap([
            [$context, 'sit amet', ['amet' => "FOO BAR BAZ"]],
            [$context, 'EXPECTED', ['amet' => "QWE ASD YXC"]],
        ]));

        /** @var Type $type */
        $type = $this->createMock(Type::class);

        $this->column->method('getType')->willReturn($type);

        /** @var array $actualData */
        $actualData = $this->objectMapping->revertValue($context, $actualObject);

        $this->assertEquals(['amet' => "QWE ASD YXC"], $actualData);
    }

    /**
     * @test
     */
    public function shouldFailAssertionOnWrongObject()
    {
        $this->setUp(ValueObjectExample::class);

        $dataFromAdditionalColumns = array(
            'lorem' => 'ipsum',
            'dolor' => 'sit amet',
        );

        /** @var mixed $actualValue */
        $actualValue = $this->createMock(EntityExample::class);

        $this->expectException(FailedRDMAssertionExceptionInterface::class);

        /** @var HydrationContextInterface $context */
        $context = $this->createMock(HydrationContextInterface::class);
        $context->method('getEntityClass')->willReturn(EntityExample::class);

        $this->objectMapping->assertValue($context, $dataFromAdditionalColumns, $actualValue);
    }

    private function createEntityManagerMock(): EntityManagerInterface
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);

        /** @var Connection $connection */
        $connection = $this->createMock(Connection::class);

        $entityManager->method('getConnection')->willReturn($connection);

        /** @var AbstractPlatform $platform */
        $platform = $this->createMock(AbstractPlatform::class);

        $connection->method('getDatabasePlatform')->willReturn($platform);

        return $entityManager;
    }

    /**
     * @test
     */
    public function shouldWakeUpInnerMapping()
    {
        /** @var ContainerInterface $container */
        $container = $this->createMock(ContainerInterface::class);

        $this->factory->expects($this->once())->method("wakeUpCall")->with(
            $this->equalTo($container)
        );

        $this->serializer->expects($this->once())->method("wakeUpCall")->with(
            $this->equalTo($container)
        );

        $this->objectMapping->wakeUpMapping($container);
    }

}
