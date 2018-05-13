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

    public function setUp()
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


}
