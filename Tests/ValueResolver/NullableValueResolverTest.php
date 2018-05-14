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
use Addiks\RDMBundle\ValueResolver\NullableValueResolver;
use Addiks\RDMBundle\ValueResolver\ValueResolverInterface;
use Addiks\RDMBundle\Mapping\MappingInterface;
use Addiks\RDMBundle\Hydration\HydrationContextInterface;
use Addiks\RDMBundle\Mapping\NullableMappingInterface;
use Addiks\RDMBundle\Exception\InvalidMappingException;
use Doctrine\DBAL\Schema\Column;

final class NullableValueResolverTest extends TestCase
{

    /**
     * @var NullableValueResolver
     */
    private $valueResolver;

    /**
     * @var ValueResolverInterface
     */
    private $rootValueResolver;

    public function setUp()
    {
        $this->rootValueResolver = $this->createMock(ValueResolverInterface::class);

        $this->valueResolver = new NullableValueResolver($this->rootValueResolver);
    }

    /**
     * @test
     */
    public function shouldResolveValue()
    {
        /** @var NullableMappingInterface $fieldMapping */
        $fieldMapping = $this->createMock(NullableMappingInterface::class);

        /** @var MappingInterface $fieldMapping */
        $innerMapping = $this->createMock(MappingInterface::class);

        /** @var Column $column */
        $column = $this->createMock(Column::class);
        $column->method("getName")->willReturn("some_column");

        $fieldMapping->method('getInnerMapping')->willReturn($innerMapping);
        $fieldMapping->method('collectDBALColumns')->willReturn([
            $column
        ]);

        /** @var HydrationContextInterface $context */
        $context = $this->createMock(HydrationContextInterface::class);

        /** @var array $dataFromAdditionalColumns */
        $dataFromAdditionalColumns = [
            'some_column' => 'foo'
        ];

        /** @var mixed $expectedResult */
        $expectedResult = 'bar';

        $this->rootValueResolver->expects($this->once())->method('resolveValue')->with(
            $this->equalTo($innerMapping),
            $this->equalTo($context),
            $dataFromAdditionalColumns
        )->willReturn($expectedResult);

        /** @var mixed $actualResult */
        $actualResult = $this->valueResolver->resolveValue(
            $fieldMapping,
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

        /** @var NullableMappingInterface $fieldMapping */
        $fieldMapping = $this->createMock(NullableMappingInterface::class);

        /** @var MappingInterface $fieldMapping */
        $innerMapping = $this->createMock(MappingInterface::class);

        $fieldMapping->method('getInnerMapping')->willReturn($innerMapping);

        $this->valueResolver->resolveValue(
            $fieldMapping,
            $this->createMock(HydrationContextInterface::class),
            []
        );
    }

    /**
     * @test
     */
    public function shouldNotResolveValueOnNull()
    {
        /** @var NullableMappingInterface $fieldMapping */
        $fieldMapping = $this->createMock(NullableMappingInterface::class);

        /** @var MappingInterface $fieldMapping */
        $innerMapping = $this->createMock(MappingInterface::class);

        /** @var Column $column */
        $column = $this->createMock(Column::class);
        $column->method("getName")->willReturn("some_column");

        $fieldMapping->method('getInnerMapping')->willReturn($innerMapping);
        $fieldMapping->method('collectDBALColumns')->willReturn([
            $column
        ]);

        /** @var HydrationContextInterface $context */
        $context = $this->createMock(HydrationContextInterface::class);

        /** @var array $dataFromAdditionalColumns */
        $dataFromAdditionalColumns = [
            'some_column' => false
        ];

        /** @var mixed $expectedResult */
        $expectedResult = 'bar';

        $this->rootValueResolver->expects($this->never())->method('resolveValue');

        $this->assertNull($this->valueResolver->resolveValue(
            $fieldMapping,
            $context,
            $dataFromAdditionalColumns
        ));
    }

    /**
     * @test
     * @dataProvider dataProviderForShouldRevertValue
     */
    public function shouldRevertValue(
        $expectedResult,
        array $revertedData,
        string $columnName
    ) {
        /** @var NullableMappingInterface $fieldMapping */
        $fieldMapping = $this->createMock(NullableMappingInterface::class);

        /** @var MappingInterface $fieldMapping */
        $innerMapping = $this->createMock(MappingInterface::class);

        $fieldMapping->method('getInnerMapping')->willReturn($innerMapping);
        $fieldMapping->method('getDeterminatorColumnName')->willReturn($columnName);

        /** @var HydrationContextInterface $context */
        $context = $this->createMock(HydrationContextInterface::class);

        /** @var mixed $valueFromEntityField */
        $valueFromEntityField = "foo";

        $this->rootValueResolver->expects($this->once())->method('revertValue')->with(
            $this->equalTo($innerMapping),
            $this->equalTo($context),
            $valueFromEntityField
        )->willReturn($revertedData);

        /** @var mixed $actualResult */
        $actualResult = $this->valueResolver->revertValue(
            $fieldMapping,
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
                "some_column"
            ],
            [
                [
                    'some_column' => 123
                ],
                [
                    'some_column' => 123
                ],
                "some_column"
            ],
            [
                [],
                [],
                ""
            ],
        );
    }

    /**
     * @test
     */
    public function shouldNotRevertValueForNonNullableMapping()
    {
        /** @var MappingInterface $fieldMapping */
        $fieldMapping = $this->createMock(MappingInterface::class);

        /** @var HydrationContextInterface $context */
        $context = $this->createMock(HydrationContextInterface::class);

        /** @var mixed $valueFromEntityField */
        $valueFromEntityField = "foo";

        /** @var mixed $actualResult */
        $this->assertEmpty($this->valueResolver->revertValue(
            $fieldMapping,
            $context,
            $valueFromEntityField
        ));
    }

    /**
     * @test
     */
    public function shouldAssertValue()
    {
        $this->assertNull($this->valueResolver->assertValue(
            $this->createMock(MappingInterface::class),
            $this->createMock(HydrationContextInterface::class),
            [],
            null
        ));
    }

}
