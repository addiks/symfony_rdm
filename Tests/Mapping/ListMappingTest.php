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
use Addiks\RDMBundle\Mapping\ListMapping;
use Doctrine\DBAL\Schema\Column;
use Addiks\RDMBundle\Mapping\MappingInterface;
use Addiks\RDMBundle\Hydration\HydrationContextInterface;
use Addiks\RDMBundle\Exception\FailedRDMAssertionException;
use Symfony\Component\DependencyInjection\ContainerInterface;

final class ListMappingTest extends TestCase
{

    /**
     * @var SubjectClass
     */
    private $mapping;

    /**
     * @var Column
     */
    private $column;

    /**
     * @var MappingInterface
     */
    private $entryMapping;

    public function setUp(): void
    {
        $this->column = $this->createMock(Column::class);
        $this->entryMapping = $this->createMock(MappingInterface::class);

        $this->mapping = new ListMapping($this->column, $this->entryMapping, "some origin");
    }

    /**
     * @test
     */
    public function shouldHaveDBALColumn()
    {
        $this->assertSame($this->column, $this->mapping->getDBALColumn());
    }

    /**
     * @test
     */
    public function shouldHaveEntryMapping()
    {
        $this->assertSame($this->entryMapping, $this->mapping->getEntryMapping());
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
        $this->assertSame([$this->column], $this->mapping->collectDBALColumns());
    }

    /**
     * @test
     */
    public function shouldResolveListValue()
    {
        /** @var HydrationContextInterface $context */
        $context = $this->createMock(HydrationContextInterface::class);

        $this->column->method('getName')->willReturn('some_column');

        /** @var array $dataFromAdditionalColumns */
        $dataFromAdditionalColumns = [
            'some_column' => '{"a":"LOREM","b":"IPSUM","c":"DOLOR","d":"SIT","e":"AMET"}',
        ];

        $this->entryMapping->method('resolveValue')->will($this->returnCallback(
            function ($context, $data) {
                return strtolower($data['']);
            }
        ));

        /** @var mixed $expectedResult */
        $expectedResult = [
            'a' => 'lorem',
            'b' => 'ipsum',
            'c' => 'dolor',
            'd' => 'sit',
            'e' => 'amet'
        ];

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
    public function shouldRevertListValue()
    {
        /** @var HydrationContextInterface $context */
        $context = $this->createMock(HydrationContextInterface::class);

        $this->column->method('getName')->willReturn('some_column');

        $this->entryMapping->method('revertValue')->will($this->returnCallback(
            function ($context, $line) {
                return ['' => strtoupper($line)];
            }
        ));

        /** @var mixed $valueFromEntityField */
        $valueFromEntityField = [
            'lorem',
            'ipsum',
            'dolor',
            'sit',
            'amet',
        ];

        /** @var mixed $expectedResult */
        $expectedResult = [
            'some_column' => '["LOREM","IPSUM","DOLOR","SIT","AMET"]',
        ];

        /** @var mixed $actualResult */
        $actualResult = $this->mapping->revertValue(
            $context,
            $valueFromEntityField
        );

        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function shouldRevertMultidimensionalListValue()
    {
        /** @var HydrationContextInterface $context */
        $context = $this->createMock(HydrationContextInterface::class);

        $this->column->method('getName')->willReturn('some_column');

        $this->entryMapping->method('revertValue')->will($this->returnCallback(
            function ($context, $data) {
                return $data;
            }
        ));

        /** @var mixed $valueFromEntityField */
        $valueFromEntityField = [
            ['a' => 123, 'b' => true],
            ['a' => 456, 'b' => false],
            []
        ];

        /** @var mixed $expectedResult */
        $expectedResult = [
            'some_column' => '[{"a":123,"b":true},{"a":456,"b":false}]',
        ];

        /** @var mixed $actualResult */
        $actualResult = $this->mapping->revertValue(
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
        $this->assertNull($this->mapping->assertValue(
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

        $this->mapping->assertValue(
            $this->createMock(HydrationContextInterface::class),
            [],
            "Lorem ipsum!"
        );
    }

    /**
     * @test
     */
    public function shouldWakeUpInnerMapping()
    {
        /** @var ContainerInterface $container */
        $container = $this->createMock(ContainerInterface::class);

        $this->entryMapping->expects($this->once())->method("wakeUpMapping")->with(
            $this->equalTo($container)
        );

        $this->mapping->wakeUpMapping($container);
    }

}
