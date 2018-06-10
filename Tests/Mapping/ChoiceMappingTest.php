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
use Addiks\RDMBundle\Mapping\ChoiceMapping;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Types\Type;
use Addiks\RDMBundle\Mapping\MappingInterface;
use Addiks\RDMBundle\Hydration\HydrationContextInterface;
use Addiks\RDMBundle\Tests\Hydration\EntityExample;
use Symfony\Component\DependencyInjection\ContainerInterface;

final class ChoiceMappingTest extends TestCase
{

    /**
     * @var ChoiceMapping
     */
    private $choiceMapping;

    /**
     * @var MappingInterface
     */
    private $optionMappingA;

    /**
     * @var MappingInterface
     */
    private $optionMappingB;

    public function setUp(
        $column = null
    ) {
        if (is_null($column)) {
            $column = new Column("some_column_name", Type::getType('string'), [
                'notnull' => false,
                'length' => 255
            ]);
        }

        $this->optionMappingA = $this->createMock(MappingInterface::class);
        $this->optionMappingB = $this->createMock(MappingInterface::class);

        $this->choiceMapping = new ChoiceMapping(
            $column,
            [
                'foo' => $this->optionMappingA,
                'bar' => $this->optionMappingB,
            ],
            "in foo_file at bar_line!"
        );
    }

    /**
     * @test
     */
    public function shouldHaveChoices()
    {
        $this->assertEquals([
            'foo' => $this->optionMappingA,
            'bar' => $this->optionMappingB
        ], $this->choiceMapping->getChoices());
    }

    /**
     * @test
     */
    public function shouldDescribeOrigin()
    {
        $this->assertEquals("in foo_file at bar_line!", $this->choiceMapping->describeOrigin());
    }

    /**
     * @test
     */
    public function shouldCollectColumns()
    {
        $choiceColumn = new Column("some_column_name", Type::getType('string'), [
            'notnull' => false,
            'length' => 255
        ]);

        $columnA = new Column("foo_column", Type::getType('integer'), []);
        $columnB = new Column("bar_column", Type::getType('date'), []);

        /** @var array<Column> $expectedColumns */
        $expectedColumns = array(
            $choiceColumn,
            $columnA,
            $columnB,
        );

        $this->optionMappingA->method('collectDBALColumns')->willReturn([$columnA]);
        $this->optionMappingB->method('collectDBALColumns')->willReturn([$columnB]);

        /** @var array<Column> $actualColumns */
        $actualColumns = $this->choiceMapping->collectDBALColumns();

        $this->assertEquals($expectedColumns, $actualColumns);
    }

    /**
     * @test
     */
    public function shouldHaveDeterminatorColumn()
    {
        $expectedColumn = new Column("some_column_name", Type::getType('string'), [
            'notnull' => false,
            'length' => 255
        ]);

        $this->assertEquals($expectedColumn, $this->choiceMapping->getDeterminatorColumn());
    }

    /**
     * @test
     */
    public function shouldHaveADeterminatorColumnName()
    {
        $this->assertEquals("some_column_name", $this->choiceMapping->getDeterminatorColumnName());
    }

    /**
     * @test
     */
    public function choosesTheCorrectValue()
    {
        $this->setUp("some_column");

        /** @var HydrationContextInterface $context */
        $context = $this->createMock(HydrationContextInterface::class);
        $context->method('getEntityClass')->willReturn(EntityExample::class);

        /** @var array<scalar> $dataFromAdditionalColumns */
        $dataFromAdditionalColumns = array(
            'lorem' => 'ipsum',
            'some_column' => 'foo',
        );

        /** @var string $expectedValue */
        $expectedValue = "lorem ipsum dolor sit amet";

        $this->optionMappingA->method('resolveValue')->willReturn($expectedValue);
        $this->optionMappingB->method('resolveValue')->willReturn("unexpected value");

        /** @var mixed $actualValue */
        $actualValue = $this->choiceMapping->resolveValue(
            $context,
            $dataFromAdditionalColumns
        );

        $this->assertEquals($expectedValue, $actualValue);
    }

    /**
     * @test
     */
    public function canRevertAChoice()
    {
        /** @var HydrationContextInterface $context */
        $context = $this->createMock(HydrationContextInterface::class);
        $context->method('getEntityClass')->willReturn(EntityExample::class);

        /** @var MappingInterface $optionMappingC */
        $optionMappingC = $this->createMock(MappingInterface::class);

        /** @var scalar $valueFromEntityField */
        $valueFromEntityField = 'lorem ipsum';

        /** @var array<scalar> $expectedValue */
        $expectedValue = [
            "some_column" => 'bar'
        ];

        $this->optionMappingA->method('resolveValue')->willReturn("unexpected value");
        $this->optionMappingB->method('resolveValue')->willReturn("lorem ipsum");
        $optionMappingC->method('resolveValue')->willReturn("lorem ipsum");

        $choiceMapping = new ChoiceMapping(
            "some_column",
            [
                'foo' => $this->optionMappingA,
                'bar' => $this->optionMappingB,
                'baz' => $optionMappingC,
            ],
            "in foo_file at bar_line!"
        );

        /** @var mixed $actualValue */
        $actualValue = $choiceMapping->revertValue(
            $context,
            $valueFromEntityField
        );

        $this->assertEquals($expectedValue, $actualValue);
    }

    /**
     * @test
     */
    public function assertsChosenValue()
    {
        $this->setUp("some_column");

        /** @var HydrationContextInterface $context */
        $context = $this->createMock(HydrationContextInterface::class);
        $context->method('getEntityClass')->willReturn(EntityExample::class);

        /** @var scalar $valueFromEntityField */
        $valueFromEntityField = 'lorem ipsum';

        /** @var array<scalar> $dataFromAdditionalColumns */
        $dataFromAdditionalColumns = array(
            'lorem' => 'ipsum',
            'some_column' => 'foo',
        );

        /** @var scalar $actualValue */
        $actualValue = "some actual value";

        $this->optionMappingA->expects($this->once())->method('assertValue')->with(
            $this->equalTo($context),
            $this->equalTo($dataFromAdditionalColumns),
            $this->equalTo($actualValue)
        );

        $this->choiceMapping->assertValue(
            $context,
            $dataFromAdditionalColumns,
            $actualValue
        );
    }

    /**
     * @test
     */
    public function shouldWakeUpInnerMapping()
    {
        /** @var ContainerInterface $container */
        $container = $this->createMock(ContainerInterface::class);

        $this->optionMappingA->expects($this->once())->method("wakeUpMapping")->with(
            $this->equalTo($container)
        );

        $this->optionMappingB->expects($this->once())->method("wakeUpMapping")->with(
            $this->equalTo($container)
        );

        $this->choiceMapping->wakeUpMapping($container);
    }

}
