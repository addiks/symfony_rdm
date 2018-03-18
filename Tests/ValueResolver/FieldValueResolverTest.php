<?php
/**
 * Copyright (C) 2017  Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 * @license GPL-3.0
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

namespace Addiks\RDMBundle\Tests\ValueResolver;

use PHPUnit\Framework\TestCase;
use Addiks\RDMBundle\ValueResolver\FieldValueResolver;
use Addiks\RDMBundle\Mapping\FieldMappingInterface;
use Addiks\RDMBundle\Tests\Hydration\EntityExample;
use Doctrine\DBAL\Schema\Column;

final class FieldValueResolverTest extends TestCase
{

    /**
     * @var FieldValueResolver
     */
    private $valueResolver;

    public function setUp()
    {
        $this->valueResolver = new FieldValueResolver();
    }

    /**
     * @test
     */
    public function shouldResolveValue()
    {
        /** @var FieldMappingInterface $fieldMapping */
        $fieldMapping = $this->createMock(FieldMappingInterface::class);

        /** @var EntityExample $entity */
        $entity = $this->createMock(EntityExample::class);

        /** @var Column $column */
        $column = $this->createMock(Column::class);

        /** @var string $expectedResult */
        $expectedResult = 'bar';

        $fieldMapping->method('getDBALColumn')->willReturn($column);
        $column->method('getName')->willReturn('foo');

        /** @var mixed $actualResult */
        $actualResult = $this->valueResolver->resolveValue($fieldMapping, $entity, [
            'foo' => $expectedResult
        ]);

        $this->assertSame($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function shouldRevertValue()
    {
        /** @var FieldMappingInterface $fieldMapping */
        $fieldMapping = $this->createMock(FieldMappingInterface::class);

        /** @var EntityExample $entity */
        $entity = $this->createMock(EntityExample::class);

        /** @var Column $column */
        $column = $this->createMock(Column::class);

        $fieldMapping->method('getDBALColumn')->willReturn($column);
        $column->method('getName')->willReturn('foo');

        $this->assertEquals(
            ['foo' => "Lorem ipsum"],
            $this->valueResolver->revertValue($fieldMapping, $entity, "Lorem ipsum")
        );
    }

    /**
     * @test
     */
    public function shouldAssertValue()
    {
        /** @var FieldMappingInterface $fieldMapping */
        $fieldMapping = $this->createMock(FieldMappingInterface::class);

        /** @var EntityExample $entity */
        $entity = $this->createMock(EntityExample::class);

        $this->assertSame(null, $this->valueResolver->assertValue(
            $fieldMapping,
            $entity,
            [],
            "Foo"
        ));
    }

}
