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
use Addiks\RDMBundle\Mapping\MappingProxy;
use Addiks\RDMBundle\Mapping\MappingInterface;
use Doctrine\DBAL\Schema\Column;
use Addiks\RDMBundle\Hydration\HydrationContextInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Doctrine\DBAL\Types\Type;

final class MappingProxyTest extends TestCase
{

    /**
     * @var MappingProxy
     */
    private $proxy;

    /**
     * @var MappingInterface
     */
    private $innerMapping;

    public function setUp()
    {
        $this->innerMapping = $this->createMock(MappingInterface::class);

        $this->proxy = new MappingProxy($this->innerMapping, "prefix_");
    }

    /**
     * @test
     */
    public function shouldForwardOrigin()
    {
        /** @var string $origin */
        $origin = "some origin!";

        $this->innerMapping->method("describeOrigin")->willReturn($origin);

        $this->assertEquals($origin, $this->proxy->describeOrigin());
    }

    /**
     * @test
     */
    public function shouldPrefixCollectedColumns()
    {
        /** @var Column $column */
        $column = $this->createMock(Column::class);
        $column->method("getName")->willReturn("some_name");
        $column->method("getType")->willReturn(Type::getType("string"));
        $column->method("toArray")->willReturn([
            'notnull' => true,
            'length' => 32
        ]);

        /** @var array<Column> $columns */
        $columns = [$column];

        $this->innerMapping->method("collectDBALColumns")->willReturn($columns);

        $this->assertEquals([
            new Column("prefix_some_name", Type::getType("string"), [
                'notnull' => true,
                'length' => 32
            ])
        ], $this->proxy->collectDBALColumns());
    }

    /**
     * @test
     */
    public function shouldForwardValueResolving()
    {
        /** @var string $value */
        $value = "Lorem ipsum";

        /** @var HydrationContextInterface $context */
        $context = $this->createMock(HydrationContextInterface::class);

        $this->innerMapping->method("resolveValue")->willReturn($value);

        $this->assertEquals($value, $this->proxy->resolveValue($context, []));
    }

    /**
     * @test
     */
    public function shouldForwardValueReverting()
    {
        /** @var array<string, string> $data */
        $data = ["column" => "Lorem ipsum"];

        /** @var HydrationContextInterface $context */
        $context = $this->createMock(HydrationContextInterface::class);

        $this->innerMapping->method("revertValue")->willReturn($data);

        $this->assertEquals($data, $this->proxy->revertValue($context, []));
    }

    /**
     * @test
     */
    public function shouldForwardValueAssertion()
    {
        /** @var array<string, string> $data */
        $data = ["column" => "Lorem ipsum"];

        /** @var HydrationContextInterface $context */
        $context = $this->createMock(HydrationContextInterface::class);

        $this->innerMapping->expects($this->once())->method("assertValue")->with(
            $this->equalTo($context),
            $this->equalTo([]),
            $this->equalTo("Lorem ipsum")
        );

        $this->proxy->assertValue($context, [], 'Lorem ipsum');
    }

    /**
     * @test
     */
    public function shouldForwardWakeUp()
    {
        /** @var ContainerInterface $context */
        $container = $this->createMock(ContainerInterface::class);

        $this->innerMapping->expects($this->once())->method("wakeUpMapping")->with(
            $this->equalTo($container)
        );

        $this->proxy->wakeUpMapping($container);
    }

}
