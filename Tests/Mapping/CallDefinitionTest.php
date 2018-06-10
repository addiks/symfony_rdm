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
use Addiks\RDMBundle\Mapping\CallDefinition;
use Addiks\RDMBundle\Mapping\MappingInterface;
use Addiks\RDMBundle\Tests\Hydration\EntityExample;
use Addiks\RDMBundle\Hydration\HydrationContextInterface;
use Addiks\RDMBundle\Tests\ValueObjectExample;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Addiks\RDMBundle\Tests\Hydration\ServiceExample;

final class CallDefinitionTest extends TestCase
{

    /**
     * @var CallDefinition
     */
    private $callDefinition;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var MappingInterface
     */
    private $argumentMappingA;

    /**
     * @var MappingInterface
     */
    private $argumentMappingB;

    private static $valueObjectToCreate;

    public function setUp(
        string $routineName = "someRoutineName",
        string $objectReference = "someObjectReference",
        bool $isStaticCall = true,
        bool $useArgumentMappingB = true
    ) {
        $this->container = $this->createMock(ContainerInterface::class);
        $this->argumentMappingA = $this->createMock(MappingInterface::class);
        $this->argumentMappingB = $this->createMock(MappingInterface::class);

        /** @var array<MappingInterface> $argumentMappings */
        $argumentMappings = [
            $this->argumentMappingA,
        ];

        if ($useArgumentMappingB) {
            $argumentMappings[] = $this->argumentMappingB;
        }

        $this->callDefinition = new CallDefinition(
            $this->container,
            $routineName,
            $objectReference,
            $argumentMappings,
            $isStaticCall
        );
    }

    /**
     * @test
     */
    public function shouldStoreObjectReference()
    {
        $this->assertEquals("someObjectReference", $this->callDefinition->getObjectReference());
    }

    /**
     * @test
     */
    public function shouldStoreRoutineName()
    {
        $this->assertEquals("someRoutineName", $this->callDefinition->getRoutineName());
    }

    /**
     * @test
     */
    public function shouldKnowIfStatic()
    {
        $this->assertEquals(true, $this->callDefinition->isStaticCall());
    }

    /**
     * @test
     */
    public function shouldStoreArgumentMapping()
    {
        $this->assertEquals([
            $this->createMock(MappingInterface::class),
            $this->createMock(MappingInterface::class),
        ], $this->callDefinition->getArgumentMappings());
    }

    /**
     * @test
     * @dataProvider dataProviderForShouldExecuteCallDefinitionOnEntity
     */
    public function shouldExecuteCallDefinitionOnEntity(
        string $objectReference,
        string $routineName,
        $argumentResult,
        $expectedResult,
        array $hydrationStack,
        $entity
    ) {
        $this->setUp($routineName, $objectReference, false, false);

        /** @var HydrationContextInterface $context */
        $context = $this->createMock(HydrationContextInterface::class);
        $context->method('getEntityClass')->willReturn(EntityExample::class);
        $context->method('getEntity')->willReturn($entity);
        $context->method('getObjectHydrationStack')->willReturn($hydrationStack);

        /** @var array<string> $dataFromAdditionalColumns */
        $dataFromAdditionalColumns = array(
            'lorem' => 'ipsum',
            'dolor' => 'sit amet'
        );

        $this->argumentMappingA->method('resolveValue')->will($this->returnCallback(
            function ($context, $dataFromAdditionalColumns) use ($argumentResult) {
                return $argumentResult;
            }
        ));

        /** @var mixed $actualResult */
        $actualResult = $this->callDefinition->execute(
            $context,
            $dataFromAdditionalColumns
        );

        $this->assertSame($expectedResult, $actualResult);
    }

    public function dataProviderForShouldExecuteCallDefinitionOnEntity(): array
    {
        /** @var EntityExample $entity */
        $entity = $this->createMock(EntityExample::class);

        /** @var ValueObjectExample $expectedResult */
        $expectedResult = $this->createMock(ValueObjectExample::class);

        /** @var MappingInterface $mapping */
        $mapping = $this->createMock(MappingInterface::class);

        $entity->method('getBoo')->willReturn($expectedResult);

        return array(
            [
                '$this',
                'getBoo',
                null,
                $expectedResult,
                [
                    $entity,
                    $this->createMock(EntityExample::class),
                    $entity
                ],
                $entity
            ],
            [
                'self',
                'getBoo',
                null,
                $expectedResult,
                [
                    $entity,
                    $this->createMock(EntityExample::class),
                    $entity
                ],
                $entity
            ],
            [
                'parent',
                'getBoo',
                null,
                $expectedResult,
                [
                    $entity,
                    $entity,
                    $this->createMock(EntityExample::class)
                ],
                $entity
            ],
            [
                '',
                'spl_object_hash',
                $mapping,
                spl_object_hash($mapping),
                [
                    $entity,
                    $entity,
                    $this->createMock(EntityExample::class)
                ],
                $entity
            ],
        );
    }

    /**
     * @test
     */
    public function shouldExecuteCallDefinitionOnStaticEntity()
    {
        $this->setUp('createValueObject', __CLASS__, false, false);

        /** @var HydrationContextInterface $context */
        $context = $this->createMock(HydrationContextInterface::class);
        $context->method('getEntityClass')->willReturn(EntityExample::class);

        /** @var array<string> $dataFromAdditionalColumns */
        $dataFromAdditionalColumns = array(
            'lorem' => 'ipsum',
            'dolor' => 'sit amet'
        );

        /** @var ValueObjectExample $expectedResult */
        $expectedResult = $this->createMock(ValueObjectExample::class);

        self::$valueObjectToCreate = $expectedResult;

        /** @var mixed $actualResult */
        $actualResult = $this->callDefinition->execute(
            $context,
            $dataFromAdditionalColumns
        );

        $this->assertSame($expectedResult, $actualResult);
    }

    public static function createValueObject()
    {
        return self::$valueObjectToCreate;
    }

    /**
     * @test
     */
    public function shouldExecuteCallDefinitionOnService()
    {
        $this->setUp('getLorem', '@foo_service', false, false);

        /** @var HydrationContextInterface $context */
        $context = $this->createMock(HydrationContextInterface::class);
        $context->method('getEntityClass')->willReturn(EntityExample::class);

        /** @var ValueObjectExample $expectedResult */
        $expectedResult = $this->createMock(ValueObjectExample::class);

        /** @var ServiceExample $factoryService */
        $factoryService = $this->createMock(ServiceExample::class);

        /** @var array<string> $dataFromAdditionalColumns */
        $dataFromAdditionalColumns = array(
            'lorem' => 'ipsum',
            'dolor' => 'sit amet'
        );

        /** @var MappingInterface $argumentMapping */
        $argumentMapping = $this->createMock(MappingInterface::class);

        $this->argumentMappingA->expects($this->once())->method('resolveValue')->with(
            $this->equalTo($context),
            $this->equalTo($dataFromAdditionalColumns)
        )->willReturn('foo');

        $factoryService->method('getLorem')->will($this->returnValueMap([
            ['foo', $expectedResult],
        ]));

        $this->container->method('get')->will($this->returnValueMap([
            ['foo_service', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $factoryService],
        ]));

        /** @var mixed $actualResult */
        $actualResult = $this->callDefinition->execute(
            $context,
            $dataFromAdditionalColumns
        );

        $this->assertSame($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function shouldWakeUpCall()
    {
        /** @var ContainerInterface $container */
        $container = $this->createMock(ContainerInterface::class);

        /** @var HydrationContextInterface $context */
        $context = $this->createMock(HydrationContextInterface::class);

        $this->setUp("get", "@some_service", false, false);

        $container->method("has")->willReturn(true);
        $container->method("get")->willReturn($container);

        $this->callDefinition->wakeUpCall($container);

        $this->assertEquals($container, $this->callDefinition->execute($context, []));
    }

    /**
     * @test
     */
    public function shouldSleep()
    {
        $this->assertEquals([
            'objectReference',
            'routineName',
            'argumentMappings',
            'isStaticCall',
        ], $this->callDefinition->__sleep());
    }

}
