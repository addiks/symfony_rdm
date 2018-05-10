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
use Addiks\RDMBundle\ValueResolver\ListValueResolver;
use Addiks\RDMBundle\Mapping\ListMappingInterface;
use Addiks\RDMBundle\Hydration\HydrationContextInterface;
use Addiks\RDMBundle\Exception\FailedRDMAssertionException;
use Addiks\RDMBundle\ValueResolver\ValueResolverInterface;
use Doctrine\DBAL\Schema\Column;

final class ListValueResolverTest extends TestCase
{

    /**
     * @var ListValueResolver
     */
    private $valueResolver;

    /**
     * @var ValueResolverInterface
     */
    private $entryValueResolver;

    public function setUp()
    {
        $this->entryValueResolver = $this->createMock(ValueResolverInterface::class);

        $this->valueResolver = new ListValueResolver($this->entryValueResolver);
    }

    /**
     * @test
     */
    public function shouldResolveValue()
    {
        /** @var ListMappingInterface $listMapping */
        $listMapping = $this->createMock(ListMappingInterface::class);

        /** @var HydrationContextInterface $context */
        $context = $this->createMock(HydrationContextInterface::class);

        /** @var Column $column */
        $column = $this->createMock(Column::class);
        $column->method('getName')->willReturn('some_column');

        $listMapping->method('getDBALColumn')->willReturn($column);

        /** @var array $dataFromAdditionalColumns */
        $dataFromAdditionalColumns = [
            'some_column' => '["LOREM","IPSUM","DOLOR","SIT","AMET"]',
        ];

        $this->entryValueResolver->method('resolveValue')->will($this->returnCallback(
            function ($mapping, $context, $data) {
                return strtolower($data['']);
            }
        ));

        /** @var mixed $expectedResult */
        $expectedResult = [
            'lorem',
            'ipsum',
            'dolor',
            'sit',
            'amet'
        ];

        /** @var mixed $actualResult */
        $actualResult = $this->valueResolver->resolveValue(
            $listMapping,
            $context,
            $dataFromAdditionalColumns
        );

        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function shouldRevertValue()
    {
        /** @var ListMappingInterface $listMapping */
        $listMapping = $this->createMock(ListMappingInterface::class);

        /** @var HydrationContextInterface $context */
        $context = $this->createMock(HydrationContextInterface::class);

        /** @var Column $column */
        $column = $this->createMock(Column::class);
        $column->method('getName')->willReturn('some_column');

        $listMapping->method('getDBALColumn')->willReturn($column);

        $this->entryValueResolver->method('revertValue')->will($this->returnCallback(
            function ($mapping, $context, $line) {
                return ['' => strtoupper($line)];
            }
        ));

        /** @var mixed $valueFromEntityField */
        $valueFromEntityField = [
            'lorem',
            'ipsum',
            'dolor',
            'sit',
            'amet'
        ];

        /** @var mixed $expectedResult */
        $expectedResult = [
            'some_column' => '["LOREM","IPSUM","DOLOR","SIT","AMET"]',
        ];

        /** @var mixed $actualResult */
        $actualResult = $this->valueResolver->revertValue(
            $listMapping,
            $context,
            $valueFromEntityField
        );

        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function shouldAssertValue()
    {
        $this->assertNull($this->valueResolver->assertValue(
            $this->createMock(ListMappingInterface::class),
            $this->createMock(HydrationContextInterface::class),
            [],
            null
        ));
    }

    /**
     * @test
     */
    public function shouldThrowExceptionOnFailedAssertion()
    {
        $this->expectException(FailedRDMAssertionException::class);

        $this->valueResolver->assertValue(
            $this->createMock(ListMappingInterface::class),
            $this->createMock(HydrationContextInterface::class),
            [],
            "Lorem ipsum!"
        );
    }

}
