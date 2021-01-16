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
use Addiks\RDMBundle\Hydration\HydrationContextInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use Symfony\Component\DependencyInjection\ContainerInterface;

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

    public function setUp(): void
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

    /**
     * @test
     */
    public function shouldResolveFieldValue()
    {
        /** @var HydrationContextInterface $context */
        $context = $this->createMock(HydrationContextInterface::class);
        $context->method('getEntityClass')->willReturn(EntityExample::class);
        $context->method('getEntityManager')->willReturn($this->createEntityManagerMock());

        /** @var string $expectedResult */
        $expectedResult = 'bar';

        $this->dbalColumn->method('getName')->willReturn('foo');
        $this->dbalColumn->method('getType')->willReturn(Type::getType('string'));

        /** @var mixed $actualResult */
        $actualResult = $this->fieldMapping->resolveValue(
            $context,
            [
                'foo' => $expectedResult
            ]
        );

        $this->assertSame($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function shouldRevertFieldValue()
    {
        /** @var HydrationContextInterface $context */
        $context = $this->createMock(HydrationContextInterface::class);
        $context->method('getEntityClass')->willReturn(EntityExample::class);
        $context->method('getEntityManager')->willReturn($this->createEntityManagerMock());

        $this->dbalColumn->method('getName')->willReturn('foo');
        $this->dbalColumn->method('getType')->willReturn(Type::getType('string'));

        $this->assertEquals(
            ['foo' => "Lorem ipsum"],
            $this->fieldMapping->revertValue($context, "Lorem ipsum")
        );
    }

    /**
     * @test
     */
    public function shouldAssertFieldValue()
    {
        /** @var HydrationContextInterface $context */
        $context = $this->createMock(HydrationContextInterface::class);
        $context->method('getEntityClass')->willReturn(EntityExample::class);
        $context->method('getEntityManager')->willReturn($this->createEntityManagerMock());

        $this->assertSame(null, $this->fieldMapping->assertValue(
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

    /**
     * @test
     */
    public function shouldWakeUpInnerMapping()
    {
        /** @var ContainerInterface $container */
        $container = $this->createMock(ContainerInterface::class);

        $this->assertNull($this->fieldMapping->wakeUpMapping($container));
    }

}
