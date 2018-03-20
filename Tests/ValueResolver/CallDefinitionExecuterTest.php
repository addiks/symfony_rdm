<?php
/**
 * Copyright (C) 2017  Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 * @license GPL-3.0
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

namespace Addiks\RDMBundle\Tests\ValueResolver;

use PHPUnit\Framework\TestCase;
use Addiks\RDMBundle\ValueResolver\CallDefinitionExecuter;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Addiks\RDMBundle\ValueResolver\ValueResolverInterface;
use Addiks\RDMBundle\Mapping\CallDefinitionInterface;
use Addiks\RDMBundle\Tests\Hydration\EntityExample;
use Addiks\RDMBundle\Tests\ValueObjectExample;
use Addiks\RDMBundle\Tests\Hydration\ServiceExample;
use Addiks\RDMBundle\Mapping\MappingInterface;

final class CallDefinitionExecuterTest extends TestCase
{

    /**
     * @var CallDefinitionExecuter
     */
    private $callDefinitionExecuter;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var ValueResolverInterface
     */
    private $argumentResolver;

    /**
     * @var null|ValueObjectExample
     */
    private static $valueObjectToCreate;

    public function setUp()
    {
        $this->container = $this->createMock(ContainerInterface::class);
        $this->argumentResolver = $this->createMock(ValueResolverInterface::class);

        $this->callDefinitionExecuter = new CallDefinitionExecuter(
            $this->container,
            $this->argumentResolver
        );
    }

    /**
     * @test
     */
    public function shouldExecuteCallDefinitionOnEntity()
    {
        /** @var CallDefinitionInterface $callDefinition */
        $callDefinition = $this->createMock(CallDefinitionInterface::class);

        /** @var EntityExample $entity */
        $entity = $this->createMock(EntityExample::class);

        /** @var array<string> $dataFromAdditionalColumns */
        $dataFromAdditionalColumns = array(
            'lorem' => 'ipsum',
            'dolor' => 'sit amet'
        );

        $callDefinition->method('getObjectReference')->willReturn('self');
        $callDefinition->method('getRoutineName')->willReturn('getBoo');
        $callDefinition->method('getArgumentMappings')->willReturn([]);

        /** @var ValueObjectExample $expectedResult */
        $expectedResult = $this->createMock(ValueObjectExample::class);

        $entity->method('getBoo')->willReturn($expectedResult);

        /** @var mixed $actualResult */
        $actualResult = $this->callDefinitionExecuter->executeCallDefinition(
            $callDefinition,
            $entity,
            $dataFromAdditionalColumns
        );

        $this->assertSame($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function shouldExecuteCallDefinitionOnStaticEntity()
    {
        /** @var CallDefinitionInterface $callDefinition */
        $callDefinition = $this->createMock(CallDefinitionInterface::class);

        /** @var EntityExample $entity */
        $entity = $this->createMock(EntityExample::class);

        /** @var array<string> $dataFromAdditionalColumns */
        $dataFromAdditionalColumns = array(
            'lorem' => 'ipsum',
            'dolor' => 'sit amet'
        );

        $callDefinition->method('getObjectReference')->willReturn(__CLASS__);
        $callDefinition->method('getRoutineName')->willReturn('createValueObject');
        $callDefinition->method('getArgumentMappings')->willReturn([]);

        /** @var ValueObjectExample $expectedResult */
        $expectedResult = $this->createMock(ValueObjectExample::class);

        self::$valueObjectToCreate = $expectedResult;

        /** @var mixed $actualResult */
        $actualResult = $this->callDefinitionExecuter->executeCallDefinition(
            $callDefinition,
            $entity,
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
        /** @var CallDefinitionInterface $callDefinition */
        $callDefinition = $this->createMock(CallDefinitionInterface::class);

        /** @var EntityExample $entity */
        $entity = $this->createMock(EntityExample::class);

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

        $callDefinition->method('getObjectReference')->willReturn('@foo_service');
        $callDefinition->method('getRoutineName')->willReturn('getLorem');
        $callDefinition->method('getArgumentMappings')->willReturn([$argumentMapping]);

        $this->argumentResolver->method('resolveValue')->will($this->returnValueMap([
            [$argumentMapping, $entity, $dataFromAdditionalColumns, 'foo'],
        ]));

        $factoryService->method('getLorem')->will($this->returnValueMap([
            ['foo', $expectedResult],
        ]));

        $this->container->method('get')->will($this->returnValueMap([
            ['foo_service', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $factoryService],
        ]));

        /** @var mixed $actualResult */
        $actualResult = $this->callDefinitionExecuter->executeCallDefinition(
            $callDefinition,
            $entity,
            $dataFromAdditionalColumns
        );

        $this->assertSame($expectedResult, $actualResult);
    }

}