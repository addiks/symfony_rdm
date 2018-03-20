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
use Addiks\RDMBundle\Mapping\ArrayMapping;
use Addiks\RDMBundle\Mapping\MappingInterface;
use Doctrine\DBAL\Schema\Column;
use InvalidArgumentException;

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

    public function setUp()
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

}
