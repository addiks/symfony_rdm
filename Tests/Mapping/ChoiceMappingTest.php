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
use Addiks\RDMBundle\Mapping\ChoiceMapping;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Types\Type;
use Addiks\RDMBundle\Mapping\MappingInterface;

final class ChoiceMappingTest extends TestCase
{

    /**
     * @var ChoiceMapping
     */
    private $choiceMapping;

    /**
     * @var MappingInterface
     */
    private $optionMappingA;

    /**
     * @var MappingInterface
     */
    private $optionMappingB;

    public function setUp()
    {
        $this->optionMappingA = $this->createMock(MappingInterface::class);
        $this->optionMappingB = $this->createMock(MappingInterface::class);

        $this->choiceMapping = new ChoiceMapping(
            new Column("some_column_name", Type::getType('string'), [
                'notnull' => false,
                'length' => 255
            ]),
            [
                $this->optionMappingA,
                $this->optionMappingB,
            ],
            "in foo_file at bar_line!"
        );
    }

    /**
     * @test
     */
    public function shouldHaveChoices()
    {
        $this->assertEquals([$this->optionMappingA, $this->optionMappingB], $this->choiceMapping->getChoices());
    }

    /**
     * @test
     */
    public function shouldDescribeOrigin()
    {
        $this->assertEquals("in foo_file at bar_line!", $this->choiceMapping->describeOrigin());
    }

    /**
     * @test
     */
    public function shouldCollectColumns()
    {
        $choiceColumn = new Column("some_column_name", Type::getType('string'), [
            'notnull' => false,
            'length' => 255
        ]);

        $columnA = new Column("foo_column", Type::getType('integer'), []);
        $columnB = new Column("bar_column", Type::getType('date'), []);

        /** @var array<Column> $expectedColumns */
        $expectedColumns = array(
            $choiceColumn,
            $columnA,
            $columnB,
        );

        $this->optionMappingA->method('collectDBALColumns')->willReturn([$columnA]);
        $this->optionMappingB->method('collectDBALColumns')->willReturn([$columnB]);

        /** @var array<Column> $actualColumns */
        $actualColumns = $this->choiceMapping->collectDBALColumns();

        $this->assertEquals($expectedColumns, $actualColumns);
    }

    /**
     * @test
     */
    public function shouldHaveDeterminatorColumn()
    {
        $expectedColumn = new Column("some_column_name", Type::getType('string'), [
            'notnull' => false,
            'length' => 255
        ]);

        $this->assertEquals($expectedColumn, $this->choiceMapping->getDeterminatorColumn());
    }

    /**
     * @test
     */
    public function shouldHaveADeterminatorColumnName()
    {
        $this->assertEquals("some_column_name", $this->choiceMapping->getDeterminatorColumnName());
    }

}
