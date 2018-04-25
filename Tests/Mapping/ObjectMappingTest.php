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

final class ObjectMappingTest extends TestCase
{

    /**
     * @var ObjectMapping
     */
    private $subject;

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

    public function setUp()
    {
        $this->factory = $this->createMock(CallDefinitionInterface::class);
        $this->serializer = $this->createMock(CallDefinitionInterface::class);
        $this->fieldMappingA = $this->createMock(MappingInterface::class);
        $this->fieldMappingB = $this->createMock(MappingInterface::class);
        $this->column = $this->createMock(Column::class);

        $this->subject = new ObjectMapping(
            ValueObjectExample::class,
            [
                $this->fieldMappingA,
                $this->fieldMappingB,
            ],
            $this->column,
            "some cool origin",
            $this->factory,
            $this->serializer
        );
    }

    /**
     * @test
     */
    public function shouldStoreClassName()
    {
        $this->assertEquals(ValueObjectExample::class, $this->subject->getClassName());
    }

    /**
     * @test
     */
    public function shouldStoreFieldMappings()
    {
        $this->assertEquals([
            $this->createMock(MappingInterface::class),
            $this->createMock(MappingInterface::class),
        ], $this->subject->getFieldMappings());
    }

    /**
     * @test
     */
    public function shouldStoreOrigin()
    {
        $this->assertEquals("some cool origin", $this->subject->describeOrigin());
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

        $this->assertEquals($expectedColumns, $this->subject->collectDBALColumns());
    }

    /**
     * @test
     */
    public function shouldStoreFactory()
    {
        $this->assertSame($this->factory, $this->subject->getFactory());
    }

    /**
     * @test
     */
    public function shouldStoreSerializer()
    {
        $this->assertSame($this->serializer, $this->subject->getSerializer());
    }

}
