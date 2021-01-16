<?php
/**
 * Copyright (C) 2017  Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 * @license GPL-3.0
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

namespace Addiks;

use PHPUnit\Framework\TestCase;
use Addiks\RDMBundle\Mapping\NullableMapping;
use Doctrine\DBAL\Schema\Column;
use Addiks\RDMBundle\Mapping\MappingInterface;
use Addiks\RDMBundle\Hydration\HydrationContextInterface;
use Addiks\RDMBundle\Exception\InvalidMappingException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use PHPUnit\Framework\MockObject\Rule\InvokedCount;

final class NullableMappingTest extends TestCase
{

    /**
     * @var NullableMapping
     */
    private $mapping;

    /**
     * @var MappingInterface
     */
    private $innerMapping;

    /**
     * @var Column
     */
    private $dbalColumn;

    public function setUp(): void
    {
        $this->innerMapping = $this->createMock(MappingInterface::class);
        $this->dbalColumn = $this->createMock(Column::class);

        $this->mapping = new NullableMapping($this->innerMapping, $this->dbalColumn, "some origin");
    }

    /**
     * @test
     */
    public function shouldHaveDBALColumn()
    {
        $this->assertSame($this->dbalColumn, $this->mapping->getDBALColumn());
    }

    /**
     * @test
     */
    public function shouldHaveDeterminatorColumnName()
    {
        $this->dbalColumn->method('getName')->willReturn("some_column");
        $this->assertSame("some_column", $this->mapping->getDeterminatorColumnName());
    }

    /**
     * @test
     */
    public function shouldHaveInnerMapping()
    {
        $this->assertSame($this->innerMapping, $this->mapping->getInnerMapping());
    }

    /**
     * @test
     */
    public function shouldHaveOrigin()
    {
        $this->assertSame("some origin", $this->mapping->describeOrigin());
    }

    /**
     * @test
     */
    public function shouldCollectDBALColumns()
    {
        /** @var Column $innerColumn */
        $innerColumn = $this->createMock(Column::class);

        $innerColumn->expects($this->once())->method("setNotnull")->with(
            $this->equalTo(false)
        );

        $this->innerMapping->method('collectDBALColumns')->willReturn([$innerColumn]);

        /** @var mixed $expectedColumns */
        $expectedColumns = array(
            $innerColumn,
            $this->dbalColumn
        );

        /** @var mixed $actualColumns */
        $actualColumns = $this->mapping->collectDBALColumns();

        $this->assertEquals($expectedColumns, $actualColumns);
    }

    /**
     * @test
     */
    public function shouldResolveNullableValue()
    {
        $this->dbalColumn->method("getName")->willReturn("some_column");

        /** @var HydrationContextInterface $context */
        $context = $this->createMock(HydrationContextInterface::class);

        /** @var array $dataFromAdditionalColumns */
        $dataFromAdditionalColumns = [
            'some_column' => 'foo'
        ];

        /** @var mixed $expectedResult */
        $expectedResult = 'bar';

        $this->innerMapping->expects($this->once())->method('resolveValue')->with(
            $this->equalTo($context),
            $dataFromAdditionalColumns
        )->willReturn($expectedResult);

        /** @var mixed $actualResult */
        $actualResult = $this->mapping->resolveValue(
            $context,
            $dataFromAdditionalColumns
        );

        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function shouldThrowExceptionOnMissingColumn()
    {
        $this->expectException(InvalidMappingException::class);

        /** @var MappingInterface $fieldMapping */
        $innerMapping = $this->createMock(MappingInterface::class);

        $mapping = new NullableMapping($innerMapping, null, "some origin");

        $mapping->resolveValue(
            $this->createMock(HydrationContextInterface::class),
            []
        );
    }

    /**
     * @test
     */
    public function shouldNotResolveValueOnNull()
    {
        $this->dbalColumn->method('getName')->willReturn("some_column");

        /** @var HydrationContextInterface $context */
        $context = $this->createMock(HydrationContextInterface::class);

        /** @var array $dataFromAdditionalColumns */
        $dataFromAdditionalColumns = [
            'some_column' => false
        ];

        /** @var mixed $expectedResult */
        $expectedResult = 'bar';

        $this->innerMapping->expects($this->never())->method('resolveValue');

        $this->assertNull($this->mapping->resolveValue(
            $context,
            $dataFromAdditionalColumns
        ));
    }

    /**
     * @test
     * @dataProvider dataProviderForShouldRevertValue
     */
    public function shouldRevertNullableValue(
        $expectedResult,
        array $revertedData,
        string $columnName,
        $valueFromEntityField,
        InvokedCount $innerRevertCount
    ) {
        $this->dbalColumn->method('getName')->willReturn($columnName);

        /** @var HydrationContextInterface $context */
        $context = $this->createMock(HydrationContextInterface::class);

        $this->innerMapping->expects($innerRevertCount)->method('revertValue')->with(
            $this->equalTo($context),
            $valueFromEntityField
        )->willReturn($revertedData);

        /** @var mixed $actualResult */
        $actualResult = $this->mapping->revertValue(
            $context,
            $valueFromEntityField
        );

        $this->assertEquals($expectedResult, $actualResult);
    }

    public function dataProviderForShouldRevertValue()
    {
        return array(
            [
                [
                    'some_column' => true
                ],
                [],
                "some_column",
                "foo",
                $this->once()
            ],
            [
                [
                    'some_column' => '0'
                ],
                [],
                "some_column",
                null,
                $this->never()
            ],
            [
                [
                    'some_column' => 123
                ],
                [
                    'some_column' => 123
                ],
                "some_column",
                "foo",
                $this->once()
            ],
            [
                [],
                [],
                "",
                "foo",
                $this->once()
            ],
        );
    }

    /**
     * @test
     */
    public function shouldAssertValue()
    {
        $this->assertNull($this->mapping->assertValue(
            $this->createMock(HydrationContextInterface::class),
            [],
            null
        ));
    }

    /**
     * @test
     */
    public function shouldWakeUpInnerMapping()
    {
        /** @var ContainerInterface $container */
        $container = $this->createMock(ContainerInterface::class);

        $this->innerMapping->expects($this->once())->method("wakeUpMapping")->with(
            $this->equalTo($container)
        );

        $this->mapping->wakeUpMapping($container);
    }

    /**
     * @test
     */
    public function shouldChooseFirstColumnWhenMultipleDefined()
    {
        /** @var Column $expectedColumn */
        $expectedColumn = $this->createMock(Column::class);
        $expectedColumn->method('getName')->willReturn("expected_column");

        $this->dbalColumn->method('getName')->willReturn("some_column");

        /** @var HydrationContextInterface $context */
        $context = $this->createMock(HydrationContextInterface::class);

        $this->innerMapping->method('collectDBALColumns')->willReturn([
            $expectedColumn,
            $this->dbalColumn
        ]);

        $this->innerMapping->method('resolveValue')->willReturn("expected_result");

        $mapping = new NullableMapping($this->innerMapping, null, "some origin");

        /** @var mixed $actualResult */
        $actualResult = $mapping->resolveValue($context, [
            "expected_column" => 'expected_value',
        ]);

        $this->assertEquals('expected_result', $actualResult);
    }

}
