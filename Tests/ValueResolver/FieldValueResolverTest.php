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
use Addiks\RDMBundle\Hydration\HydrationContextInterface;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;

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

        /** @var HydrationContextInterface $context */
        $context = $this->createMock(HydrationContextInterface::class);
        $context->method('getEntityClass')->willReturn(EntityExample::class);
        $context->method('getEntityManager')->willReturn($this->createEntityManagerMock());

        /** @var Column $column */
        $column = $this->createMock(Column::class);

        /** @var string $expectedResult */
        $expectedResult = 'bar';

        $fieldMapping->method('getDBALColumn')->willReturn($column);
        $column->method('getName')->willReturn('foo');
        $column->method('getType')->willReturn(Type::getType('string'));

        /** @var mixed $actualResult */
        $actualResult = $this->valueResolver->resolveValue($fieldMapping, $context, [
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

        /** @var HydrationContextInterface $context */
        $context = $this->createMock(HydrationContextInterface::class);
        $context->method('getEntityClass')->willReturn(EntityExample::class);
        $context->method('getEntityManager')->willReturn($this->createEntityManagerMock());

        /** @var Column $column */
        $column = $this->createMock(Column::class);

        $fieldMapping->method('getDBALColumn')->willReturn($column);
        $column->method('getName')->willReturn('foo');
        $column->method('getType')->willReturn(Type::getType('string'));

        $this->assertEquals(
            ['foo' => "Lorem ipsum"],
            $this->valueResolver->revertValue($fieldMapping, $context, "Lorem ipsum")
        );
    }

    /**
     * @test
     */
    public function shouldAssertValue()
    {
        /** @var FieldMappingInterface $fieldMapping */
        $fieldMapping = $this->createMock(FieldMappingInterface::class);

        /** @var HydrationContextInterface $context */
        $context = $this->createMock(HydrationContextInterface::class);
        $context->method('getEntityClass')->willReturn(EntityExample::class);
        $context->method('getEntityManager')->willReturn($this->createEntityManagerMock());

        $this->assertSame(null, $this->valueResolver->assertValue(
            $fieldMapping,
            $context,
            [],
            "Foo"
        ));
    }

    private function createEntityManagerMock(): EntityManagerInterface
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);

        /** @var Connection $connection */
        $connection = $this->createMock(Connection::class);

        $entityManager->method('getConnection')->willReturn($connection);

        /** @var AbstractPlatform $platform */
        $platform = $this->createMock(AbstractPlatform::class);

        $connection->method('getDatabasePlatform')->willReturn($platform);

        return $entityManager;
    }

}
