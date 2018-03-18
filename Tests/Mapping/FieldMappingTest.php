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
use Addiks\RDMBundle\Mapping\FieldMapping;
use Doctrine\DBAL\Schema\Column;

final class FieldMappingTest extends TestCase
{

    /**
     * @var FieldMapping
     */
    private $fieldMapping;

    /**
     * @var Column
     */
    private $dbalColumn;

    public function setUp()
    {
        $this->dbalColumn = $this->createMock(Column::class);

        $this->fieldMapping = new FieldMapping($this->dbalColumn, "some origin");
    }

    /**
     * @test
     */
    public function shouldStoreDBALColumn()
    {
        $this->assertSame($this->dbalColumn, $this->fieldMapping->getDBALColumn());
    }

    /**
     * @test
     */
    public function shouldStoreOrigin()
    {
        $this->assertSame("some origin", $this->fieldMapping->describeOrigin());
    }

    /**
     * @test
     */
    public function shouldCollectDBALColumns()
    {
        $this->assertSame([$this->dbalColumn], $this->fieldMapping->collectDBALColumns());
    }

}
