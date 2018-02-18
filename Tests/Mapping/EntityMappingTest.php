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
use Addiks\RDMBundle\Mapping\EntityMapping;
use Addiks\RDMBundle\Tests\Hydration\EntityExample;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Types\Type;
use Addiks\RDMBundle\Mapping\MappingInterface;

final class EntityMappingTest extends TestCase
{

    /**
     * @var EntityMapping
     */
    private $entityMapping;

    /**
     * @var MappingInterface
     */
    private $fieldMappingA;

    /**
     * @var MappingInterface
     */
    private $fieldMappingB;

    public function setUp()
    {
        $this->fieldMappingA = $this->createMock(MappingInterface::class);
        $this->fieldMappingB = $this->createMock(MappingInterface::class);

        $this->entityMapping = new EntityMapping(EntityExample::class, [
            $this->fieldMappingA,
            $this->fieldMappingB,
        ]);
    }

    /**
     * @test
     */
    public function shouldStoreEntityName()
    {
        $this->assertEquals(EntityExample::class, $this->entityMapping->getEntityClassName());
    }

    /**
     * @test
     */
    public function shouldStoreFieldMappings()
    {
        $this->assertEquals([
            $this->fieldMappingA,
            $this->fieldMappingB,
        ], $this->entityMapping->getFieldMappings());
    }

    /**
     * @test
     */
    public function shouldDescribeOrigin()
    {
        $this->assertEquals(EntityExample::class, $this->entityMapping->describeOrigin());
    }

    /**
     * @test
     */
    public function shouldCollectColumns()
    {
        $columnA = new Column("column_a", Type::getType("string"), []);
        $columnB = new Column("column_b", Type::getType("string"), []);

        /** @var array<Column> $expectedColumns */
        $expectedColumns = [$columnA, $columnB];

        $this->fieldMappingA->method('collectDBALColumns')->willReturn([$columnA]);
        $this->fieldMappingB->method('collectDBALColumns')->willReturn([$columnB]);

        $this->assertEquals($expectedColumns, $this->entityMapping->collectDBALColumns());
    }

}
