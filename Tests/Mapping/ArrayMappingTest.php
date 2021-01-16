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
use Addiks\RDMBundle\Mapping\ArrayMapping;
use Addiks\RDMBundle\Mapping\MappingInterface;
use Doctrine\DBAL\Schema\Column;
use Addiks\RDMBundle\Hydration\HydrationContextInterface;
use Addiks\RDMBundle\Tests\Hydration\EntityExample;
use Addiks\RDMBundle\Exception\FailedRDMAssertionExceptionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

final class ArrayMappingTest extends TestCase
{

    /**
     * @var ArrayMapping
     */
    private $arrayMapping;

    /**
     * @var MappingInterface
     */
    private $mappingA;

    /**
     * @var MappingInterface
     */
    private $mappingB;

    public function setUp(): void
    {
        $this->mappingA = $this->createMock(MappingInterface::class);
        $this->mappingB = $this->createMock(MappingInterface::class);

        $this->arrayMapping = new ArrayMapping([
            'foo' => $this->mappingA,
            'bar' => $this->mappingB,
        ], "Some Origin");
    }

    /**
     * @test
     */
    public function shouldHaveOrigin()
    {
        $this->assertEquals("Some Origin", $this->arrayMapping->describeOrigin());
    }

    /**
     * @test
     */
    public function shouldStoreEntryMappings()
    {
        $this->assertEquals([
            'foo' => $this->mappingA,
            'bar' => $this->mappingB,
        ], $this->arrayMapping->getEntryMappings());
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

        $this->mappingA->method('collectDBALColumns')->willReturn([
            'lorem' => $columnA,
        ]);

        $this->mappingB->method('collectDBALColumns')->willReturn([
            'ipsum' => $columnB,
        ]);

        $this->assertEquals([
            'lorem' => $columnA,
            'ipsum' => $columnB
        ], $this->arrayMapping->collectDBALColumns());
    }

    /**
     * @test
     */
    public function shouldResolveValueToArray()
    {
        /** @var HydrationContextInterface $context */
        $context = $this->createMock(HydrationContextInterface::class);
        $context->method('getEntityClass')->willReturn(EntityExample::class);

        /** @var mixed $dataFromAdditionalColumns */
        $dataFromAdditionalColumns = array(
            'lorem' => 'ipsum',
            'dolor' => 'sit',
        );

        /** @var array<mixed> $expectedResult */
        $expectedResult = [
            'foo' => 'bar',
            'bar' => 3.1415
        ];

        $this->mappingA->expects($this->once())->method('resolveValue')->with(
            $this->equalTo($context),
            $this->equalTo($dataFromAdditionalColumns)
        )->willReturn('bar');

        $this->mappingB->expects($this->once())->method('resolveValue')->with(
            $this->equalTo($context),
            $this->equalTo($dataFromAdditionalColumns)
        )->willReturn(3.1415);

        /** @var mixed $actualResult */
        $actualResult = $this->arrayMapping->resolveValue($context, $dataFromAdditionalColumns);

        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function shouldRevertValueFromArray()
    {
        /** @var HydrationContextInterface $context */
        $context = $this->createMock(HydrationContextInterface::class);
        $context->method('getEntityClass')->willReturn(EntityExample::class);

        /** @var mixed $valueFromEntityField */
        $valueFromEntityField = array(
            'foo' => 'bar',
            'bar' => 3.1415
        );

        /** @var array<string, string> $expectedResult */
        $expectedResult = array(
            'lorem' => 'ipsum',
            'dolor' => 'sit',
        );

        $this->mappingA->expects($this->once())->method('revertValue')->with(
            $this->equalTo($context),
            $this->equalTo('bar')
        )->willReturn(['lorem' => 'ipsum']);

        $this->mappingB->expects($this->once())->method('revertValue')->with(
            $this->equalTo($context),
            $this->equalTo(3.1415)
        )->willReturn(['dolor' => 'sit']);

        /** @var mixed $actualResult */
        $actualResult = $this->arrayMapping->revertValue($context, $valueFromEntityField);

        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function shouldNotRevertForNonArray()
    {
        /** @var HydrationContextInterface $context */
        $context = $this->createMock(HydrationContextInterface::class);
        $context->method('getEntityClass')->willReturn(EntityExample::class);

        $this->mappingA->expects($this->never())->method('revertValue');
        $this->mappingB->expects($this->never())->method('revertValue');

        /** @var mixed $actualResult */
        $actualResult = $this->arrayMapping->revertValue($context, "a non-array");

        $this->assertEquals([], $actualResult);
    }

    /**
     * @test
     */
    public function shouldAssertValue()
    {
        $this->expectException(FailedRDMAssertionExceptionInterface::class);

        /** @var HydrationContextInterface $context */
        $context = $this->createMock(HydrationContextInterface::class);
        $context->method('getEntityClass')->willReturn(EntityExample::class);

        $this->arrayMapping->assertValue($context, [], "foo");
    }

    /**
     * @test
     */
    public function shouldWakeUpInnerMapping()
    {
        /** @var ContainerInterface $container */
        $container = $this->createMock(ContainerInterface::class);

        $this->mappingA->expects($this->once())->method("wakeUpMapping")->with(
            $this->equalTo($container)
        );

        $this->mappingB->expects($this->once())->method("wakeUpMapping")->with(
            $this->equalTo($container)
        );

        $this->arrayMapping->wakeUpMapping($container);
    }

}
