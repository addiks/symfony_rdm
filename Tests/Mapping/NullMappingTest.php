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
use Addiks\RDMBundle\Mapping\NullMapping;
use Addiks\RDMBundle\Hydration\HydrationContextInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

final class NullMappingTest extends TestCase
{

    /**
     * @var NullMapping
     */
    private $mapping;

    public function setUp()
    {
        $this->mapping = new NullMapping("some origin");
    }

    /**
     * @test
     */
    public function shouldDescribeOrigin()
    {
        $this->assertEquals("some origin", $this->mapping->describeOrigin());
    }

    /**
     * @test
     */
    public function shouldNotCollectAnyColumns()
    {
        $this->assertEmpty($this->mapping->collectDBALColumns());
    }

    /**
     * @test
     */
    public function shouldNotResolveValue()
    {
        $this->assertNull($this->mapping->resolveValue(
            $this->createMock(HydrationContextInterface::class),
            []
        ));
    }

    /**
     * @test
     */
    public function shouldNotRevertValue()
    {
        $this->assertEmpty($this->mapping->revertValue(
            $this->createMock(HydrationContextInterface::class),
            null
        ));
    }

    /**
     * @test
     */
    public function shouldNotAssertValue()
    {
        $this->assertNull($this->mapping->assertValue(
            $this->createMock(HydrationContextInterface::class),
            [],
            null
        ));
    }

    /**
     * @test
     */
    public function shouldWakeUpInnerMapping()
    {
        /** @var ContainerInterface $container */
        $container = $this->createMock(ContainerInterface::class);

        $this->assertNull($this->mapping->wakeUpMapping($container));
    }

}
