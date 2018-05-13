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

    public function setUp()
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

        $this->innerMapping->method('collectDBALColumns')->willReturn([$innerColumn]);

        /** @var mixed $expectedColumns */
        $expectedColumns = array(
            $innerColumn,
            $this->dbalColumn
        );

        /** @var mixed $actualColumns */
        $actualColumns = $this->mapping->collectDBALColumns();

        $this->assertSame($expectedColumns, $actualColumns);
    }

}
